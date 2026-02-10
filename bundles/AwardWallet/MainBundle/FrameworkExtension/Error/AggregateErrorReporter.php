<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Error;

use Psr\Log\LoggerInterface;

class AggregateErrorReporter
{
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var array<string, int>
     */
    private $loggedCriticalMap = [];
    /**
     * @var bool
     */
    private $isReporting = false;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function logThrowable(\Throwable $throwable): void
    {
        if ($this->isReporting) {
            return;
        }

        $this->isReporting = true;
        $logEntry = ErrorUtils::makeLogEntry($throwable);
        $messageHash = $logEntry->getMessageHash();
        $isCritical = ErrorUtils::isCriticalThrowable($throwable);

        if ($isCritical && !isset($this->loggedCriticalMap[$messageHash])) {
            $this->updateCount($messageHash);
            $this->logger->critical($logEntry->getMessage(), $logEntry->getContext());
        } else {
            $errorContext = $logEntry->getContext();

            if ($isCritical && isset($this->loggedCriticalMap[$messageHash])) {
                $errorContext['aggregatedCount'] = $this->loggedCriticalMap[$messageHash];
            }

            $this->updateCount($messageHash);
            $this->logger->error($logEntry->getMessage(), $errorContext);
        }

        $this->isReporting = false;
    }

    protected function updateCount(string $messageHash): void
    {
        $this->loggedCriticalMap[$messageHash] = ($this->loggedCriticalMap[$messageHash] ?? 0) + 1;
    }
}
