<?php

namespace AwardWallet\MainBundle\Security\Encryptor\Exception;

class EncryptionFailedException extends \RuntimeException implements EncryptorExceptionInterface
{
    public function __construct($code = 0, ?\Throwable $previous = null)
    {
        parent::__construct("Encryption failed", $code, $previous);
    }
}
