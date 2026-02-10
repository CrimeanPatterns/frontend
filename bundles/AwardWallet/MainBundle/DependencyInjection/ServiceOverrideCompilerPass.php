<?php

namespace AwardWallet\MainBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class ServiceOverrideCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!($extensionConfig = $container->getExtensionConfig('award_wallet_main'))) {
            return;
        }

        foreach (
            it($extensionConfig)
            ->flatMap(function (array $config) { return $config['service_name_override'] ?? []; })
            ->toArrayWithKeys() as $serviceName => $newServiceName
        ) {
            try {
                $alias = $container->getAlias($newServiceName);
            } catch (\Throwable $e) {
                $alias = $newServiceName;
            }

            $definition = $container->getDefinition((string) $alias);
            $container->setDefinition($serviceName, $definition);
            $container->removeDefinition($alias);
        }
    }
}
