<?php

namespace AwardWallet\MainBundle\FrameworkExtension\HttpFoundation;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Cookie as SymfonyCookie;

abstract class AwCookieFactory
{
    public static function createLax(string $name, ?string $value = null, $expire = 0, ?string $path = '/', ?string $domain = null, ?bool $secure = null, bool $httpOnly = true, bool $raw = false): SymfonyCookie
    {
        return SymfonyCookie::create($name, $value, $expire, $path, $domain, $secure, $httpOnly, $raw, Cookie::SAMESITE_LAX);
    }
}
