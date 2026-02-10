<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Error;

use Psr\Log\LoggerInterface;

class SimpleErrorReporter
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function logThrowable(\Throwable $throwable): void
    {
        $logEntry = ErrorUtils::makeLogEntry($throwable);

        if (ErrorUtils::isCriticalThrowable($throwable)) {
            $this->logger->critical($logEntry->getMessage(), $logEntry->getContext());
        } else {
            $this->logger->error($logEntry->getMessage(), $logEntry->getContext());
        }
    }
}
