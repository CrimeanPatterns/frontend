<?php

namespace AwardWallet\MainBundle\Security\Encryptor\Backend;

interface BackendInterface
{
    public function supports(string $ciphertext): bool;

    public function encrypt(string $plaintext): string;

    public function decrypt(string $ciphertext): string;
}
