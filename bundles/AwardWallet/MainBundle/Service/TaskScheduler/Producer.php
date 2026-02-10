<?php

namespace AwardWallet\MainBundle\Service\TaskScheduler;

use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use Psr\Log\LoggerInterface;

/**
 * use this class instead of \AwardWallet\MainBundle\Worker\AsyncProcess\Process::execute because there is no need in
 * unreliable storage like Memcached and you can schedule task execution with delay without any problems.
 */
class Producer
{
    public const PRIORITY_LOW = 0;
    public const PRIORITY_NORMAL = 1;
    public const PRIORITY_HIGH = 2;

    private ProducerInterface $baseProducer;

    private LoggerInterface $logger;

    public function __construct(ProducerInterface $oldSoundRabbitMqTaskSchedulerProducer, LoggerFactory $loggerFactory)
    {
        $this->baseProducer = $oldSoundRabbitMqTaskSchedulerProducer;
        $this->logger = $loggerFactory->createLogger($loggerFactory->createProcessor());
    }

    /**
     * @param int|null $delaySeconds Delay in seconds, max (2^32)-1 milliseconds (approx 49 days)
     */
    public function publish(TaskInterface $task, ?int $delaySeconds = null, int $priority = self::PRIORITY_LOW): void
    {
        if (!in_array($priority, [self::PRIORITY_LOW, self::PRIORITY_NORMAL, self::PRIORITY_HIGH])) {
            throw new \InvalidArgumentException('Invalid priority');
        }

        if (is_int($delaySeconds)) {
            if ($delaySeconds < 0) {
                throw new \InvalidArgumentException(sprintf('Delay must be greater than or equal to 0, %d given', $delaySeconds));
            }

            $maxDelay = 3600 * 24 * 47; // 47 days, -2 day for safety

            if ($delaySeconds > $maxDelay) {
                throw new \InvalidArgumentException(sprintf('Delay must be less than %d seconds, %d given', $maxDelay, $delaySeconds));
            }
        }

        $properties = [
            'priority' => $priority,
        ];

        if (is_int($delaySeconds) && $delaySeconds > 0) {
            $properties['application_headers'] = [
                'x-delay' => ['I', $delaySeconds * 1000],
            ];
        }

        $this->baseProducer->publish(serialize($task), '', $properties);

        $this->logger->info(sprintf(
            'task "%s" scheduled, delay: %d%s, priority: %d',
            sprintf('%s_%s', get_class($task), $task->getRequestId()),
            $delaySeconds ?? 0,
            ($delaySeconds ?? 0) > 0 ? sprintf(' (%s)', date('Y-m-d H:i:s', time() + $delaySeconds)) : '',
            $priority
        ));
    }
}
