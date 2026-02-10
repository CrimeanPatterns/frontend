<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\DataProvider;

use AwardWallet\MainBundle\Globals\PrivateConstructor;

abstract class Group
{
    use PrivateConstructor;

    public const GENERAL = 'General';
    public const FACEBOOK_AW101 = 'Facebook AW 101';
    public const GROUPS_2_US = '2 Groups, from US\unknown';
    public const GROUPS_14_US = '14 Groups, from US\unknown, 20000 each, date: 25 May 2019';
    public const GROUPS_3_US = '3 Groups, from US\unknown, 6500 each, date: 25 May 2019';
    public const FOUNDERS_CARD = 'Founders Card';

    public static function getGroupPriority(string $group): int
    {
        if (self::GENERAL === $group) {
            return 100;
        }

        return 0;
    }
}
