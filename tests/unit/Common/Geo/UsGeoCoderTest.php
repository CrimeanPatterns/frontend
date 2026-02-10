<?php

namespace AwardWallet\Tests\Unit\Common\Geo;

use AwardWallet\Common\Geo\GeoCodeResult;
use AwardWallet\Common\Geo\PositionStack\SolverClient;
use AwardWallet\Common\Geo\UsGeoCoder;
use Codeception\Test\Unit;
use Psr\Log\NullLogger;

/**
 * @group frontend-unit
 */
class UsGeoCoderTest extends Unit
{
    public function testIsUs()
    {
        foreach ([
            'Some Address, USA',
            'Another Address, US',
            'And another one, United States of America',
            'Here\'s some more, Ohio',
            'And the last one, PA',
        ] as $query) {
            $this->assertEquals(1, count($this->getGeocoder(true)->geoCode($query)));
        }
    }

    public function testNonUs()
    {
        $this->getGeocoder(false)->geoCode('Address in Moscow, Russia');
    }

    public function testShort()
    {
        $this->getGeocoder(false)->geoCode('CA');
    }

    private function getGeocoder(bool $called): UsGeoCoder
    {
        $client = $this->getMockBuilder(SolverClient::class)
            ->disableOriginalConstructor()
            ->getMock();

        if ($called) {
            $client->expects($this->once())->method('geoCode')->willReturn([new GeoCodeResult(1, 1)]);
        } else {
            $client->expects($this->never())->method('geoCode');
        }

        return new UsGeoCoder($client, new NullLogger(), $this->getModule('Symfony')->grabService('database_connection'));
    }
}
