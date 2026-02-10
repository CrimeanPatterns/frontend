<?php

namespace AwardWallet\MainBundle\Service\Overlay;

use Doctrine\DBAL\Connection;

class SegmentsFinder
{
    /**
     * @var \Doctrine\DBAL\Driver\Statement
     */
    private $findSegmentsQuery;

    public function __construct(Connection $connection)
    {
        $this->findSegmentsQuery = $connection->prepare("
        select
            ts.TripSegmentID,
            ts.TripID,
            t.UserID,
            ts.Hidden,
            t.Hidden as TripHidden
        from
            TripSegment ts
            join Trip t on ts.TripID = t.TripID
            join Provider p on t.ProviderID = p.ProviderID
        where
            (p.IATACode = :iataCode or ts.AirlineName = (select Name from Airline where Code = :iataCode limit 1))
            /* TODO: remove this after cleaning up FlightNumber on loyalty side */
            and (
                ts.FlightNumber = :flightNumber 
                or ts.FlightNumber = lpad(:flightNumber, 4, '0') 
                or ts.FlightNumber = lpad(:flightNumber, 3, '0') 
                or ts.FlightNumber = concat(:iataCode, :flightNumber) 
                or ts.FlightNumber = concat(:iataCode, ' ', :flightNumber) 
                or ts.FlightNumber = concat(:iataCode, lpad(:flightNumber, 4, '0'))
                or ts.FlightNumber = concat(:iataCode, lpad(:flightNumber, 3, '0'))
            )
            and ts.DepCode = :depCode
            and ts.ScheduledDepDate = :depDate
        ");
    }

    public function find(string $airlineIataCode, string $flightNumber, string $depCode, int $depDate): array
    {
        $this->findSegmentsQuery->execute(["iataCode" => $airlineIataCode, "flightNumber" => $flightNumber, "depCode" => $depCode, "depDate" => date("Y-m-d H:i:s", $depDate)]);

        return $this->findSegmentsQuery->fetchAll(\PDO::FETCH_ASSOC);
    }
}
