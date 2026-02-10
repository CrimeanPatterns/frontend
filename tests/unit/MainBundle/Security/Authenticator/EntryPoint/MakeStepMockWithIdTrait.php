<?php

namespace AwardWallet\Tests\Unit\MainBundle\Security\Authenticator\EntryPoint;

use AwardWallet\MainBundle\Security\Authenticator\Step\StepInterface;
use Prophecy\Prediction\NoCallsPrediction;

trait MakeStepMockWithIdTrait
{
    protected function makeStepMockWithId(string $id): StepInterface
    {
        $step = $this->prophesizeExtended(StepInterface::class);
        $step
            ->getId()
            ->willReturn($id);

        $step->prophesizeRemainingMethods(new NoCallsPrediction());

        return $step->reveal();
    }
}
