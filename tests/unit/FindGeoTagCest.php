<?php

namespace AwardWallet\Tests\Unit;

use AwardWallet\Common\Geo\TimezoneDb\Client;
use AwardWallet\Common\Geo\TimezoneDb\Response;

/**
 * @group frontend-unit
 */
class FindGeoTagCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function _before(\CodeGuy $I)
    {
        $I->mockService(Client::class, $I->stubMake(Client::class, [
            'getTimezone' => function () {return new Response('UTC', 0); },
        ]));
    }

    public function testTimezoneLookup(\CodeGuy $I)
    {
        $I->executeQuery("delete from GeoTag where Address = 'ITO'");
        FindGeoTag('ITO');
        $I->assertEquals('Pacific/Honolulu', $I->grabFromDatabase("GeoTag", "TimeZoneLocation", ["Address" => "ITO"]));
    }

    public function testEmpty(\CodeGuy $I)
    {
        $I->assertNotEmpty(FindGeoTag(''));
    }

    public function testAirportByCityCode(\CodeGuy $I)
    {
        $I->executeQuery("delete from GeoTag where Address = 'NYC'");
        $tag = FindGeoTag('NYC');
        $I->assertEquals('New York', $tag['City']);
        $I->assertStringContainsString('New York LaGuardia Airport, New York, US', $tag['FoundAddress']);
    }
}
