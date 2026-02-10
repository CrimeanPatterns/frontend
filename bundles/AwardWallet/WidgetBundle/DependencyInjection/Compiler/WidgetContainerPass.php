<?php

namespace AwardWallet\WidgetBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class WidgetContainerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        foreach ($container->findTaggedServiceIds('aw.widget_container') as $containerId => $containerTags) {
            $definition = $container->getDefinition($containerId);

            foreach ($container->findTaggedServiceIds($containerId) as $id => $tags) {
                $definition->addMethodCall(
                    'addItem',
                    [new Reference($id)]
                );

                $tags = $tags[0];

                if (array_key_exists('position', $tags)) {
                    $definition->addMethodCall(
                        'moveItem',
                        [new Reference($id),  $tags['position']]
                    );
                }
            }
        }
    }
}
