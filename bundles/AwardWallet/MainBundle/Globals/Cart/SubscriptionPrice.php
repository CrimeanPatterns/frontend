<?php

namespace AwardWallet\MainBundle\Globals\Cart;

use AwardWallet\MainBundle\Entity\Usr;

class SubscriptionPrice
{
    public const AWPLUS_PRICE = [
        SubscriptionPeriod::DURATION_1_YEAR => 49.99,
        SubscriptionPeriod::DURATION_6_MONTHS => 25,
    ];

    public const AT201_PRICE = [
        SubscriptionPeriod::DURATION_1_YEAR => 119.99,
        SubscriptionPeriod::DURATION_6_MONTHS => 69.99,
        SubscriptionPeriod::DURATION_1_MONTH => 14.99,
    ];

    public static function getPrice(int $subscriptionType, string $duration): ?float
    {
        if ($subscriptionType === Usr::SUBSCRIPTION_TYPE_AWPLUS) {
            return self::AWPLUS_PRICE[$duration] ?? null;
        }

        if ($subscriptionType === Usr::SUBSCRIPTION_TYPE_AT201) {
            return self::AT201_PRICE[$duration] ?? null;
        }

        return null;
    }
}
