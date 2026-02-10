<?php

namespace AwardWallet\MainBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class PublicTestAliasesCompilerPass implements CompilerPassInterface
{
    public const TEST_SERVICE_PREFIX = "test.";

    public function process(ContainerBuilder $container)
    {
        if (!$container->getParameter("test_services")) {
            return;
        }

        // may be decorating services will be simpler? (decorates:)
        // leave it as is for now

        $serviceAliases = [];

        foreach ($container->getAliases() as $definitionId => $target) {
            $targetId = (string) $target;
            $serviceAliases[$targetId][] = [$definitionId, $target->isPublic()];
        }

        $aliasesTargets = [];

        foreach ($container->getDefinitions() as $id => $definition) {
            // aliasing controllers will fail in RemoveEmptyControllerArgumentLocatorsPass, it expects controllers in services
            // decorated services will fail with non-existent service "AwardWallet\MainBundle\FrameworkExtension\Migrations\MigrationFactoryDecorator.inner"
            if (strpos($id, 'AwardWallet\\') === 0 && substr($id, -strlen('Controller')) !== 'Controller' && $definition->getDecoratedService() === null) {
                //                $container->setAlias("test." . $id, new Alias($id, true));
                // we will create public "test." service, and private alias to it
                $container->removeDefinition($id);
                $wasPublic = $definition->isPublic();
                $definition->setPublic(true);
                $container->setDefinition(self::TEST_SERVICE_PREFIX . $id, $definition);
                $container->setAlias($id, new Alias(self::TEST_SERVICE_PREFIX . $id, $wasPublic));

                // replace aliases to new service
                foreach ($serviceAliases[$id] ?? [] as [$alias, $isPublic]) {
                    $container->setAlias($alias, new Alias(self::TEST_SERVICE_PREFIX . $id, $isPublic));
                    $aliasesTargets[$alias] = self::TEST_SERVICE_PREFIX . $id;
                }
            }
        }

        $container->setParameter("aliases_targets", $aliasesTargets);
    }
}
