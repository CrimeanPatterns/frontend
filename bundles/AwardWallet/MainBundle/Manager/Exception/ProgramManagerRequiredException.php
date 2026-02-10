<?php

namespace AwardWallet\MainBundle\Manager\Exception;

class ProgramManagerRequiredException extends ProgramShareException
{
    public function __construct()
    {
        parent::__construct("Low access level to other user's program");
    }
}
