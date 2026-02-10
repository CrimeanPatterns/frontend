<?php

namespace AwardWallet\Tests\Modules\DbBuilder;

class RaFlightFullSearchStat extends AbstractDbEntity
{
    public function __construct(
        string $depCode,
        string $arrCode,
        int $period,
        string $flightClass,
        int $passengersCount,
        \DateTime $lastSearchDate,
        ?\DateTime $lastFullSearchDate = null,
        array $fields = []
    ) {
        parent::__construct(array_merge(
            $fields,
            [
                'DepartureAirportCode' => $depCode,
                'ArrivalAirportCode' => $arrCode,
                'Period' => $period,
                'FlightClass' => $flightClass,
                'PassengersCount' => $passengersCount,
                'LastSearchDate' => $lastSearchDate->format('Y-m-d H:i:s'),
                'LastFullSearchDate' => $lastFullSearchDate ? $lastFullSearchDate->format('Y-m-d H:i:s') : null,
            ]
        ));
    }

    public function getPrimaryKey(): array
    {
        return [
            'DepartureAirportCode',
            'ArrivalAirportCode',
            'Period',
            'FlightClass',
            'PassengersCount',
        ];
    }
}
