<?php

namespace AwardWallet\tests\unit\PositionStack;

use AwardWallet\Common\Geo\GeoCodeResult;
use AwardWallet\Common\Geo\PositionStack\SolverClient;
use Codeception\Test\Unit;
use Psr\Log\NullLogger;

/**
 * @group frontend-unit
 */
class PositionStackTest extends Unit
{
    public function testSuccess()
    {
        /** @var GeoCodeResult[] $result */
        $result = $this->getClient('single')->geoCode('6002 Master St, Philadelphia, PA, USA');
        $this->assertEquals('6002 Master St', $result[0]->detailedAddress['AddressLine']);
    }

    public function testDoubleClose()
    {
        /** @var GeoCodeResult[] $result */
        $result = $this->getClient('doubleClose')->geoCode('60 West 37th Street, New York, NY, USA');
        $this->assertEquals('60 West 37th Street', $result[0]->detailedAddress['AddressLine']);
    }

    public function testDouble()
    {
        /** @var GeoCodeResult[] $result */
        $result = $this->getClient('double')->geoCode('3736 Southcenter Boulevard, A, Tukwila, WA 98188, United States');
        $this->assertEquals(0, count($result));
    }

    public function testTriple()
    {
        /** @var GeoCodeResult[] $result */
        $result = $this->getClient('triple')->geoCode('Honolulu, HI, USA');
        $this->assertEquals(0, count($result));
    }

    public function testEmpty()
    {
        /** @var GeoCodeResult[] $result */
        $result = $this->getClient('empty')->geoCode('invalid address');
        $this->assertEquals(0, count($result));
    }

    private function getClient($key): SolverClient
    {
        return new SolverClient($this->getRiggedHttpDriver($key), '', new NullLogger());
    }

    private function getRiggedHttpDriver($key): \HttpDriverInterface
    {
        $responses = json_decode(file_get_contents(__DIR__ . '/../../_data/PositionStack/geoCode.json'), true);
        $response = new \HttpDriverResponse(json_encode($responses[$key]));
        $response->httpCode = 200;
        $httpDriver = $this->makeEmpty(\HttpDriverInterface::class, ['request' => $response]);

        return $httpDriver;
    }
}
