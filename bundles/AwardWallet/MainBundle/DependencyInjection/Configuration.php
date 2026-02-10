<?php

namespace AwardWallet\MainBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('award_wallet_main');
        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('service_class_override')
                    ->normalizeKeys(false)
                    ->scalarPrototype()->end()
                ->end()
                ->arrayNode('service_name_override')
                    ->normalizeKeys(false)
                    ->scalarPrototype()->end()
                ->end()
                ->arrayNode('mark_services_lazy')
                    ->scalarPrototype()->end()
                ->end()
                ->arrayNode('tagged_services')
                    ->normalizeKeys(false)
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('service')->end()
                            ->scalarNode('service_tag')->end()
                            ->scalarNode('method')->end()
                            ->scalarNode('tag')->end()
                        ->end()
                ->end()

            ->end();

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        return $treeBuilder;
    }
}
