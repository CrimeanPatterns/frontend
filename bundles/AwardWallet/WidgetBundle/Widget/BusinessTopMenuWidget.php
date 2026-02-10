<?php

namespace AwardWallet\WidgetBundle\Widget;

use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorage;
use AwardWallet\MainBundle\Security\Voter\BusinessVoter;

class BusinessTopMenuWidget extends MenuWidget
{
    public function __construct($template = 'menu.html.twig', $menu = [], AwTokenStorage $tokenStorage, BusinessVoter $businessVoter)
    {
        if (!$businessVoter->businessAccounts($tokenStorage->getToken())) {
            unset($menu['button_accounts']);
            unset($menu['button_trips']);
            unset($menu['button_members']);
            unset($menu['button_balance']);
        }
        parent::__construct($template, $menu);
    }
}
