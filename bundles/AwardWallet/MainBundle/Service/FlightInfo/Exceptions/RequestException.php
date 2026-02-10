<?php

namespace AwardWallet\MainBundle\Service\FlightInfo\Exceptions;

class RequestException extends Exception
{
    public function __construct($message = "", $code = 0, ?\Exception $previous = null)
    {
        $message = $message ?: 'Request Error';

        parent::__construct($message, $code, $previous);
    }
}
