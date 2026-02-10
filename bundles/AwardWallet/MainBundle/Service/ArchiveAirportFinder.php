<?php

namespace AwardWallet\MainBundle\Service;

use AwardWallet\Common\Geo\GeoAirportFinder;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;

class ArchiveAirportFinder
{
    private Connection $archiveConnection;

    private GeoAirportFinder $geoAirportFinder;

    public function __construct(
        Connection $archiveConnection,
        GeoAirportFinder $geoAirportFinder
    ) {
        $this->archiveConnection = $archiveConnection;
        $this->geoAirportFinder = $geoAirportFinder;
    }

    public function findAirCodeByTag(int $tripSegmentId, string $prefix): ?string
    {
        $latLng = $this->archiveConnection->executeQuery("select 
            gt.Lat,
            gt.Lng
        from 
            GeoTag gt
            join TripSegment ts on ts.{$prefix}GeoTagID = gt.GeoTagID
        where
            ts.TripSegmentID = ?", [$tripSegmentId])->fetch(FetchMode::ASSOCIATIVE);

        if ($latLng === false || $latLng['Lat'] === null) {
            return null;
        }

        $airport = $this->geoAirportFinder->getNearestAirport($latLng['Lat'], $latLng['Lng'], 50);

        return $airport ? $airport->getAircode() : null;
    }
}
