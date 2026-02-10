<?php

namespace AwardWallet\MainBundle\Manager\Exception;

class RequestOwnerRequiredException extends ProgramShareException
{
    public function __construct()
    {
        parent::__construct("Can't grant access to other user's request");
    }
}
