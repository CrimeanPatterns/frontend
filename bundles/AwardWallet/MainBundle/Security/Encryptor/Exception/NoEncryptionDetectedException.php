<?php

namespace AwardWallet\MainBundle\Security\Encryptor\Exception;

class NoEncryptionDetectedException extends \RuntimeException implements EncryptorExceptionInterface
{
    public function __construct($code = 0, ?\Throwable $previous = null)
    {
        parent::__construct("No encryption detected", $code, $previous);
    }
}
