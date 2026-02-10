<?php

namespace AwardWallet\MainBundle\Security\Encryptor\Backend\Util;

use AwardWallet\MainBundle\Security\Encryptor\Exception\DecryptionFailedException;
use AwardWallet\MainBundle\Security\Encryptor\Exception\EncryptionFailedException;

class SodiumEncryptor
{
    /**
     * @throws EncryptionFailedException
     */
    public function encrypt(string $plaintext, string $key): string
    {
        $nonce = \random_bytes(\SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        try {
            return $nonce . \sodium_crypto_secretbox($plaintext, $nonce, $key);
        } catch (\SodiumException $e) {
            throw new EncryptionFailedException(0, $e);
        }
    }

    /**
     * @throws DecryptionFailedException
     */
    public function decrypt(string $ciphertext, string $key): string
    {
        try {
            $decrypted = \sodium_crypto_secretbox_open(
                \mb_substr($ciphertext, \SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit'),
                \mb_substr($ciphertext, 0, \SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit'),
                $key
            );
        } catch (\SodiumException $e) {
            throw new DecryptionFailedException(0, $e);
        }

        if (false === $decrypted) {
            throw new DecryptionFailedException();
        }

        return $decrypted;
    }
}
