<?php

namespace AwardWallet\MainBundle\Security\Encryptor\Exception;

class DecryptionFailedException extends \RuntimeException implements EncryptorExceptionInterface
{
    public function __construct($code = 0, ?\Throwable $previous = null)
    {
        parent::__construct("Decryption failed", $code, $previous);
    }
}
