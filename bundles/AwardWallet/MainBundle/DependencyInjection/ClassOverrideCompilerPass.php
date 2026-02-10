<?php

namespace AwardWallet\MainBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class ClassOverrideCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!($extensionConfig = $container->getExtensionConfig('award_wallet_main'))) {
            return;
        }

        foreach (
            it($extensionConfig)
            ->flatMap(function (array $config) { return $config['service_class_override'] ?? []; })
            ->toArrayWithKeys() as $serviceName => $classOverride
        ) {
            try {
                $alias = $container->getAlias($serviceName);
            } catch (\Throwable $e) {
                $alias = $serviceName;
            }

            $definition = $container->getDefinition((string) $alias);
            $definition->setClass($classOverride);
        }
    }
}
