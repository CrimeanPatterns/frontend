<?php

namespace AwardWallet\MainBundle\Loyalty;

class AutologinLinkValidator
{
    private const SALT = 'nifoewhr3902ofnjdhgiru';

    private string $secret;

    public function __construct(string $secret)
    {
        $this->secret = $secret;
    }

    public function createSignature(?string $link): string
    {
        if ($link === null || $link === '') {
            return '';
        }

        return sha1(self::SALT . $link . $this->secret);
    }

    public function validateLink(string $link, string $signature): bool
    {
        return $this->createSignature($link) === $signature;
    }
}
