<?php

namespace AwardWallet\MainBundle\Manager\Exception;

use AwardWallet\MainBundle\Security\TranslatedAuthenticationException;

class NotBusinessAdministratorException extends TranslatedAuthenticationException
{
    public function __construct($protection = null)
    {
        parent::__construct("You are not an administrator of any business account", $protection);
    }
}
