<?php

namespace AwardWallet\Tests\Modules\Utils\Prophecy;

use Prophecy\Prediction\NoCallsPrediction;
use Prophecy\Prophet;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class ProphetExtended extends Prophet
{
    /**
     * Creates new object prophecy.
     *
     * @param string|null $classOrInterface Class or interface name
     * @return ObjectProphecyExtended
     */
    public function prophesize($classOrInterface = null)
    {
        $vendorObjectProphecy = parent::prophesize($classOrInterface);

        return new ObjectProphecyExtended($classOrInterface, $vendorObjectProphecy);
    }

    /**
     * @throws \ReflectionException
     */
    public function prophesizeConstructorArguments($class, array $argsMixinMap = []): array
    {
        return $this->doProphesizeConstructorArguments(
            $class,
            function ($class) {
                return $this->prophesize($class)->reveal();
            },
            $argsMixinMap
        );
    }

    /**
     * @throws \ReflectionException
     */
    public function prophesizeConstructorArgumentsMuted($class, array $argsMixinMap = []): array
    {
        return $this->doProphesizeConstructorArguments(
            $class,
            function ($class) {
                $prophecy = $this->prophesize($class);
                $prophecy->prophesizeRemainingMethods(new NoCallsPrediction());

                return $prophecy->reveal();
            },
            $argsMixinMap
        );
    }

    protected function doProphesizeConstructorArguments($class, callable $prophecyProvider, array $argsMixinMap = []): array
    {
        $argsEvaluations =
            it((new \ReflectionClass($class))->getConstructor()->getParameters())
            ->map(function (\ReflectionParameter $refl) use (&$argsMixinMap, $prophecyProvider) {
                $paramName = '$' . $refl->getName();

                if (isset($argsMixinMap[$paramName])) {
                    $bareValue = $argsMixinMap[$paramName];
                    unset($argsMixinMap[$paramName]);

                    return function () use ($bareValue) {
                        return $bareValue;
                    };
                }

                if (
                    $refl->isOptional()
                    || $refl->getType()->isBuiltin()
                    || $refl->allowsNull()
                ) {
                    throw new \LogicException('Constructor parameter should be class or interface and should not be optional');
                }

                $class = $refl->getClass()->getName();

                $evaluator = function () use ($class, $prophecyProvider, $argsMixinMap) {
                    return $argsMixinMap[$class] ??
                        $prophecyProvider($class);
                };

                unset($argsMixinMap[$class]);

                return $evaluator;
            })
            ->toArray();

        if ($argsMixinMap) {
            throw new \LogicException('Unknown parameters: ' . \json_encode(\array_keys($argsMixinMap)));
        }

        return
            it($argsEvaluations)
            ->mapByApply()
            ->toArray();
    }
}
