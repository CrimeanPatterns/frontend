<?php

namespace AwardWallet\MainBundle\Manager\Exception;

class BookerRequiredException extends ProgramShareException
{
    public function __construct()
    {
        parent::__construct("User must be Booker");
    }
}
