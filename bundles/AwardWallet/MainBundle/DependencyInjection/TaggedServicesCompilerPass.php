<?php

namespace AwardWallet\MainBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class TaggedServicesCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!($extensionConfig = $container->getExtensionConfig('award_wallet_main'))) {
            return;
        }

        foreach ($extensionConfig[0]['tagged_services'] ?? [] as $taggedServiceData) {
            if (
                !isset(
                    $taggedServiceData['method'],
                    $taggedServiceData['tag']
                )
                && (
                    !isset($taggedServiceData['service'])
                    && !isset($taggedServiceData['service_tag'])
                )
            ) {
                throw new \RuntimeException(sprintf("Incomplete tagged service definition: %s", json_encode($taggedServiceData)));
            }

            if (isset($taggedServiceData['service'])) {
                if (!$container->hasDefinition($taggedServiceData['service'])) {
                    throw new \RuntimeException(sprintf('Missing service "%s"', $taggedServiceData['service']));
                }

                $parentServices = [$container->getDefinition($taggedServiceData['service'])];
            } else {
                $taggedParentServiceIds = $container->findTaggedServiceIds($taggedServiceData['service_tag']);
                $parentServices = [];

                foreach ($taggedParentServiceIds as $taggedParentServiceId => $_) {
                    $parentServices[] = $container->getDefinition($taggedParentServiceId);
                }
            }

            $childServices = $container->findTaggedServiceIds($taggedServiceData['tag']);

            foreach ($parentServices as $parentService) {
                foreach ($childServices as $childServiceId => $_) {
                    if (is_array($childServiceId)) {
                        throw new \RuntimeException(json_encode($childServiceId));
                    }

                    $parentService->addMethodCall($taggedServiceData['method'], [new Reference($childServiceId)]);
                }
            }
        }
    }
}
