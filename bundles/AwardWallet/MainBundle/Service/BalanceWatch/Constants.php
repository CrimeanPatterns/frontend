<?php

namespace AwardWallet\MainBundle\Service\BalanceWatch;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class Constants
{
    public const TIMEOUT_SECONDS = 86400 * 7;

    public const EVENT_START_MONITORED = 1;
    public const EVENT_BALANCE_CHANGED = 2;
    public const EVENT_TIMEOUT = 3;
    public const EVENT_UPDATE_ERROR = 4;
    public const EVENT_FORCED_STOP = 5;

    public const EVENTS = [
        self::EVENT_START_MONITORED => 'start monitored',
        self::EVENT_BALANCE_CHANGED => 'balance changed',
        self::EVENT_TIMEOUT => 'update timeout',
        self::EVENT_UPDATE_ERROR => 'update error',
        self::EVENT_FORCED_STOP => 'forced stop',
    ];

    public const TRANSACTION_COST = 1;
    public const GIFT_COUNT = 1;
}
