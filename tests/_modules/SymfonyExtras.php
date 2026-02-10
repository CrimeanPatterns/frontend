<?php

namespace Codeception\Module;

use AwardWallet\MainBundle\DependencyInjection\PublicTestAliasesCompilerPass;
use Codeception\Module;
use Codeception\TestInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SymfonyExtras extends Module
{
    /** @var array */
    protected static $definitions = [];

    /**
     * Unload all cached definitions.
     */
    public function _after(TestInterface $test)
    {
        self::$definitions = [];
    }

    /**
     * @param string $service
     * @return string mixed
     */
    public function getServiceClass($service)
    {
        /** @var Symfony $symfony2 */
        $symfony2 = $this->getModule('Symfony');
        $this->loadDefinitions($symfony2->_getContainer());

        $result = self::$definitions[PublicTestAliasesCompilerPass::TEST_SERVICE_PREFIX . $service] ?? self::$definitions[$service] ?? null;

        if ($result === null) {
            throw new \InvalidArgumentException(sprintf('Service "%s" is not defined or class name cannot be detected', $service));
        }

        return $result;
    }

    protected function loadDefinitions(ContainerInterface $container)
    {
        if (empty(self::$definitions)) {
            if (!$container->hasParameter('debug.container.dump')) {
                throw new \BadMethodCallException('Class autodetection works only with "debug" enabled');
            }

            $dump = $container->getParameter('debug.container.dump');
            $xml = simplexml_load_file($dump);
            $aliasesMap = [];

            foreach ($xml->services->service as $service) {
                $attributes = $service->attributes();
                $id = (string) $attributes['id'];

                // check alias
                if (isset($attributes['alias'])) {
                    $aliasId = (string) $attributes['alias'];
                    $aliasesMap[$id] = $aliasId;

                    continue;
                }

                $class = (string) $attributes['class'];

                if (!empty($class)) {
                    self::$definitions[$id] = $class;
                }
            }

            // resolve aliases
            foreach ($aliasesMap as $id => $aliasId) {
                if (isset(self::$definitions[$aliasId])) {
                    self::$definitions[$id] = self::$definitions[$aliasId];
                }
            }
        }
    }
}
