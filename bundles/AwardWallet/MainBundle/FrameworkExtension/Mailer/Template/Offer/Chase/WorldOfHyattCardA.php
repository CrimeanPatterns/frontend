<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Offer\Chase;

class WorldOfHyattCardA extends AbstractTemplate
{
    public $link = 'https://awardwallet.com/blog/link/travel-plans-world-of-hyatt-a/';

    public static function getEmailKind(): string
    {
        return 'travel_plans_world_of_hyatt_a';
    }

    public static function getDescription(): string
    {
        return 'World of Hyatt Card (A)';
    }
}
