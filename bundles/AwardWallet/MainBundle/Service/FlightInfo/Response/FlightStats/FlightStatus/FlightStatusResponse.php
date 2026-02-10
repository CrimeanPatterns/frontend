<?php

namespace AwardWallet\MainBundle\Service\FlightInfo\Response\FlightStats\FlightStatus;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Service\FlightInfo\Response\Common\FlightStatus;
use AwardWallet\MainBundle\Service\FlightInfo\Response\CommonResponse;
use AwardWallet\MainBundle\Service\FlightInfo\Response\FlightInfoResponseInterface;
use AwardWallet\MainBundle\Service\FlightInfo\Response\ResponseInterface;

/**
 * @NoDI()
 */
class FlightStatusResponse extends CommonResponse implements ResponseInterface, FlightInfoResponseInterface
{
    private $data;

    private $airlines;

    private $airports;

    private $equipments;

    public function __construct($data)
    {
        parent::__construct();

        $this->data = $data;
        $this->airlines = [];
        $this->airports = [];

        if (array_key_exists('appendix', $this->data)) {
            $appendix = $this->data['appendix'];

            if (array_key_exists('airlines', $appendix)) {
                $airlines = $appendix['airlines'];
                $airlines = array_combine(
                    array_map(function ($v) {return $v['fs']; }, $airlines),
                    array_map(function ($v) {return $v['iata'] ?? ''; }, $airlines)
                );
                $this->airlines = $airlines;
            }

            if (array_key_exists('airports', $appendix)) {
                $airports = $appendix['airports'];
                $airports = array_combine(
                    array_map(function ($v) {return $v['fs']; }, $airports),
                    array_map(function ($v) {return $v['iata'] ?? ''; }, $airports)
                );
                $this->airports = $airports;
            }

            if (array_key_exists('equipments', $appendix)) {
                $equipments = $appendix['equipments'];
                $equipments = array_combine(
                    array_map(function ($v) {return $v['iata']; }, $equipments),
                    array_map(function ($v) {return $v['name']; }, $equipments)
                );
                $this->equipments = $equipments;
            }
        }
    }

    /**
     * @return FlightStatus[]
     */
    public function getFlightIndex()
    {
        $ret = [];

        if (!is_array($this->data)) {
            return [];
        }

        if (!array_key_exists('flightStatuses', $this->data)) {
            return [];
        }

        $statuses = $this->data['flightStatuses'];

        foreach ($statuses as $status) {
            $flightStatus = new FlightStatus();
            $flightStatus->setDepartureIATACode($this->resolveAirport($status['departureAirportFsCode']));
            $flightStatus->setArrivalIATACode($this->resolveAirport($status['arrivalAirportFsCode']));
            $flightStatus->setInfo($this->extractInfo($status));
            $flightStatus->setCreateDate($this->getCreateDate());

            $s = clone $flightStatus;
            $s->setCarrierIATACode($this->resolveAirline($status['carrierFsCode']));
            $s->setFlightNumber($status['flightNumber']);
            $ret[] = $s;

            if (array_key_exists('codeshares', $status) && is_array($status['codeshares'])) {
                foreach ($status['codeshares'] as $code) {
                    $s = clone $flightStatus;
                    $s->setCarrierIATACode($this->resolveAirline($code['fsCode']));
                    $s->setFlightNumber($code['flightNumber']);
                    $ret[] = $s;
                }
            }
        }

        return $ret;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param array $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    private function resolveAirport($fsCode)
    {
        if (array_key_exists($fsCode, $this->airports) && $this->airports[$fsCode]) {
            return $this->airports[$fsCode];
        }

        return $fsCode;
    }

    private function resolveAirline($fsCode)
    {
        if (array_key_exists($fsCode, $this->airlines) && $this->airlines[$fsCode]) {
            return $this->airlines[$fsCode];
        }

        return $fsCode;
    }

    private function extractInfo($segment)
    {
        $equipments = $this->equipments;

        $data = [];
        $data['DepDate'] = $segment['departureDate']['dateLocal'];
        $data['DepDateUtc'] = $segment['departureDate']['dateUtc'];
        $data['ArrDate'] = $segment['arrivalDate']['dateLocal'];
        $data['ArrDateUtc'] = $segment['arrivalDate']['dateUtc'];

        if (array_key_exists('operationalTimes', $segment)) {
            $opTimes = $segment['operationalTimes'];

            if (array_key_exists('estimatedGateDeparture', $opTimes)) {
                $data['DepDate'] = $opTimes['estimatedGateDeparture']['dateLocal'];
                $data['DepDateUtc'] = $opTimes['estimatedGateDeparture']['dateUtc'];
            }

            if (array_key_exists('estimatedGateArrival', $opTimes)) {
                $data['ArrDate'] = $opTimes['estimatedGateArrival']['dateLocal'];
                $data['ArrDateUtc'] = $opTimes['estimatedGateArrival']['dateUtc'];
            }

            if (array_key_exists('actualGateDeparture', $opTimes)) {
                $data['DepDate'] = $opTimes['actualGateDeparture']['dateLocal'];
                $data['DepDateUtc'] = $opTimes['actualGateDeparture']['dateUtc'];
            }

            if (array_key_exists('actualGateArrival', $opTimes)) {
                $data['ArrDate'] = $opTimes['actualGateArrival']['dateLocal'];
                $data['ArrDateUtc'] = $opTimes['actualGateArrival']['dateUtc'];
            }
        }

        if (array_key_exists('flightEquipment', $segment)) {
            $flightEquipment = $segment['flightEquipment'];

            if (isset($flightEquipment['scheduledEquipmentIataCode']) && array_key_exists($flightEquipment['scheduledEquipmentIataCode'], $equipments)) {
                $data['Aircraft'] = $equipments[$flightEquipment['scheduledEquipmentIataCode']];
            }

            if (array_key_exists('actualEquipmentIataCode', $flightEquipment) && array_key_exists($flightEquipment['actualEquipmentIataCode'], $equipments)) {
                $data['Aircraft'] = $equipments[$flightEquipment['actualEquipmentIataCode']];
            }
        }

        if (array_key_exists('airportResources', $segment)) {
            $airportResources = $segment['airportResources'];

            if (array_key_exists('departureTerminal', $airportResources)) {
                $data['DepartureTerminal'] = $airportResources['departureTerminal'];
            }

            if (array_key_exists('departureGate', $airportResources)) {
                $data['DepartureGate'] = $airportResources['departureGate'];
            }

            if (array_key_exists('arrivalTerminal', $airportResources)) {
                $data['ArrivalTerminal'] = $airportResources['arrivalTerminal'];
            }

            if (array_key_exists('arrivalGate', $airportResources)) {
                $data['ArrivalGate'] = $airportResources['arrivalGate'];
            }

            if (array_key_exists('baggage', $airportResources)) {
                $data['BaggageClaim'] = $airportResources['baggage'];
            }
        }

        return $data;
    }
}
