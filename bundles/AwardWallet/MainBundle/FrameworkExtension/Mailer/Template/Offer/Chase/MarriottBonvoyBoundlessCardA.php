<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Offer\Chase;

class MarriottBonvoyBoundlessCardA extends AbstractTemplate
{
    public $link = 'https://awardwallet.com/blog/link/travel-plans-marriott-boundless-a/';

    public static function getEmailKind(): string
    {
        return 'travel_plans_marriott_boundless_a';
    }

    public static function getDescription(): string
    {
        return 'Marriott Bonvoy Boundless Card (A)';
    }
}
