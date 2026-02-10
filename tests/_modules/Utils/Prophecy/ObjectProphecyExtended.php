<?php

namespace AwardWallet\Tests\Modules\Utils\Prophecy;

use AwardWallet\Tests\Modules\Utils\Prophecy\Prediction\AnyTimesCallPrediction;
use Prophecy\Argument;
use Prophecy\Argument\ArgumentsWildcard;
use Prophecy\Prediction\PredictionInterface;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophecy\ObjectProphecy;
use Prophecy\Prophecy\ObjectProphecy as VendorObjectProphecy;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class ObjectProphecyExtended extends VendorObjectProphecy
{
    /**
     * @var string|null
     */
    private $originClass;
    /**
     * @var VendorObjectProphecy
     */
    private $vendorObjectProphecy;

    public function __construct(?string $originClass, ObjectProphecy $vendorObjectProphecy)
    {
        $this->vendorObjectProphecy = $vendorObjectProphecy;
        $this->originClass = $originClass;
    }

    public function __call($methodName, $arguments)
    {
        return $this->vendorObjectProphecy->__call($methodName, $arguments);
    }

    public function __get($name)
    {
        return $this->vendorObjectProphecy->__get($name);
    }

    public function __set($name, $value)
    {
        $this->vendorObjectProphecy->__set($name, $value);
    }

    public function willExtend($class)
    {
        return $this->vendorObjectProphecy->willExtend($class);
    }

    public function willImplement($interface)
    {
        return $this->vendorObjectProphecy->willImplement($interface);
    }

    public function willBeConstructedWith(?array $arguments = null)
    {
        return $this->vendorObjectProphecy->willBeConstructedWith($arguments);
    }

    public function reveal()
    {
        return $this->vendorObjectProphecy->reveal();
    }

    public function addMethodProphecy(MethodProphecy $methodProphecy)
    {
        $this->vendorObjectProphecy->addMethodProphecy($methodProphecy);
    }

    public function getMethodProphecies($methodName = null)
    {
        return $this->vendorObjectProphecy->getMethodProphecies($methodName);
    }

    public function makeProphecyMethodCall($methodName, array $arguments)
    {
        return $this->vendorObjectProphecy->makeProphecyMethodCall($methodName, $arguments);
    }

    public function findProphecyMethodCalls($methodName, ArgumentsWildcard $wildcard)
    {
        return $this->vendorObjectProphecy->findProphecyMethodCalls($methodName, $wildcard);
    }

    public function checkProphecyMethodsPredictions()
    {
        parent::checkProphecyMethodsPredictions();
    }

    public function prophesizeRemainingMethods(?PredictionInterface $prediction = null): self
    {
        $prophesizedMethodsMap =
            it($this->vendorObjectProphecy->getMethodProphecies())
            ->keys()
            ->mapToLower()
            ->flip()
            ->toArrayWithKeys();
        $prophesizedMethodsMap['__construct'] = 0;
        $prediction = $prediction ?: new AnyTimesCallPrediction();

        /** @var \ReflectionMethod $reflectionMethod */
        foreach (
            it(
                (new \ReflectionClass($this->originClass))
                ->getMethods(\ReflectionMethod::IS_PUBLIC)
            )
            ->filter(function (\ReflectionMethod $method) use ($prophesizedMethodsMap) {
                return !isset($prophesizedMethodsMap[\strtolower($method->getName())]);
            }) as $reflectionMethod
        ) {
            $this->vendorObjectProphecy
                ->__call($reflectionMethod->getName(), [Argument::cetera()])
                ->should($prediction);
        }

        return $this;
    }
}
