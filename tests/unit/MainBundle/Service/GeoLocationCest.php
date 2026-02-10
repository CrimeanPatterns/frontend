<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service;

use AwardWallet\MainBundle\Entity\Country;
use AwardWallet\MainBundle\Service\GeoLocation\GeoLocation;

class GeoLocationCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    /**
     * @var GeoLocation
     */
    private $geo;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->geo = $I->grabService(GeoLocation::class);
    }

    public function test(\TestSymfonyGuy $I)
    {
        $I->assertEquals(Country::UNITED_STATES, $this->geo->getCountryIdByIp("0:0:0:0:0:ffff:9455:881f"));
        $I->assertEquals(Country::UK, $this->geo->getCountryIdByIp("2a03:b0c0:1:d0::611:4001"));
    }
}
