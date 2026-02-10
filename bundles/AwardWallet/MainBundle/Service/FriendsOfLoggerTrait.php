<?php

namespace AwardWallet\MainBundle\Service;

use AwardWallet\MainBundle\Globals\LoggerContext\ContextAwareLoggerWrapper;
use AwardWallet\MainBundle\Globals\Utils\BinaryLogger\BinaryLoggerFactory;
use Cocur\Slugify\SlugifyInterface;
use Psr\Log\LoggerInterface;

trait FriendsOfLoggerTrait
{
    public function makeContextAwareLogger(LoggerInterface $inner): ContextAwareLoggerWrapper
    {
        return (new ContextAwareLoggerWrapper($inner))
            ->withClass(self::class)
            ->withTypedContext();
    }

    public function makeBinaryLoggerFactory(LoggerInterface $inner, ?SlugifyInterface $slugify = null): BinaryLoggerFactory
    {
        if (!$inner instanceof ContextAwareLoggerWrapper) {
            $inner = $this->makeContextAwareLogger($inner);
        }

        return (new BinaryLoggerFactory($inner, $slugify))->toInfo();
    }
}
