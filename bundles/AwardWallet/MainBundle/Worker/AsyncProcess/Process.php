<?php

namespace AwardWallet\MainBundle\Worker\AsyncProcess;

use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;

/**
 * @deprecated use \AwardWallet\MainBundle\Service\TaskScheduler\Producer instead
 */
class Process
{
    public const PRIORITY_LOW = 0;
    public const PRIORITY_NORMAL = 1;
    public const PRIORITY_HIGH = 2;

    public const ALLOWED_PRIORITIES = [self::PRIORITY_LOW, self::PRIORITY_NORMAL, self::PRIORITY_HIGH];

    /**
     * @var ProducerInterface
     */
    private $producer;

    /**
     * @var Storage
     */
    private $storage;

    public function __construct(ProducerInterface $producer, Storage $storage)
    {
        $this->producer = $producer;
        $this->storage = $storage;
    }

    public function execute(Task $task, ?int $delaySeconds = null, bool $forcePublish = false, int $priority = self::PRIORITY_LOW): Response
    {
        $response = $this->storage->getResponse($task);

        if (($response->status == Response::STATUS_NONE) || $forcePublish) {
            $response->status = Response::STATUS_QUEUED;
            $this->storage->setResponse($task, $response);

            if (!in_array($priority, self::ALLOWED_PRIORITIES)) {
                throw new \InvalidArgumentException('Invalid priority');
            }

            $properties = [
                'priority' => $priority,
            ];

            if ($delaySeconds > 0) {
                $properties['application_headers'] = [
                    'x-delay' => ['I', $delaySeconds * 1000],
                ];
            }

            $this->producer->publish(serialize($task), '', $properties);
        }

        return $response;
    }
}
