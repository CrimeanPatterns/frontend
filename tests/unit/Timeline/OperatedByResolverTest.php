<?php

namespace AwardWallet\Tests\Unit\Timeline;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Repositories\ProviderRepository;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Service\OperatedByResolver;
use AwardWallet\MainBundle\Service\ProviderNameResolver;
use AwardWallet\Tests\Unit\BaseUserTest;
use Codeception\Module\Aw;
use Doctrine\ORM\EntityRepository;

class OperatedByResolverTest extends BaseUserTest
{
    /**
     * @var OperatedByResolver
     */
    protected $operatedByResolver;

    /**
     * @var ProviderNameResolver
     */
    protected $providerNameResolver;

    /** @var ProviderRepository */
    protected $providerRepository;

    /**
     * @var EntityRepository
     */
    protected $tripSegmentRepository;

    public function _before()
    {
        parent::_before();
        $this->operatedByResolver = $this->container->get(OperatedByResolver::class);
        $this->providerNameResolver = $this->container->get(ProviderNameResolver::class);
        $this->providerRepository = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Provider::class);
        $this->tripSegmentRepository = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Tripsegment::class);
    }

    public function _after()
    {
        parent::_after();
        $this->operatedByResolver = null;
        $this->providerNameResolver = null;
        $this->providerRepository = null;
        $this->tripSegmentRepository = null;
    }

    public function testResolveByFlightnumber()
    {
        $tripId = $this->db->haveInDatabase("Trip", [
            "UserID" => $this->user->getUserid(),
            "RecordLocator" => 'FLIGHT1',
            'ProviderID' => $this->providerRepository->find(1)->getProviderid(), // American Airlines
        ]);

        $time = time();
        $tripSegmentId = $this->aw->createTripSegment([
            "TripID" => $tripId,
            "MarketingAirlineConfirmationNumber" => "FLIGHT1",
            "DepCode" => Aw::GMT_AIRPORT,
            "DepDate" => date("Y-m-d H:i:s", $time),
            "ArrCode" => Aw::GMT_AIRPORT,
            "ArrDate" => date("Y-m-d H:i:s", $time + 12 * SECONDS_PER_HOUR),
            "AirlineName" => 'alaska', // Alaska Airline
            'FlightNumber' => 'AS 1234', // AS == Alaska Airline
        ]);

        /** @var Tripsegment $tripSegment */
        $tripSegment = $this->tripSegmentRepository->find($tripSegmentId);
        $this->assertInstanceOf(Tripsegment::class, $tripSegment);

        $provider = $this->operatedByResolver->resolveAirProvider($tripSegment);
        $this->assertInstanceOf(Provider::class, $provider);

        $this->assertEquals($provider->getCode(), 'alaskaair');
    }

    public function testResolveByAirlineName()
    {
        $tripId = $this->db->haveInDatabase("Trip", [
            "UserID" => $this->user->getUserid(),
            "RecordLocator" => 'FLIGHT1',
            'ProviderID' => $this->providerRepository->find(1)->getProviderid(), // American Airlines
        ]);

        $time = time();
        $tripSegmentId = $this->aw->createTripSegment([
            "TripID" => $tripId,
            "MarketingAirlineConfirmationNumber" => "FLIGHT1",
            "DepCode" => Aw::GMT_AIRPORT,
            "DepDate" => date("Y-m-d H:i:s", $time),
            "ArrCode" => Aw::GMT_AIRPORT,
            "ArrDate" => date("Y-m-d H:i:s", $time + 12 * SECONDS_PER_HOUR),
            "AirlineName" => 'alaska', // Alaska Airline
            'FlightNumber' => '1234',
        ]);

        /** @var Tripsegment $tripSegment */
        $tripSegment = $this->tripSegmentRepository->find($tripSegmentId);
        $this->assertInstanceOf(Tripsegment::class, $tripSegment);

        $provider = $this->operatedByResolver->resolveAirProvider($tripSegment);
        $this->assertInstanceOf(Provider::class, $provider);

        $this->assertEquals($provider->getCode(), 'alaskaair');
    }

    public function testResolveByAirlineNameLOT()
    {
        $tripId = $this->db->haveInDatabase("Trip", [
            "UserID" => $this->user->getUserid(),
            "RecordLocator" => 'FLIGHT1',
            'ProviderID' => $this->providerRepository->find(1)->getProviderid(), // American Airlines
        ]);

        $time = time();
        $tripSegmentId = $this->aw->createTripSegment([
            "TripID" => $tripId,
            "MarketingAirlineConfirmationNumber" => "FLIGHT1",
            "DepCode" => Aw::GMT_AIRPORT,
            "DepDate" => date("Y-m-d H:i:s", $time),
            "ArrCode" => Aw::GMT_AIRPORT,
            "ArrDate" => date("Y-m-d H:i:s", $time + 12 * SECONDS_PER_HOUR),
            "AirlineName" => 'LOT', // LOT
            'FlightNumber' => '1234',
        ]);

        /** @var Tripsegment $tripSegment */
        $tripSegment = $this->tripSegmentRepository->find($tripSegmentId);
        $this->assertInstanceOf(Tripsegment::class, $tripSegment);

        $provider = $this->operatedByResolver->resolveAirProvider($tripSegment);
        $this->assertInstanceOf(Provider::class, $provider);

        $this->assertEquals($provider->getCode(), 'lotpair');
    }

    public function testResolveByAirlineNameExpediaToAA()
    {
        $tripId = $this->db->haveInDatabase("Trip", [
            "UserID" => $this->user->getUserid(),
            "RecordLocator" => 'FLIGHT1',
            'ProviderID' => $this->providerRepository->find(161)->getProviderid(), // Expedia
        ]);

        $time = time();
        $tripSegmentId = $this->aw->createTripSegment([
            "TripID" => $tripId,
            "MarketingAirlineConfirmationNumber" => "FLIGHT1",
            "DepCode" => Aw::GMT_AIRPORT,
            "DepDate" => date("Y-m-d H:i:s", $time),
            "ArrCode" => Aw::GMT_AIRPORT,
            "ArrDate" => date("Y-m-d H:i:s", $time + 12 * SECONDS_PER_HOUR),
            "AirlineName" => 'American Airlines',
            'FlightNumber' => '8400',
        ]);

        /** @var Tripsegment $tripSegment */
        $tripSegment = $this->tripSegmentRepository->find($tripSegmentId);
        $this->assertInstanceOf(Tripsegment::class, $tripSegment);

        $provider = $this->operatedByResolver->resolveAirProvider($tripSegment);
        $this->assertInstanceOf(Provider::class, $provider);

        $this->assertEquals($provider->getCode(), 'aa');
    }

    public function testResolveByAirlineNameExpediaToJA()
    {
        $tripId = $this->db->haveInDatabase("Trip", [
            "UserID" => $this->user->getUserid(),
            "RecordLocator" => 'FLIGHT1',
            'ProviderID' => $this->providerRepository->find(161)->getProviderid(), // Expedia
        ]);

        $time = time();
        $tripSegmentId = $this->aw->createTripSegment([
            "TripID" => $tripId,
            "MarketingAirlineConfirmationNumber" => "FLIGHT1",
            "DepCode" => Aw::GMT_AIRPORT,
            "DepDate" => date("Y-m-d H:i:s", $time),
            "ArrCode" => Aw::GMT_AIRPORT,
            "ArrDate" => date("Y-m-d H:i:s", $time + 12 * SECONDS_PER_HOUR),
            "AirlineName" => 'Japan Airlines',
            'FlightNumber' => '8400',
        ]);

        /** @var Tripsegment $tripSegment */
        $tripSegment = $this->tripSegmentRepository->find($tripSegmentId);
        $this->assertInstanceOf(Tripsegment::class, $tripSegment);

        $provider = $this->operatedByResolver->resolveAirProvider($tripSegment);
        $this->assertInstanceOf(Provider::class, $provider);

        $this->assertEquals($provider->getCode(), 'japanair');
    }

    public function testResolveByAirlineNameExpediaToGI()
    {
        $tripId = $this->db->haveInDatabase("Trip", [
            "UserID" => $this->user->getUserid(),
            "RecordLocator" => 'FLIGHT1',
            'ProviderID' => $this->providerRepository->find(161)->getProviderid(), // Expedia
        ]);

        $time = time();
        $tripSegmentId = $this->aw->createTripSegment([
            "TripID" => $tripId,
            "MarketingAirlineConfirmationNumber" => "FLIGHT1",
            "DepCode" => Aw::GMT_AIRPORT,
            "DepDate" => date("Y-m-d H:i:s", $time),
            "ArrCode" => Aw::GMT_AIRPORT,
            "ArrDate" => date("Y-m-d H:i:s", $time + 12 * SECONDS_PER_HOUR),
            "AirlineName" => 'Garuda Indonesia',
            'FlightNumber' => '8400',
        ]);

        /** @var Tripsegment $tripSegment */
        $tripSegment = $this->tripSegmentRepository->find($tripSegmentId);
        $this->assertInstanceOf(Tripsegment::class, $tripSegment);

        $provider = $this->operatedByResolver->resolveAirProvider($tripSegment);
        $this->assertInstanceOf(Provider::class, $provider);

        $this->assertEquals($provider->getCode(), 'garuda');
    }

    public function testResolveByProvider()
    {
        $tripId = $this->db->haveInDatabase("Trip", [
            "UserID" => $this->user->getUserid(),
            "RecordLocator" => 'FLIGHT1',
            'ProviderID' => $this->providerRepository->find(1)->getProviderid(), // American Airlines
        ]);

        $time = time();
        $tripSegmentId = $this->aw->createTripSegment([
            "TripID" => $tripId,
            "MarketingAirlineConfirmationNumber" => "FLIGHT1",
            "DepCode" => Aw::GMT_AIRPORT,
            "DepDate" => date("Y-m-d H:i:s", $time),
            "ArrCode" => Aw::GMT_AIRPORT,
            "ArrDate" => date("Y-m-d H:i:s", $time + 12 * SECONDS_PER_HOUR),
            'FlightNumber' => '1234',
        ]);

        /** @var Tripsegment $tripSegment */
        $tripSegment = $this->tripSegmentRepository->find($tripSegmentId);
        $this->assertInstanceOf(Tripsegment::class, $tripSegment);

        $provider = $this->operatedByResolver->resolveAirProvider($tripSegment);
        $this->assertInstanceOf(Provider::class, $provider);

        $this->assertEquals($provider->getCode(), 'aa');
    }
}
