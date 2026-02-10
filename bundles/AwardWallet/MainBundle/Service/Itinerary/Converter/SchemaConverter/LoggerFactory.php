<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter;

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

    public function createProcessor(): LogProcessor
    {
        return new LogProcessor('itinerary_converter', [], [], ['it', 'segment']);
    }

    public function createLogger(LogProcessor $logProcessor): LoggerInterface
    {
        return new Logger('itinerary_converter', [new PsrHandler($this->logger)], [$logProcessor]);
    }
}
