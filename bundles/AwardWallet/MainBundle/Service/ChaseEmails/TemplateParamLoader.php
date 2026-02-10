<?php

namespace AwardWallet\MainBundle\Service\ChaseEmails;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Psr\Log\LoggerInterface;

class TemplateParamLoader
{
    private Connection $connection;

    private LoggerInterface $logger;

    private TripAnalyzer $tripAnalyzer;

    public function __construct(
        Connection $connection,
        LoggerInterface $logger,
        TripAnalyzer $tripAnalyzer
    ) {
        $this->connection = $connection;
        $this->logger = $logger;
        $this->tripAnalyzer = $tripAnalyzer;
    }

    /**
     * supported ids:
     *  - 'R.123.City'
     *  - 'S.123.DepCity'
     *  - 'S.123.ArrCity'.
     *
     * @return - ['date' => <unixtimestamp>, 'location' => 'Houston'],
     */
    public function load(string $id): ?array
    {
        [$type, $rowId, $property] = explode(".", $id);

        switch ($type) {
            case "R":
                $row = $this->loadReservationRow($rowId);

                if ($row === null) {
                    $this->logger->warning("reservation $rowId not found");

                    return null;
                }

                return $this->extractReservationParams($row, $property);

            case "T":
                $row = $this->loadTripRow($rowId);

                if ($row === null) {
                    $this->logger->warning("trip $rowId not found");

                    return null;
                }
                $params = $this->extractSegmentParams($row, $property);

                if ($params === null) {
                    return null;
                }

                return $params;

            default:
                throw new \Exception("Unknown table type: $type");
        }
    }

    private function loadReservationRow($rowId): ?array
    {
        $row = $this->connection->executeQuery("
            select 
                r.ConfirmationNumber,
                r.CheckInDate,
                r.HotelName,
                r.Address,
                gt.CountryCode,
                gt.Country,
                gt.State,
                gt.City
            from
                Reservation r
                join GeoTag gt on r.GeoTagID = gt.GeoTagID
            where 
                r.ReservationID = ?
                and gt.City is not null
        ", [$rowId])->fetch(FetchMode::ASSOCIATIVE);

        if ($row === false) {
            return null;
        }

        $this->logger->info("reservation {$rowId} / {$row['ConfirmationNumber']} at hotel: {$row['HotelName']}, address: {$row['Address']}, city: {$row['City']}, state: {$row['State']}, country code: {$row['CountryCode']}, country: {$row['Country']}");

        return $row;
    }

    private function extractReservationParams(array $row, string $property)
    {
        switch ($property) {
            case "City":
                return [
                    'date' => $this->formatDate($row['CheckInDate']),
                    'location' => $this->lookupReservationCity($row['CountryCode'], $row['Country'], $row['State'], $row['City']),
                ];

            default:
                throw new \Exception("Unknown reservation property: $property");
        }
    }

    private function formatDate(string $dateStr): \DateTime
    {
        return new \DateTime($dateStr);
    }

    private function loadTripRow($rowId): ?array
    {
        $row = $this->connection->executeQuery("
            select 
                min(s.DepDate) as DepDate,
                s.TripID
            from
                TripSegment s
            where 
                s.TripID = ?
            group by    
                s.TripID
        ", [$rowId])->fetch(FetchMode::ASSOCIATIVE);

        if ($row === false) {
            return null;
        }

        return $row;
    }

    private function extractSegmentParams(array $row, string $property)
    {
        switch ($property) {
            case "ArrCity":
                $location = $this->tripAnalyzer->getTripDestination($row['TripID']);

                if ($location === null) {
                    return null;
                }

                return [
                    'date' => $this->formatDate($row['DepDate']),
                    'location' => $location,
                ];

            default:
                throw new \Exception("Unknown segment property: $property");
        }
    }

    private function lookupReservationCity(?string $countryCode, string $country, ?string $state, string $city): string
    {
        if ($countryCode === 'US' || $country === 'United States') {
            return $city . ', ' . $state;
        }

        return $city . ', ' . $country;
    }
}
