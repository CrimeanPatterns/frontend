<?php

namespace AwardWallet\MainBundle\Entity\CartItem;

class PlusItems
{
    public static function getTypes()
    {
        return [
            AwPlus::TYPE,
            AwPlus1Year::TYPE,
            AwPlus1Month::TYPE,
            AwPlus2Months::TYPE,
            AwPlus3Months::TYPE,
            AwPlus6Months::TYPE,
            AwPlus20Year::TYPE,
            AwPlusSubscription::TYPE,
            AwPlusSubscription6Months::TYPE,
            AwPlusTrial::TYPE,
            AwPlusTrial6Months::TYPE,
            AwPlusWeekSubscription::TYPE,
            AwPlusPrepaid::TYPE,
            AwPlusVIP1YearUpgrade::TYPE,
            Supporters3MonthsUpgrade::TYPE,
        ];
    }
}
