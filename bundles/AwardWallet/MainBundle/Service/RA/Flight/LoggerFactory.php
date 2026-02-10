<?php

namespace AwardWallet\MainBundle\Service\RA\Flight;

use AwardWallet\MainBundle\Service\LogProcessor;
use Monolog\Handler\PsrHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class LoggerFactory
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function createProcessor(array $baseContext = []): LogProcessor
    {
        return new LogProcessor(
            'ra_flight',
            $baseContext,
            [],
            ['class', 'q:%d!query', 'requestId', 'route', 'depDate']
        );
    }

    public function createLogger(LogProcessor $logProcessor): LoggerInterface
    {
        return new Logger('ra_flight', [new PsrHandler($this->logger)], [$logProcessor]);
    }
}
