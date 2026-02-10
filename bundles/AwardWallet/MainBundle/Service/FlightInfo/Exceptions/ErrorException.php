<?php

namespace AwardWallet\MainBundle\Service\FlightInfo\Exceptions;

class ErrorException extends ResponseException
{
    private $error;

    public function setError($error)
    {
        $this->error = $error;

        return $this;
    }

    public function getError()
    {
        return $this->error;
    }
}
