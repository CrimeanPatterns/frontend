<?php

namespace AwardWallet\MainBundle\DependencyInjection;

use AwardWallet\MainBundle\Timeline\Manager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class TimelineFormatterCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $manager = $container->getDefinition(Manager::class);

        foreach ($container->findTaggedServiceIds('timeline.format_handler') as $formatHandlerId => $formatHandlerTag) {
            if (!isset($formatHandlerTag[0]['format'])) {
                throw new \InvalidArgumentException(sprintf('Timeline format handler service "%s" must have "format" attribute', $formatHandlerId));
            }

            $formatHandler = $container->getDefinition($formatHandlerId);

            foreach ($container->findTaggedServiceIds("timeline.item_formatter") as $itemFormatterId => $itemFormatterTags) {
                foreach ($itemFormatterTags as $itemFormatterTag) {
                    if (!isset($itemFormatterTag['format'])) {
                        throw new \InvalidArgumentException(sprintf('Timeline formatter service "%s" must have an "format" attribute', $formatHandlerId));
                    }

                    if (!isset($itemFormatterTag['type'])) {
                        throw new \InvalidArgumentException(sprintf('Timeline formatter service "%s" must have an "type" attribute', $formatHandlerId));
                    }

                    if ($itemFormatterTag['format'] !== $formatHandlerTag[0]['format']) {
                        continue;
                    }

                    $formatHandler->addMethodCall('addItemFormatter', [
                        $itemFormatterTag['type'],
                        new Reference($itemFormatterId),
                    ]);
                }
            }

            $manager->addMethodCall('addFormatHandler', [
                $formatHandlerTag[0]['format'],
                new Reference($formatHandlerId),
            ]);
        }
    }
}
