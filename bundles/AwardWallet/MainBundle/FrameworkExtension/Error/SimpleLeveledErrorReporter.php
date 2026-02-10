<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Error;

use Psr\Log\LoggerInterface;

class SimpleLeveledErrorReporter
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function logThrowable(\Throwable $e, $level): void
    {
        $logEntry = ErrorUtils::makeLogEntry($e);

        $this->logger->log($level, $logEntry->getMessage(), $logEntry->getContext());
    }
}
