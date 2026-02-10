<?php

namespace AwardWallet\MainBundle\Service\FlightNotification;

use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Service\LogProcessor;
use AwardWallet\MainBundle\Service\TaskScheduler\Producer as BaseProducer;
use Monolog\Handler\PsrHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class Producer
{
    private OffsetHandler $offsetHandler;

    private QueueLocker $queueLocker;

    private LoggerInterface $logger;

    private BaseProducer $baseProducer;

    public function __construct(
        OffsetHandler $offsetHandler,
        QueueLocker $queueLocker,
        LoggerInterface $logger,
        BaseProducer $baseProducer
    ) {
        $this->offsetHandler = $offsetHandler;
        $this->queueLocker = $queueLocker;
        $logProcessor = new LogProcessor('flight_notification_producer', [], [], ['ts:%d!ts', 'status']);
        $this->logger = new Logger('flight_notification', [new PsrHandler($logger)], [$logProcessor]);
        $this->baseProducer = $baseProducer;
    }

    /**
     * @param callable(OffsetStatus):bool $filter - filter OffsetStatus[]
     */
    public function publish(Tripsegment $tripSegment, ?\DateTimeInterface $now = null, ?callable $filter = null): bool
    {
        if (is_null($now)) {
            $now = new \DateTime();
        }

        $context = ['ts' => $tripSegment->getId()];
        $statuses = $this->offsetHandler->getOffsetsStatusesBySegment($tripSegment, $now);

        if (count($statuses) === 0) {
            $this->logger->debug('nothing to publish', $context);

            return false;
        }

        $processed = false;

        foreach ($statuses as $status) {
            $statusContext = array_merge($context, ['status' => $status->getKind()]);

            if (is_callable($filter) && !$filter($status)) {
                $this->logger->debug('filtered, skip', $statusContext);

                continue;
            }

            if ($this->queueLocker->isAcquired($tripSegment, $status)) {
                $this->logger->debug(sprintf('message {%s} is already in the queue, skip', $status), $statusContext);

                continue;
            }

            try {
                if (!is_null(NotificationDate::getDate($tripSegment, $status->getKind()))) {
                    $this->logger->debug(sprintf('message {%s} has already been sent, producer, skip', $status), $statusContext);

                    continue;
                }

                $this->logger->info(sprintf('publish message {%s}, %s', $status, $tripSegment->getDepartureDate()->format('Y-m-d H:i:s')), $statusContext);
                $this->queueLocker->acquire($tripSegment, $status);
                $this->baseProducer->publish(
                    new FlightAlertTask($tripSegment->getId()),
                    $status->getSendingDelay() > 0 ? $status->getSendingDelay() : null
                );
                $processed = true;
            } catch (\Exception $e) {
                $this->queueLocker->release($tripSegment, $status);

                throw $e;
            }
        }

        return $processed;
    }
}
