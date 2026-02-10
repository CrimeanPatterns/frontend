<?php

namespace AwardWallet\MainBundle\Service;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use Psr\Log\LoggerInterface;

/**
 * @NoDI
 */
class ProgressLogger
{
    private LoggerInterface $logger;

    private int $reportOnNth;

    private int $reportEverySeconds;

    private int $lastLogTime = 0;

    private int $lastLogCount = 0;

    public function __construct(LoggerInterface $logger, int $reportOnNth, int $reportEverySeconds)
    {
        $this->logger = $logger;
        $this->reportOnNth = $reportOnNth;
        $this->reportEverySeconds = $reportEverySeconds;
    }

    public function showProgress(string $message, int $count)
    {
        if ($count === 0) {
            $this->lastLogCount = 0;
            $this->lastLogTime = time();
        }

        if (($count % $this->reportOnNth) === 0 && ($count === 0 || ($this->lastLogTime + $this->reportEverySeconds) < time())) {
            $context = [
                'memory' => (int) round(memory_get_usage() / (1024 * 1024)),
            ];
            $duration = time() - $this->lastLogTime;

            if ($duration > 0) {
                $context['speed'] = round(($count - $this->lastLogCount) / 1000 / $duration, 1) . ' k/sec';
            }
            $this->logger->info("$message: " . number_format($count) . " rows processed", $context);
            $this->lastLogTime = time();
            $this->lastLogCount = $count;
        }
    }
}
