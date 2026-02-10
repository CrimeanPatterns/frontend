<?php

namespace AwardWallet\Tests\FunctionalSymfony;

class BaseTraitCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->callTraitMethods('_lazy_', $I);
        $this->callTraitMethods('_before_', $I);
    }

    public function _after(\TestSymfonyGuy $I)
    {
        $this->callTraitMethods('_after_', $I);
    }

    protected function callTraitMethods($prefix, ...$arguments)
    {
        $reflClass = new \ReflectionClass($this);

        foreach ($reflClass->getTraits() as $reflTrait) {
            foreach ($reflTrait->getMethods() as $reflMethod) {
                if ($reflMethod->getName() === ($methodName = $prefix . $reflTrait->getShortName())) {
                    $this->$methodName(...$arguments);
                }
            }
        }
    }
}
