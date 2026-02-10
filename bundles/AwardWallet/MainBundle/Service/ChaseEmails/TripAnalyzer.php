<?php

namespace AwardWallet\MainBundle\Service\ChaseEmails;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Psr\Log\LoggerInterface;

class TripAnalyzer
{
    /**
     * @var \Doctrine\DBAL\Statement
     */
    private $query;

    private LoggerInterface $logger;

    public function __construct(
        Connection $connection,
        LoggerInterface $logger
    ) {
        $this->query = $connection->prepare("
        select
            ts.DepDate,
            ts.ArrDate,  
            case when da.CityCode <> '' then da.CityCode else ts.DepCode end as DepCityCode,
            case when aa.CityCode <> '' then aa.CityCode else ts.ArrCode end as ArrCityCode,
            aa.CityName as ArrCityName,
            da.CityName as DepCityName,
            dgt.TimeZoneLocation as DepLocation,
            agt.TimeZoneLocation as ArrLocation
        from 
            TripSegment ts
            join AirCode da on ts.DepCode = da.AirCode
            join AirCode aa on ts.ArrCode = aa.AirCode
            join GeoTag dgt on ts.DepGeoTagID = dgt.GeoTagID
            join GeoTag agt on ts.ArrGeoTagID = agt.GeoTagID
        where 
            ts.TripID = ?
        order by 
            ts.DepDate            
        ");
        $this->logger = $logger;
    }

    public function getTripDestination(int $tripId): ?string
    {
        $this->query->execute([$tripId]);
        $segments = $this->query->fetchAll(FetchMode::ASSOCIATIVE);

        if (count($segments) === 0) {
            $this->logger->warning("trip segments not found for $tripId");

            return null;
        }
        $result = $this->getDestinationName($segments);
        $this->logger->info("trip destination for TripID {$tripId}, " . count($segments) . " segments: $result");

        return $result;
    }

    private function getDestinationName(array $segments): string
    {
        $lastSegment = null;
        $maxStopTime = 0;
        $isRoundTrip = false;
        $firstFullDayStop = null;
        $destination = $segments[count($segments) - 1]['ArrCityName'];

        foreach ($segments as $segment) {
            $segment = $this->calcGmt($segment);

            if ($lastSegment !== null) {
                $layoverTime = $segment['DepDateGmt'] - $lastSegment['ArrDateGmt'];

                if ($layoverTime > $maxStopTime) {
                    $maxStopTime = $layoverTime;
                    $destination = $lastSegment['ArrCityName'];
                }

                if ($layoverTime > 86400 && $firstFullDayStop === null) {
                    $firstFullDayStop = $lastSegment['ArrCityName'];
                }
            }
            $lastSegment = $segment;

            if ($segment['ArrCityName'] === $segments[0]['DepCityName']) {
                $this->logger->info("roundtrip detected");
                $isRoundTrip = true;

                break;
            }
        }

        if (!$isRoundTrip) {
            if ($firstFullDayStop !== null) {
                $destination = $firstFullDayStop;
            } else {
                $destination = $segments[count($segments) - 1]['ArrCityName'];
            }
        }

        return $destination;
    }

    private function calcGmt(array $segment): array
    {
        $segment['DepDateGmt'] = $this->calcGmtDate($segment['DepDate'], $segment['DepLocation']);
        $segment['ArrDateGmt'] = $this->calcGmtDate($segment['ArrDate'], $segment['ArrLocation']);

        return $segment;
    }

    private function calcGmtDate(string $date, string $location): int
    {
        $dateTime = new \DateTime($date, new \DateTimeZone($location));

        return $dateTime->getTimestamp();
    }
}
