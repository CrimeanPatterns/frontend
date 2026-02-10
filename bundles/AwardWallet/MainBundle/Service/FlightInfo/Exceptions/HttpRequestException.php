<?php

namespace AwardWallet\MainBundle\Service\FlightInfo\Exceptions;

class HttpRequestException extends Exception
{
    public function __construct($message = "", $code = 0, ?\Exception $previous = null)
    {
        $message = $message ?: 'HTTP Request Failure';

        parent::__construct($message, $code, $previous);
    }
}
