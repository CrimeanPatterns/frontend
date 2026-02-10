<?php

namespace AwardWallet\MainBundle\Manager\Exception;

class LocallyStoredPasswordException extends ProgramShareException
{
    public function __construct()
    {
        parent::__construct("Can't reveal locally stored password");
    }
}
