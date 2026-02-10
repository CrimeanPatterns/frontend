<?php

namespace AwardWallet\Tests\Unit\Common\Geo;

use AwardWallet\Common\Geo\GoogleGeo;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\Tests\Unit\BaseContainerTest;

/**
 * @group frontend-unit
 */
class GoogleGeoTest extends BaseContainerTest
{
    /**
     * @var GoogleGeo
     */
    private $geo;

    public function _before()
    {
        parent::_before();

        $this->geo = $this->container->get('aw.geo.google_geo');
    }

    public function _after()
    {
        $this->geo = null;

        parent::_after();
    }

    public function testMissing()
    {
        $result = $this->geo->FindGeoTag("Someaddress " . StringUtils::getRandomCode(20));
        $this->assertEmpty($result["Lat"]);
    }

    public function testFound()
    {
        $result = $this->geo->FindGeoTag("JFK");
        $this->assertNotEmpty($result["Lat"]);
    }
}
