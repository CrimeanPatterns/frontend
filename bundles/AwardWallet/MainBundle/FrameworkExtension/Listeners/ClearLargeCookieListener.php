<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Listeners;

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class ClearLargeCookieListener
{
    private const MAX_SIZE_KB = 256; // nginx.conf: large_client_header_buffers
    private const CRITICAL_PERCENT = 20;

    private const BIG_COOKIES_MATCH = ['_iiq_fdata_', 'euconsent-'];

    public function __construct()
    {
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();
        $cookies = $request->cookies;

        if (!$this->isFoundBigCookies($cookies)) {
            return;
        }

        $maxSize = self::MAX_SIZE_KB * 1024;
        $currentSize = $this->getFullSize($cookies);
        $isCriticalState = $currentSize >= $maxSize - ($maxSize * (self::CRITICAL_PERCENT / 100));

        if (!$isCriticalState) {
            return;
        }

        $response = $event->getResponse();

        if (null !== $firstName = $this->getCookieNameStartsWith(self::BIG_COOKIES_MATCH[0], $cookies)) {
            $response->headers->clearCookie($firstName, '/');
        }

        if (null === $firstName
            && null !== $secondName = $this->getCookieNameStartsWith(self::BIG_COOKIES_MATCH[1], $cookies)
        ) {
            $response->headers->clearCookie($secondName, '/');
        }

        $event->setResponse($response);
    }

    private function isFoundBigCookies(ParameterBag $cookies): bool
    {
        foreach (self::BIG_COOKIES_MATCH as $bigName) {
            foreach ($cookies->keys() as $name) {
                if (0 === stripos($name, $bigName)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function getFullSize(ParameterBag $cookies): int
    {
        $size = 0;

        foreach ($cookies as $name => $value) {
            $size += (strlen($name) + strlen($value));
        }

        return $size;
    }

    private function getCookieNameStartsWith(string $startNameCookie, ParameterBag $cookies): ?string
    {
        foreach ($cookies->keys() as $name) {
            if (0 === stripos($name, $startNameCookie)) {
                return $name;
            }
        }

        return null;
    }
}
