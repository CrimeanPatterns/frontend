<?php

namespace AwardWallet\MainBundle\Service\FlightStats\TripAlerts;

use AwardWallet\MainBundle\Service\FlightStats\AirlineConverter;
use AwardWallet\MainBundle\Service\FlightStats\TripAlerts\API\Model\AirportDetail;
use AwardWallet\MainBundle\Service\FlightStats\TripAlerts\API\Model\ImportFlight;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class TripConverter
{
    private AirlineConverter $airlineConverter;

    private LoggerInterface $logger;

    /**
     * @var array
     */
    private $airlineNameToIata = [];

    public function __construct(AirlineConverter $airlineConverter, LoggerInterface $tripAlertsLogger, Connection $connection)
    {
        $this->airlineConverter = $airlineConverter;
        $this->logger = $tripAlertsLogger;
        $this->airlineNameToIata = $connection->executeQuery("select lower(Name), Code from Airline")->fetchAll(\PDO::FETCH_KEY_PAIR);
    }

    /**
     * $segment structure:
     * [
     *      'IATACode' => 'AA',
     *      'FlightNumber' => 'DL1234',
     *      'AirlineName' => 'Virgin America',
     *      'DepCode' => 'JFK',
     *      'ArrCode' => 'LAX',
     *      'DepDate' => '2016-01-01 12:55:00'
     *      'ArrDate' => '2016-01-01 13:55:00'
     * ].
     *
     * @return ImportFlight
     */
    public function convert(array $segment)
    {
        if (!empty($segment['AirlineName']) && isset($this->airlineNameToIata[strtolower($segment['AirlineName'])])) {
            $this->logger->debug("set airline from airlineName", ["Name" => $segment['AirlineName']]);
            $segment['IATACode'] = $this->airlineNameToIata[strtolower($segment['AirlineName'])];
        }

        if (null === ($fsCode = $this->airlineConverter->IataToFSCode($segment['IATACode']))) {
            $this->logger->warning("could not detect airline", ["segment" => $segment]);

            return null;
        }

        if (stripos($segment['FlightNumber'], $segment['IATACode']) === 0) {
            $segment['FlightNumber'] = substr($segment['FlightNumber'], strlen($segment['IATACode']));
        }
        $flightNumber = preg_replace("#[^\d]+#ims", "", $segment['FlightNumber']);

        if (empty($flightNumber)) {
            $this->logger->warning("invalid flightNumber", ["segment" => $segment]);

            return null;
        }
        $flight = new ImportFlight([
            'bookedAirlineCode' => $fsCode,
            'flightNumber' => $flightNumber,
            'departure' => new AirportDetail([
                'airportCode' => $segment['DepCode'],
                'dateTime' => date("Y-m-d\\TH:i", strtotime($segment['ScheduledDepDate'])),
            ]),
            'arrival' => new AirportDetail([
                'airportCode' => $segment['ArrCode'],
                'dateTime' => date("Y-m-d\\TH:i", strtotime($segment['ScheduledArrDate'])),
            ]),
        ]);

        return $flight;
    }
}
