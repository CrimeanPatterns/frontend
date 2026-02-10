<?php

namespace AwardWallet\MainBundle\Service\FlightInfo\Exceptions;

class Exception extends \Exception
{
    public function __construct($message = "", $code = 0, ?\Exception $previous = null)
    {
        $message = "[FlightInfo] " . $message;

        parent::__construct($message, $code, $previous);
    }
}
