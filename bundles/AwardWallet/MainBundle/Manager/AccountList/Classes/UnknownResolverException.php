<?php

namespace AwardWallet\MainBundle\Manager\AccountList\Classes;

class UnknownResolverException extends \Exception
{
    public function __construct()
    {
        parent::__construct("Unknown Resolver");
    }
}
