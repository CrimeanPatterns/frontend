<?php

namespace AwardWallet\MainBundle\Security\Encryptor\Backend;

use AwardWallet\MainBundle\Security\Encryptor\Backend\Util\SodiumEncryptor;

class Sodium1Backend implements BackendInterface
{
    /**
     * @var SodiumEncryptor
     */
    private $sodiumEncryptor;
    /**
     * @var string
     */
    private $key;
    /**
     * @var string
     */
    private $prefix;

    public function __construct(SodiumEncryptor $sodiumEncryptor, string $prefix, string $key)
    {
        $this->sodiumEncryptor = $sodiumEncryptor;

        $key = \base64_decode($key);

        if (false === $key) {
            throw new \InvalidArgumentException('Invalid key!');
        }

        $this->key = $key;
        $this->prefix = $prefix;
    }

    public function supports(string $ciphertext): bool
    {
        $compareResult = \sodium_compare(
            \mb_substr($ciphertext, 0, \mb_strlen($this->prefix, '8bit'), '8bit'),
            $this->prefix
        );

        return 0 === $compareResult;
    }

    public function encrypt(string $plaintext): string
    {
        return $this->prefix . $this->sodiumEncryptor->encrypt($plaintext, $this->key);
    }

    public function decrypt(string $ciphertext): string
    {
        return $this->sodiumEncryptor->decrypt(
            \mb_substr($ciphertext, \mb_strlen($this->prefix, '8bit'), null, '8bit'),
            $this->key
        );
    }
}
