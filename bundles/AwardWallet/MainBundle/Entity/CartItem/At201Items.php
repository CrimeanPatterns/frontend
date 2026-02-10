<?php

namespace AwardWallet\MainBundle\Entity\CartItem;

class At201Items
{
    public static function getTypes()
    {
        return [
            AT201Subscription1Month::TYPE,
            AT201Subscription6Months::TYPE,
            AT201Subscription1Year::TYPE,
        ];
    }
}
