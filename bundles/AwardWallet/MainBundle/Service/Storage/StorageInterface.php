<?php

namespace AwardWallet\MainBundle\Service\Storage;

use Psr\Http\Message\StreamInterface;

interface StorageInterface
{
    public function get(string $key): ?StreamInterface;

    public function isExists(string $key): bool;

    public function put(string $key, $content, ?\DateTime $expires = null): void;

    public function delete(string $key): void;
}
