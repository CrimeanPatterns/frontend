<?php

namespace AwardWallet\MainBundle\Service\AutologinV3Handler\Result;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Entity\Usr;

/**
 * @NoDI()
 */
class ImpersonatedError extends AccessDenied
{
    private Usr $impersonatedAs;

    public function __construct(
        Usr $impersonatedAs
    ) {
        $this->impersonatedAs = $impersonatedAs;
    }

    public function getImpersonatedAs(): Usr
    {
        return $this->impersonatedAs;
    }
}
