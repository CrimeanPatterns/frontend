<?php

namespace AwardWallet\MainBundle\Form\Helper;

use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Globals\StringUtils as Str;
use Symfony\Component\Form\FormBuilderInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class MobileExtensionLoader
{
    /**
     * @var string
     */
    protected $rootDir;
    /**
     * @var ApiVersioningService
     */
    private $apiVersioningService;

    public function __construct(string $rootDir, ApiVersioningService $apiVersioningService)
    {
        $this->rootDir = $rootDir;
        $this->apiVersioningService = $apiVersioningService;
    }

    public function loadExtensionByPath(FormBuilderInterface $formBuilder, $extensionPaths = []): void
    {
        $extensionPaths = (array) $extensionPaths;
        $formInterfaceName = 'MobileForm';

        if ($this->apiVersioningService->supports(MobileVersions::FORM_INTERFACE_V2)) {
            $formInterfaceName = 'MobileForm.v2';
        }

        if (
            \is_string($mobileFormJs = @\file_get_contents($this->rootDir . "/../mobile/scripts/services/{$formInterfaceName}.js"))
            && !Str::isEmpty($mobileFormJs)

            && \is_string($abstractFormJs = @\file_get_contents($this->rootDir . '/../web/assets/common/js/abstractFormExtension.js'))
            && !Str::isEmpty($abstractFormJs)
        ) {
            $formBuilder->setAttribute('jsFormInterface', $abstractFormJs . "\n\n" . $mobileFormJs);
        } else {
            throw new \RuntimeException('Js form interface files not found!');
        }

        if (!$extensionPaths) {
            return;
        }

        if ($this->apiVersioningService->supports(MobileVersions::MULTIPLE_JS_FORM_EXTENSIONS)) {
            $formBuilder->setAttribute(
                'jsProviderExtension',
                it($extensionPaths)
                    ->map(fn (string $path) => $this->getExtension($path))
                    ->filterNotNull()
                    ->toArrayWithKeys()
            );
        } else {
            if (
                (\count($extensionPaths) > 1)
                || !isset($extensionPaths[0])
            ) {
                throw new \RuntimeException('Multiple js form extensions are not supported!');
            }

            $formBuilder->setAttribute('jsProviderExtension', $this->getExtension($extensionPaths[0]));
        }
    }

    public function getExtension(string $path): ?string
    {
        if ($this->apiVersioningService->supports(MobileVersions::FORM_INTERFACE_V2)) {
            $newPath = preg_replace('/(\.js)$/ims', '.v2$1', $path);

            if (file_exists($this->rootDir . '/../' . $newPath)) {
                $path = $newPath;
            }
        }

        if (
            \is_string($formJs = @\file_get_contents($this->rootDir . '/../' . $path))
            && !Str::isEmpty($formJs)
        ) {
            return $formJs;
        }

        return null;
    }
}
