<?php

namespace AwardWallet\MainBundle\DependencyInjection;

use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class WellKnownAssociationsCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $cacheDir = $container->getParameter('kernel.cache_dir');
        $isDebug = $container->getParameter('kernel.debug');

        $configCache = new ConfigCache("{$cacheDir}/aw/wellKnownRoutes.php", $isDebug);

        if (!$configCache->isFresh()) {
            $rootDir = $container->getParameter('kernel.root_dir');
            $wellKnownSourceFile = "{$rootDir}/../web/.well-known/apple-app-site-association";
            $wellKnownData = @\json_decode(@\file_get_contents($wellKnownSourceFile), true);

            if (\json_last_error() !== \JSON_ERROR_NONE) {
                $this->throwInvalid($wellKnownSourceFile);
            }

            $paths = $wellKnownData['applinks']['details'][0]['paths'] ?? [];

            if (!$paths) {
                $this->throwInvalid($wellKnownSourceFile);
            }

            $static = [];
            $dynamic = [];

            foreach ($paths as $path) {
                if (
                    false === \mb_strpos($path, '*')
                    && false === \mb_strpos($path, '?')
                ) {
                    // static
                    $static[$path] = true;
                } else {
                    $dynamic[] =
                        it(\explode('*', $path))
                        ->map(function (string $pathPart) {
                            return
                                it(\explode('?', $pathPart))
                                ->map(function (string $pathPart) { return \preg_quote($pathPart, '#'); })
                                ->joinToString('[^/]?');
                        })
                        ->joinToString('[^/]+');
                }
            }

            $dump = ['static' => $static];

            if ($dynamic) {
                $dump['dynamic'] = '#^(' . \implode('|', $dynamic) . ')$#ims';
            }

            $configCache->write($this->generatePhpBody($dump), [$resource = new FileResource($wellKnownSourceFile)]);
            $container->addResource($resource);
        }
    }

    protected function generatePhpBody(array $dump): string
    {
        return '<?php return ' . \var_export($dump, true) . ';';
    }

    protected function throwInvalid(string $wellKnownSourceFile): void
    {
        throw new \RuntimeException('File not found or invalid format: ' . $wellKnownSourceFile);
    }
}
