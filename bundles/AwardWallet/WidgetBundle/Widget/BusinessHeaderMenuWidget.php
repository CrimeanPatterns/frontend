<?php

namespace AwardWallet\WidgetBundle\Widget;

use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorage;
use AwardWallet\MainBundle\Security\Voter\BusinessVoter;

class BusinessHeaderMenuWidget extends TopMenuWidget
{
    public function __construct($template = 'menu.html.twig', $menu = [], AwTokenStorage $tokenStorage, BusinessVoter $businessVoter)
    {
        if (!$businessVoter->businessAccounts($tokenStorage->getToken())) {
            $menu['account'][0] = '/user/notifications';
        }
        parent::__construct($template, $menu);
    }
}
