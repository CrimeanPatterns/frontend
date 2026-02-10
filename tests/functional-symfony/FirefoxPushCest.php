<?php

namespace AwardWallet\Tests\FunctionalSymfony;

use AwardWallet\MainBundle\Entity\MobileDevice;

/**
 * @group frontend-functional
 */
class FirefoxPushCest extends ChromePushCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    protected $deviceType = MobileDevice::TYPE_FIREFOX;
}
