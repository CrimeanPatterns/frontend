<?php

namespace AwardWallet\Tests\Unit\AwardWallet\MainBundle\Service\AirportTerminalMatcher;

use AwardWallet\MainBundle\Service\AirportTerminalMatcher\FlightStatsWriter;
use AwardWallet\MainBundle\Service\FlightStats\TripAlerts\API\Model\Alert;
use AwardWallet\MainBundle\Service\FlightStats\TripAlerts\API\Model\AlertTrip;
use AwardWallet\MainBundle\Service\FlightStats\TripAlerts\API\Model\FlightStatus;
use AwardWallet\MainBundle\Service\FlightStats\TripAlerts\API\Model\FlightStatusDetail;
use AwardWallet\MainBundle\Service\FlightStats\TripAlerts\API\Model\FlightWithStatus;
use AwardWallet\MainBundle\Service\FlightStats\TripAlerts\API\Model\Leg;
use AwardWallet\Tests\Unit\BaseContainerTest;

/**
 * @group frontend-unit
 */
class FlightStatsWriterTest extends BaseContainerTest
{
    private ?FlightStatsWriter $flightStatsWriter;

    public function _before()
    {
        parent::_before();

        $this->flightStatsWriter = $this->container->get(FlightStatsWriter::class);
    }

    public function _after()
    {
        $this->flightStatsWriter = null;

        parent::_after();
    }

    public function test()
    {
        $this->db->executeQuery("DELETE FROM FlightStats WHERE DepCode = 'ABC' OR ArrCode = 'ABC'");
        $alert = $this->getAlert([
            $this->createFlight(
                '123',
                'ABC',
                $depDate1 = new \DateTime('+1 DAY'),
                null,
                'CBA',
                $arrDate = (clone $depDate1)->modify('+3 HOUR'),
                '3',
                'DL',
                'WN',
                'WN'
            ),
            $this->createFlight(
                '567',
                'CBA',
                $depDate2 = new \DateTime('+7 DAY'),
                'B',
                'ABC',
                $arrDate = (clone $depDate2)->modify('+3 HOUR'),
                null,
                'WN',
            ),
        ]);
        $this->flightStatsWriter->write($alert);
        $this->db->seeInDatabase('FlightStats', [
            'DepCode' => 'ABC',
            'ArrCode' => 'CBA',
            'DepDate' => $depDate1->format('Y-m-d H:i:s'),
            'FlightNumber' => '123',
            'FlightNumber2' => '123',
            'DepTerminal' => null,
            'ArrTerminal' => '3',
            'BookedAirline' => 'WN',
            'OperatingAirline' => 'WN',
            'PrimaryMarketingAirline' => 'DL',
        ]);
        $this->db->seeInDatabase('FlightStats', [
            'DepCode' => 'CBA',
            'ArrCode' => 'ABC',
            'DepDate' => $depDate2->format('Y-m-d H:i:s'),
            'FlightNumber' => '567',
            'FlightNumber2' => '567',
            'DepTerminal' => 'B',
            'ArrTerminal' => null,
            'BookedAirline' => 'WN',
            'OperatingAirline' => 'WN',
            'PrimaryMarketingAirline' => 'WN',
        ]);
        $this->flightStatsWriter->write($alert);

        $this->assertEquals(
            2,
            $this->db->query("SELECT COUNT(*) FROM FlightStats WHERE DepCode = 'ABC' OR ArrCode = 'ABC'")->fetchColumn()
        );
    }

    /**
     * @param FlightWithStatus[] $flights
     */
    private function getAlert(array $flights): Alert
    {
        $leg = new Leg();
        $leg->setFlights($flights);

        $trip = new AlertTrip();
        $trip->setLegs([$leg]);

        $alert = new Alert();
        $alert->setTrip($trip);

        return $alert;
    }

    private function createFlight(
        string $flightNumber,
        string $depCode,
        \DateTime $depDate,
        ?string $depTerminal,
        string $arrCode,
        \DateTime $arrDate,
        ?string $arrTerminal,
        string $primaryMarketingAirline,
        ?string $operatingAirline = null,
        ?string $bookedAirline = null
    ): FlightWithStatus {
        $flight = new FlightWithStatus();
        $flight->setFlightNumber($flightNumber);
        $flight->setBookedAirlineCode($bookedAirline ?? $primaryMarketingAirline);
        $status = new FlightStatus();
        $status
            ->setFlightNumber($flightNumber)
            ->setPrimaryMarketingAirlineCode($primaryMarketingAirline)
            ->setOperatingAirlineCode($operatingAirline ?? $primaryMarketingAirline)
            ->setDeparture(
                (new FlightStatusDetail())
                    ->setAirportCode($depCode)
                    ->setScheduledGateDateTime($depDate->format('Y-m-d\TH:i:s'))
                    ->setTerminal($depTerminal)
            )
            ->setArrival(
                (new FlightStatusDetail())
                    ->setAirportCode($arrCode)
                    ->setScheduledGateDateTime($arrDate->format('Y-m-d\TH:i:s'))
                    ->setTerminal($arrTerminal)
            );

        $flight->setFlightStatuses([$status]);

        return $flight;
    }
}
