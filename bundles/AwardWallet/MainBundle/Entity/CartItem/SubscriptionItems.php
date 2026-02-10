<?php

namespace AwardWallet\MainBundle\Entity\CartItem;

class SubscriptionItems
{
    public static function getTypes(): array
    {
        return [
            AT201Subscription1Month::TYPE,
            AT201Subscription6Months::TYPE,
            AT201Subscription1Year::TYPE,
            AwPlusSubscription::TYPE,
            AwPlusSubscription6Months::TYPE,
            AwPlusWeekSubscription::TYPE,
        ];
    }
}
