<?php

namespace AwardWallet\MainBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

/**
 * This pass will find all non-autowired services that have required
 * arguments in their class constructor and these arguments are not manually
 * wired or bound by name or type.
 */
class CatchNonAutowiredServicesCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $containerBuilder)
    {
        $definitions = $containerBuilder->getDefinitions();

        foreach ($definitions as $id => $definition) {
            if (
                $definition->isAutowired()
                || (false !== \strpos($id, '.service_locator.'))
            ) {
                continue;
            }

            $class = $definition->getClass();

            // check AwardWallet namespace only
            if (false === \strpos($class, 'AwardWallet')) {
                continue;
            }

            if (!\class_exists($class)) {
                continue;
            }

            $reflectionClass = new \ReflectionClass($class);
            $constructor = $reflectionClass->getConstructor();

            if (!$constructor) {
                continue;
            }

            $requiredArgumentsCount =
                it($constructor->getParameters())
                ->filter(fn (\ReflectionParameter $parameter) => !$parameter->isOptional())
                ->count();
            $definitionArgumentsCount = \count($definition->getArguments());

            if ($requiredArgumentsCount > $definitionArgumentsCount) {
                throw new \LogicException(\sprintf('Service "%s" has %d required arguments in its constructor, but only %d argument(s) provided. Consider to make this service autowired or manually wire all required arguments', $id, $requiredArgumentsCount, $definitionArgumentsCount));
            }
        }
    }
}
