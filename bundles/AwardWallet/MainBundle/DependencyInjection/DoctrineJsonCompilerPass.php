<?php

namespace AwardWallet\MainBundle\DependencyInjection;

use AwardWallet\MainBundle\FrameworkExtension\Doctrine\DoctrineJsonTypeConfigurator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DoctrineJsonCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $definition = $container->getDefinition("doctrine.dbal.connection_factory");
        $definition->setConfigurator([$container->getDefinition(DoctrineJsonTypeConfigurator::class), 'configure']);
    }
}
