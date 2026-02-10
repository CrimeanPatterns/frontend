<?php

namespace AwardWallet\MainBundle\Manager\Exception;

class ProgramOwnerRequiredException extends ProgramShareException
{
    public function __construct()
    {
        parent::__construct("Can't grant access to other user's program");
    }
}
