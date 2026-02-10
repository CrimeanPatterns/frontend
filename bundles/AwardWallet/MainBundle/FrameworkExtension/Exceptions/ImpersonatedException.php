<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Exceptions;

class ImpersonatedException extends UserErrorException
{
    public function __construct()
    {
        parent::__construct("You are impersonated as <b><big>{$_SESSION['Login']}</big></b>. You can't use this feature. <a href='/security/logout?BackTo=" . urlencode($_SERVER['REQUEST_URI']) . "'>Logout</a>");
    }
}
