<?php

namespace AwardWallet\MainBundle\Manager\Exception;

class EmptyPasswordException extends ProgramShareException
{
    public function __construct()
    {
        parent::__construct("Can't reveal empty password");
    }
}
