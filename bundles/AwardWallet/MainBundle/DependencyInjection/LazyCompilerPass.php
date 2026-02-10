<?php

namespace AwardWallet\MainBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class LazyCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!($extensionConfig = $container->getExtensionConfig('award_wallet_main'))) {
            return;
        }

        foreach (array_merge(...$extensionConfig)['mark_services_lazy'] ?? [] as $serviceName) {
            $definition = $container->getDefinition($serviceName);
            $definition->setLazy(true);
        }
    }
}
