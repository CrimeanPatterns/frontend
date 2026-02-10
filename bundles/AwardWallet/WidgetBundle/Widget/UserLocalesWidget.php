<?php

namespace AwardWallet\WidgetBundle\Widget;

use AwardWallet\MainBundle\Entity\Usr;

class UserLocalesWidget extends LocalesWidget
{
    protected function checkContext()
    {
        $token = $this->container->get('security.token_storage')->getToken();

        if (!empty($token) && $token->isAuthenticated()) {
            $user = $token->getUser();

            if ($user instanceof Usr) {
                return true;
            }
        }

        return false;
    }
}
