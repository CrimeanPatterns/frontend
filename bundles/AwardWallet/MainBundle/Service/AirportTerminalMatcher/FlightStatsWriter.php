<?php

namespace AwardWallet\MainBundle\Service\AirportTerminalMatcher;

use AwardWallet\MainBundle\Service\FlightStats\TripAlerts\API\Model\Alert;
use AwardWallet\MainBundle\Service\FlightStats\TripAlerts\API\Model\FlightStatus;
use AwardWallet\MainBundle\Service\FlightStats\TripAlerts\API\Model\FlightWithStatus;
use Doctrine\DBAL\Connection;

class FlightStatsWriter
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * write FlightStats data to database.
     */
    public function write(Alert $alert): void
    {
        $trip = $alert->getTrip();

        if (!$trip->getLegs()) {
            return;
        }

        foreach ($trip->getLegs() as $leg) {
            foreach ($leg->getFlights() as $flight) {
                $this->processFlight($flight);
            }
        }
    }

    private function processFlight(FlightWithStatus $flight): void
    {
        $statuses = $flight->getFlightStatuses();

        if (empty($statuses) || count($statuses) > 1) {
            return;
        }

        /** @var FlightStatus $status */
        $status = array_shift($statuses);
        $departure = $status->getDeparture();
        $arrival = $status->getArrival();

        if (
            is_null($departure->getAirportCode())
            || is_null($departure->getScheduledGateDateTime())
            || is_null($arrival->getAirportCode())
            || is_null($arrival->getScheduledGateDateTime())
            || is_null($status->getFlightNumber())
            || is_null($flight->getFlightNumber())
            || is_null($flight->getBookedAirlineCode())
            || is_null($status->getOperatingAirlineCode())
            || is_null($status->getPrimaryMarketingAirlineCode())
        ) {
            return;
        }

        $this->connection->executeStatement("
            INSERT IGNORE INTO FlightStats(DepCode, DepDate, ArrCode, ArrDate, FlightNumber, FlightNumber2, DepTerminal, ArrTerminal, BookedAirline, OperatingAirline, PrimaryMarketingAirline)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ", [
            $departure->getAirportCode(),
            (new \DateTime($departure->getScheduledGateDateTime()))->format('Y-m-d H:i:s'),
            $arrival->getAirportCode(),
            (new \DateTime($arrival->getScheduledGateDateTime()))->format('Y-m-d H:i:s'),
            $status->getFlightNumber(),
            $flight->getFlightNumber(),
            $departure->getTerminal(),
            $arrival->getTerminal(),
            $flight->getBookedAirlineCode(),
            $status->getOperatingAirlineCode(),
            $status->getPrimaryMarketingAirlineCode(),
        ], [
            \PDO::PARAM_STR,
            \PDO::PARAM_STR,
            \PDO::PARAM_STR,
            \PDO::PARAM_STR,
            \PDO::PARAM_STR,
            \PDO::PARAM_STR,
            $status->getDeparture()->getTerminal() ? \PDO::PARAM_STR : \PDO::PARAM_NULL,
            $status->getArrival()->getTerminal() ? \PDO::PARAM_STR : \PDO::PARAM_NULL,
            \PDO::PARAM_STR,
            \PDO::PARAM_STR,
            \PDO::PARAM_STR,
        ]);
    }
}
