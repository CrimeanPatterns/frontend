<?php

namespace AwardWallet\MainBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class SqlLoggerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->getParameter("kernel.debug")) {
            $definition = $container->getDefinition('doctrine.dbal.default_connection');
            $definition->setConfigurator([new Reference("aw.sql.counter"), "init"]);
        }
    }
}
