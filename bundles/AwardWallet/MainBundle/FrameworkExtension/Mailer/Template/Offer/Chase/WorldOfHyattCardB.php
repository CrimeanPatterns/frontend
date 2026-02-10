<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Offer\Chase;

class WorldOfHyattCardB extends WorldOfHyattCardA
{
    public $link = 'https://awardwallet.com/blog/link/travel-plans-world-of-hyatt-b/';

    public static function getEmailKind(): string
    {
        return 'travel_plans_world_of_hyatt_b';
    }

    public static function getDescription(): string
    {
        return 'World of Hyatt Card (B)';
    }
}
