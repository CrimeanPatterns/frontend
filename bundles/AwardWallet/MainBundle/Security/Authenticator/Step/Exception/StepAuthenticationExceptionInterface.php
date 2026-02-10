<?php

namespace AwardWallet\MainBundle\Security\Authenticator\Step\Exception;

use AwardWallet\MainBundle\Security\Authenticator\Step\StepInterface;

interface StepAuthenticationExceptionInterface
{
    public function getStep(): StepInterface;

    public function getData();
}
