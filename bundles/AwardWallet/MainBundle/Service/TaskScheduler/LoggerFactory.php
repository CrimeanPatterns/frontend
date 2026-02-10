<?php

namespace AwardWallet\MainBundle\Service\TaskScheduler;

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
        return new LogProcessor('task_scheduler');
    }

    public function createLogger(LogProcessor $logProcessor): LoggerInterface
    {
        return new Logger('task_scheduler', [new PsrHandler($this->logger)], [$logProcessor]);
    }
}
