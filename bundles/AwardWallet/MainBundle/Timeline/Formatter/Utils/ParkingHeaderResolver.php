<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Utils;

use AwardWallet\Common\Entity\Geotag;
use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Parking;
use AwardWallet\MainBundle\Globals\Geo;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

class ParkingHeaderResolver
{
    private const SEARCH_RADIUS = 5;

    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getLocation(Itinerary $itinerary): ?string
    {
        /** @var Parking $itinerary */
        if ($itinerary->getParkingCompanyName() !== null) {
            $location = $itinerary->getParkingCompanyName();
        } elseif ($itinerary->getGeoTagID() !== null && $airport = $this->getAirport($itinerary->getGeoTagID())) {
            $location = $airport['AirCode'];
        } else {
            $location = $itinerary->getLocation();
        }

        return $location;
    }

    /**
     * Get the nearest airport to the parking lot.
     *
     * @param Geotag $geotag geographic coordinates of the parking lot
     * @throws Exception
     */
    private function getAirport(Geotag $geotag): ?array
    {
        $condition = Geo::getSquareGeofenceSQLCondition(
            $geotag->getLat(),
            $geotag->getLng(),
            'Lat',
            'Lng',
            false,
            self::SEARCH_RADIUS
        );
        $airCode = $this->connection->fetchAssociative("
            SELECT *
            FROM `AirCode`
            WHERE {$condition}
            ORDER BY `Popularity` DESC LIMIT 1;
        ");

        return isset($airCode['AirCodeID']) ? $airCode : null;
    }
}
