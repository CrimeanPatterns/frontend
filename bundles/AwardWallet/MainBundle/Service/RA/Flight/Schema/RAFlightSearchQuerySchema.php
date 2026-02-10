<?php

namespace AwardWallet\MainBundle\Service\RA\Flight\Schema;

use AwardWallet\MainBundle\Entity\RAFlightSearchQuery;
use AwardWallet\MainBundle\Service\EnhancedAdmin\AbstractEnhancedSchema;
use AwardWallet\MainBundle\Service\RA\Flight\Api;

class RAFlightSearchQuerySchema extends AbstractEnhancedSchema
{
    private array $parsers;

    public function __construct(Api $api)
    {
        $this->parsers = $api->getParserList();

        parent::__construct();

        $this->ListClass = RAFlightSearchQueryList::class;
        $this->Fields['UserID']['filterWidth'] = 25;
        $this->Fields['RAFlightSearchQueryID']['Caption'] = 'ID';
        $this->Fields['RAFlightSearchQueryID']['filterWidth'] = 25;
        $this->Fields['DepartureAirports']['Caption'] = 'From';
        $this->Fields['DepartureAirports']['filterWidth'] = 45;
        $this->Fields['DepartureAirports']['Sort'] = false;
        $this->Fields['ArrivalAirports']['Caption'] = 'To';
        $this->Fields['ArrivalAirports']['filterWidth'] = 65;
        $this->Fields['ArrivalAirports']['Sort'] = false;
        $this->Fields['DepDateFrom']['Caption'] = 'From date';
        $this->Fields['DepDateFrom']['Sort'] = 'DepDateFrom ASC';
        $this->Fields['DepDateTo']['Caption'] = 'To date';
        $this->Fields['FlightClass']['Caption'] = 'Class of Service';
        $this->Fields['FlightClass']['Sort'] = false;
        $this->Fields['Adults']['filterWidth'] = 35;
        $this->Fields['SearchInterval']['Caption'] = 'Repeat';
        $this->Fields['SearchInterval']['filterWidth'] = 35;
        $this->Fields['Parsers']['Sort'] = false;
        $this->Fields['Parsers']['Options'] = array_map(
            fn (array $parser) => $parser['name'],
            $this->parsers
        );
        $this->Fields['Parsers']['filterWidth'] = 100;
        $this->Fields['EconomyMilesLimit']['Sort'] = false;
        $this->Fields['EconomyMilesLimit']['filterWidth'] = 30;
        $this->Fields['PremiumEconomyMilesLimit']['Sort'] = false;
        $this->Fields['PremiumEconomyMilesLimit']['filterWidth'] = 30;
        $this->Fields['BusinessMilesLimit']['Sort'] = false;
        $this->Fields['BusinessMilesLimit']['filterWidth'] = 30;
        $this->Fields['FirstMilesLimit']['Sort'] = false;
        $this->Fields['FirstMilesLimit']['filterWidth'] = 30;
        $this->Fields['SearchCount']['Caption'] = 'Searches';
        $this->Fields['SearchCount']['filterWidth'] = 15;
        $this->Fields['CreateDate']['Caption'] = 'Created';
        $this->Fields['LastSearchDate']['Caption'] = 'Last Search';
        $this->Fields['Status'] = [
            'Type' => 'string',
            'Options' => [
                0 => 'In progress',
                1 => 'Inactive',
            ],
            'Sort' => 's.Status ASC',
            'FilterField' => 's.Status',
            'filterWidth' => 30,
        ];

        // insert after FirstMilesLimit key
        $this->Fields = array_merge(
            array_slice($this->Fields, 0, array_search('FirstMilesLimit', array_keys($this->Fields), true) + 1, true),
            [
                'AdditionFilters' => [
                    'Type' => 'string',
                    'Database' => false,
                ],
            ],
            array_slice($this->Fields, array_search('FirstMilesLimit', array_keys($this->Fields), true) + 1, null, true)
        );

        unset(
            $this->Fields['UpdateDate'],
            $this->Fields['State'],
            $this->Fields['MaxTotalDuration'],
            $this->Fields['MaxSingleLayoverDuration'],
            $this->Fields['MaxTotalLayoverDuration'],
            $this->Fields['MaxStops'],
            $this->Fields['DeleteDate'],
        );
    }

    protected function guessFieldOptions(string $field, array $fieldInfo): ?array
    {
        if ($field === 'FlightClass') {
            return [
                RAFlightSearchQuery::FLIGHT_CLASS_ECONOMY | RAFlightSearchQuery::FLIGHT_CLASS_PREMIUM_ECONOMY | RAFlightSearchQuery::FLIGHT_CLASS_BUSINESS | RAFlightSearchQuery::FLIGHT_CLASS_FIRST => 'Any',
                RAFlightSearchQuery::FLIGHT_CLASS_ECONOMY | RAFlightSearchQuery::FLIGHT_CLASS_PREMIUM_ECONOMY => 'Economy & Premium Economy',
                RAFlightSearchQuery::FLIGHT_CLASS_BUSINESS | RAFlightSearchQuery::FLIGHT_CLASS_FIRST => 'Business & First',
                RAFlightSearchQuery::FLIGHT_CLASS_ECONOMY => 'Economy',
                RAFlightSearchQuery::FLIGHT_CLASS_PREMIUM_ECONOMY => 'Premium Economy',
                RAFlightSearchQuery::FLIGHT_CLASS_BUSINESS => 'Business',
                RAFlightSearchQuery::FLIGHT_CLASS_FIRST => 'First',

                RAFlightSearchQuery::FLIGHT_CLASS_ECONOMY | RAFlightSearchQuery::FLIGHT_CLASS_BUSINESS => 'Economy & Business',
                RAFlightSearchQuery::FLIGHT_CLASS_ECONOMY | RAFlightSearchQuery::FLIGHT_CLASS_FIRST => 'Economy & First',
                RAFlightSearchQuery::FLIGHT_CLASS_PREMIUM_ECONOMY | RAFlightSearchQuery::FLIGHT_CLASS_BUSINESS => 'Premium Economy & Business',
                RAFlightSearchQuery::FLIGHT_CLASS_PREMIUM_ECONOMY | RAFlightSearchQuery::FLIGHT_CLASS_FIRST => 'Premium Economy & First',
                RAFlightSearchQuery::FLIGHT_CLASS_ECONOMY | RAFlightSearchQuery::FLIGHT_CLASS_PREMIUM_ECONOMY | RAFlightSearchQuery::FLIGHT_CLASS_BUSINESS => 'Economy & Premium Economy & Business',
                RAFlightSearchQuery::FLIGHT_CLASS_ECONOMY | RAFlightSearchQuery::FLIGHT_CLASS_PREMIUM_ECONOMY | RAFlightSearchQuery::FLIGHT_CLASS_FIRST => 'Economy & Premium Economy & First',
                RAFlightSearchQuery::FLIGHT_CLASS_ECONOMY | RAFlightSearchQuery::FLIGHT_CLASS_BUSINESS | RAFlightSearchQuery::FLIGHT_CLASS_FIRST => 'Economy & Business & First',
                RAFlightSearchQuery::FLIGHT_CLASS_PREMIUM_ECONOMY | RAFlightSearchQuery::FLIGHT_CLASS_BUSINESS | RAFlightSearchQuery::FLIGHT_CLASS_FIRST => 'Premium Economy & Business & First',
            ];
        } elseif ($field === 'SearchInterval') {
            return [
                RAFlightSearchQuery::SEARCH_INTERVAL_ONCE => 'Once',
                RAFlightSearchQuery::SEARCH_INTERVAL_DAILY => 'Daily',
                RAFlightSearchQuery::SEARCH_INTERVAL_WEEKLY => 'Weekly',
            ];
        } elseif ($field === 'Adults') {
            return array_combine(range(1, 9), range(1, 9));
        }

        return parent::guessFieldOptions($field, $fieldInfo);
    }
}
