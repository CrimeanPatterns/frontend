<?php

namespace AwardWallet\MainBundle\Globals\Cart;

class SubscriptionPeriod
{
    public const DURATION_1_DAY = '+1 day';
    public const DURATION_1_WEEK = '+1 week';
    public const DURATION_1_MONTH = '+1 months';
    public const DURATION_2_MONTHS = '+2 months';
    public const DURATION_3_MONTHS = '+3 months';
    public const DURATION_6_MONTHS = '+6 months';
    public const DURATION_1_YEAR = '+1 year';
    public const DURATION_20_YEARS = '+20 years';

    public const DAYS_1_DAY = 1;
    public const DAYS_1_WEEK = 7;
    public const DAYS_1_MONTH = 30;
    public const DAYS_2_MONTHS = 60;
    public const DAYS_3_MONTHS = 90;
    public const DAYS_6_MONTHS = 180;
    public const DAYS_1_YEAR = 360;
    public const DAYS_20_YEAR = 360 * 20;

    public const DURATION_TO_DAYS = [
        self::DURATION_1_DAY => self::DAYS_1_DAY,
        self::DURATION_1_WEEK => self::DAYS_1_WEEK,
        self::DURATION_1_MONTH => self::DAYS_1_MONTH,
        self::DURATION_2_MONTHS => self::DAYS_2_MONTHS,
        self::DURATION_3_MONTHS => self::DAYS_3_MONTHS,
        self::DURATION_6_MONTHS => self::DAYS_6_MONTHS,
        self::DURATION_1_YEAR => self::DAYS_1_YEAR,
        self::DURATION_20_YEARS => self::DAYS_20_YEAR,
    ];

    public const DAYS_TO_DURATION = [
        self::DAYS_1_DAY => self::DURATION_1_DAY,
        self::DAYS_1_WEEK => self::DURATION_1_WEEK,
        self::DAYS_1_MONTH => self::DURATION_1_MONTH,
        self::DAYS_2_MONTHS => self::DURATION_2_MONTHS,
        self::DAYS_3_MONTHS => self::DURATION_3_MONTHS,
        self::DAYS_6_MONTHS => self::DURATION_6_MONTHS,
        self::DAYS_1_YEAR => self::DURATION_1_YEAR,
        self::DAYS_20_YEAR => self::DURATION_20_YEARS,
    ];
}
