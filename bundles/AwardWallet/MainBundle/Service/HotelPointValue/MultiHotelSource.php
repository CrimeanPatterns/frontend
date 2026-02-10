<?php

namespace AwardWallet\MainBundle\Service\HotelPointValue;

use AwardWallet\MainBundle\Globals\Geo;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class MultiHotelSource implements HotelSourceInterface
{
    private Connection $connection;

    private LoggerInterface $logger;

    public function __construct(Connection $connection, LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->logger = $logger;
    }

    public function searchByLatLng(float $lat, float $lng): array
    {
        $hotels = $this->connection->fetchAllAssociative("select * from TpoHotel where " . Geo::getSquareGeofenceSQLCondition(
            $lat,
            $lng,
            "latitude",
            "longitude",
            false,
            2
        ));

        $this->logger->debug("found " . count($hotels) . " hotels at {$lat},$lng");

        return it($hotels)
            ->map(fn (array $hotel) => new HotelFinderResult($hotel["id"], $hotel["name"], $hotel["latitude"], $hotel["longitude"]))
            ->toArray()
        ;
    }
}
