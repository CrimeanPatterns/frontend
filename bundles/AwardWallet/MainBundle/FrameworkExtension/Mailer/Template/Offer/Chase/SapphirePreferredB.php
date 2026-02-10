<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Offer\Chase;

class SapphirePreferredB extends SapphirePreferredA
{
    public $link = 'https://awardwallet.com/blog/link/travel-plans-sapphire-preferred-b/';

    public static function getEmailKind(): string
    {
        return 'travel_plans_sapphire_preferred_b';
    }

    public static function getDescription(): string
    {
        return 'Sapphire Preferred (B)';
    }
}
