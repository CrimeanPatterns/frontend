<?php

namespace AwardWallet\MainBundle\Entity;

class ProviderMileValue
{
    public const STATUS_DISABLED = 0;
    public const STATUS_ENABLED = 1;
    public const STATUS_IN_PROGRESS = 2;

    public const STATUSES = [
        self::STATUS_DISABLED => 'Disabled',
        self::STATUS_ENABLED => 'Enabled',
        self::STATUS_IN_PROGRESS => 'In Progress',
    ];

    public const STATUSES_FOR_MILE_VALUE_CALCULATION = [
        self::STATUS_DISABLED,
        self::STATUS_ENABLED,
    ];
}
