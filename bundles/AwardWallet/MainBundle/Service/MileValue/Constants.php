<?php

namespace AwardWallet\MainBundle\Service\MileValue;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class Constants
{
    // values from AirClassDictionary.Target, see TAirClassDictionarySchema
    public const CLASS_BASIC_ECONOMY = 'Basic Economy';
    public const CLASS_ECONOMY = 'Economy';
    public const CLASS_ECONOMY_PLUS = 'Economy Plus';
    public const CLASS_PREMIUM_ECONOMY = 'Premium Economy';
    public const CLASS_BUSINESS = 'Business';
    public const CLASS_FIRST = 'First';

    public const CLASS_MAP_UNKNOWN = 'Unknown';
    public const CLASS_MAP_PARSE_ERROR = 'Parse Error';

    public const CLASSES_OF_SERVICE = [
        self::CLASS_BASIC_ECONOMY,
        self::CLASS_ECONOMY,
        self::CLASS_ECONOMY_PLUS,
        self::CLASS_PREMIUM_ECONOMY,
        self::CLASS_BUSINESS,
        self::CLASS_FIRST,
    ];

    public const LUXE_CLASSES_OF_SERVICE = [
        self::CLASS_BUSINESS,
        self::CLASS_FIRST,
    ];

    public const ECONOMY_CLASSES = [
        self::CLASS_BASIC_ECONOMY,
        self::CLASS_ECONOMY,
        self::CLASS_ECONOMY_PLUS,
    ];

    public const ROUTE_TYPE_MULTI_CITY = 'MC';
    public const ROUTE_TYPE_ROUND_TRIP = 'RT';
    public const ROUTE_TYPE_ONE_WAY = 'OW';

    public const ROUTE_TYPES = [
        self::ROUTE_TYPE_MULTI_CITY,
        self::ROUTE_TYPE_ROUND_TRIP,
        self::ROUTE_TYPE_ONE_WAY,
    ];

    public const LOWCOSTERS = [
        'F9', // frontier
        'NK', // spirit
        'E9', // evelop
        'DI', // norwegian uk
    ];

    public const ASSUME_BASIC_ECONOMY = [
        'NK', // spirit
    ];

    public const MILE_SOURCE_TRIP = 'T'; // Trip.SpentAwards
    public const MILE_SOURCE_ACCOUNT_HISTORY = 'H'; // AccountHistory linked through HistoryToTripLink

    public const MILE_SOURCES = [
        self::MILE_SOURCE_TRIP => 'Trip spent awards',
        self::MILE_SOURCE_ACCOUNT_HISTORY => 'Account history',
    ];

    /**
     * these fields could be edited by user.
     */
    public const CUSTOM_FIELDS = ['TravelersCount', 'TotalMilesSpent', 'TotalTaxesSpent', 'AlternativeCost'];

    public const STOP_TYPE_STOP_OVER = 'so';
    public const STOP_TYPE_RETURN = 'rt';
    public const STOP_TYPE_LAYOVER = 'lo';
}
