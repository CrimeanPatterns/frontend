<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Offer\Chase;

class FreedomUnlimitedB extends FreedomUnlimitedA
{
    public $link = 'https://awardwallet.com/blog/link/travel-plans-freedom-unlimited-b/';

    public static function getEmailKind(): string
    {
        return 'travel_plans_freedom_unlimited_b';
    }

    public static function getDescription(): string
    {
        return 'Freedom Unlimited (B)';
    }
}
