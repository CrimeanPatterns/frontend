<?php

namespace AwardWallet\MainBundle\Service\FlightInfo\Exceptions;

class ResponseException extends Exception
{
    public function __construct($message = "", $code = 0, ?\Exception $previous = null)
    {
        $message = $message ?: 'Response Error';

        parent::__construct($message, $code, $previous);
    }
}
