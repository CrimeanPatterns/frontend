<?php

namespace AwardWallet\MainBundle\Service\Lounge;

use Monolog\Handler\PsrHandler;
use Monolog\Logger as BaseLogger;
use Psr\Log\LoggerInterface;

class Logger extends BaseLogger
{
    private LogProcessor $logProcessor;

    public function __construct(LoggerInterface $logger, LogProcessor $logProcessor)
    {
        parent::__construct('lounges', [new PsrHandler($logger)], [$logProcessor]);

        $this->logProcessor = $logProcessor;
    }

    public function getLogProcessor(): LogProcessor
    {
        return $this->logProcessor;
    }
}
