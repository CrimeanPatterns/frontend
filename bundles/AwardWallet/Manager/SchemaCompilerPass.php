<?php

namespace AwardWallet\Manager;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\ExpressionLanguage\Expression;

class SchemaCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        foreach ($container->findTaggedServiceIds('aw.manager.schema') as $id => $tags) {
            $schema = $container->getDefinition($id);
            $schema
                ->clearTag('aw.manager.schema')
                ->addTag('aw.manager.schema', ['schema' => \TBaseSchema::getSchemaName($id)])
            ;
        }

        foreach ($container->findTaggedServiceIds('aw.manager.list') as $id => $tags) {
            $list = $container->getDefinition($id);
            $schemaClass = preg_replace('#List$#', '', $id);

            if (!class_exists($schemaClass)) {
                $schemaClass .= 'Schema';
            }

            if (!class_exists($schemaClass)) {
                throw new \Exception("Could not find schema $schemaClass for list $id");
            }

            $schemaName = $schemaClass::getSchemaName($schemaClass);
            $list->setArgument('$table', $schemaName);
            $list->setArgument('$fields', new Expression("service('" . addslashes(SchemaFactory::class) . "').getSchema('{$schemaName}').GetListFields()"));
            $list->addTag('aw.manager.list', ['schema' => $schemaName]);
        }
    }
}
