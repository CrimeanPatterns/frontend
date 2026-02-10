<?php

namespace AwardWallet\Tests\FunctionalSymfony\Traits;

use AwardWallet\MainBundle\Globals\Headers\MobileHeaders;

trait LatestMobileApp
{
    protected function _before_LatestMobileApp(\TestSymfonyGuy $I)
    {
        $I->haveHttpHeader(MobileHeaders::MOBILE_VERSION, '3.25.0+b100500');
    }
}
