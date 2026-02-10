<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\Itinerary\Matcher;

use AwardWallet\MainBundle\Service\GeoLocationMatcher;
use AwardWallet\Tests\Unit\BaseContainerTest;
use Psr\Log\LoggerInterface;
use Psr\Log\Test\TestLogger;

abstract class AbstractTest extends BaseContainerTest
{
    protected ?TestLogger $logger = null;

    protected function getLogs(): string
    {
        return implode("\n", array_column($this->logger->records, 'message'));
    }

    protected function assertLogContains(string $str)
    {
        self::assertStringContainsString($str, $this->getLogs());
    }

    protected function assertLogNotContains(string $str)
    {
        self::assertStringNotContainsString($str, $this->getLogs());
    }

    protected function getLogger(bool $new = false): LoggerInterface
    {
        if (is_null($this->logger) || $new) {
            return $this->logger = new TestLogger();
        }

        return $this->logger;
    }

    protected function getGeoLocationMatcher(bool $match): GeoLocationMatcher
    {
        return $this->makeEmpty(GeoLocationMatcher::class, ['match' => $match]);
    }
}
