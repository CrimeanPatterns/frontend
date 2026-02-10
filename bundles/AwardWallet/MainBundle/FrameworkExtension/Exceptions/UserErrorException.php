<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class UserErrorException extends \Exception implements HttpExceptionInterface
{
    public function __construct($message = "", $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, 400, $previous);
    }

    public function getStatusCode()
    {
        return 400;
    }

    public function getHeaders()
    {
        return [];
    }
}
