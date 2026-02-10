<?php

namespace AwardWallet\MainBundle\DependencyInjection;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class NoDICompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $builder)
    {
        $annotationReader = new AnnotationReader();
        $annotationRegistry = new AnnotationRegistry();
        $annotationRegistry->registerUniqueLoader('class_exists');
        $annotationReader->addGlobalIgnoredName('required', $annotationRegistry);

        /** @var Definition $definition */
        foreach ($builder->getDefinitions() as $id => $definition) {
            $class = $definition->getClass();

            if (null === $class) {
                continue;
            }

            /*
             * Class autoload disabled here because:classes of services marked with @NoDI annotation
             * are already class-loaded by autoload functionality. So no need to load other classes.
             */
            if (!\class_exists($class, false)) {
                continue;
            }

            $parsed = $annotationReader->getClassAnnotations(new \ReflectionClass($class));

            foreach ($parsed as $annotation) {
                if ($annotation instanceof NoDI) {
                    $builder->removeDefinition($id);

                    break;
                }
            }
        }
    }
}
