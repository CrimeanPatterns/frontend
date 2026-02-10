<?php

namespace AwardWallet\MainBundle\Worker\PushNotification;

use AwardWallet\Common\Monolog\Processor\TraceProcessor;
use AwardWallet\MainBundle\Manager\MobileDeviceManager;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\Notification;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\OutdatedClientException;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\OutdatedMessageException;
use AwardWallet\MainBundle\Worker\PushNotification\Platform\PlatformHandlerInterface;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use RMS\PushNotificationsBundle\Service\Notifications;

class NotificationWorker implements ConsumerInterface
{
    private Notifications $sender;
    /**
     * @var array<int, PlatformHandlerInterface>
     */
    private $handlers;
    private LoggerInterface $logger;
    private NotificationHelper $notificationHelper;
    private LogHelper $logHelper;

    private $count = 0;
    private MobileDeviceManager $mobileDeviceManager;

    public function __construct(
        Notifications $sender,
        ProducerInterface $producer,
        ProducerInterface $delayedProducer,
        LoggerInterface $pushLogger,
        MobileDeviceManager $mobileDeviceManager
    ) {
        $this->sender = $sender;
        $this->logger = $pushLogger;
        $this->logHelper = new LogHelper('notification');
        $this->notificationHelper = new NotificationHelper($producer, $delayedProducer, $pushLogger, $this->logHelper);
        $this->mobileDeviceManager = $mobileDeviceManager;
    }

    public function addHandler(int $deviceType, PlatformHandlerInterface $platformHandler)
    {
        $this->handlers[$deviceType] = $platformHandler;
    }

    public function execute(AMQPMessage $message)
    {
        $this->count++;

        try {
            $notification = @unserialize($message->body);
        } catch (OutdatedClientException $e) {
            $this->logger->error($e->getMessage(), $this->logHelper->getDefaultFailContext());

            throw $e;
        } catch (OutdatedMessageException $e) {
            $this->logger->error($e->getMessage(), $this->logHelper->getDefaultFailContext());

            return true;
        }

        if (!$notification instanceof Notification) {
            $this->logger->error(sprintf('Unserialization failed, data: "%s"', $message->body), $this->logHelper->getDefaultFailContext());

            return true;
        }

        $this->logger->pushProcessor(function (array $record) use ($notification) {
            $record['context'] = array_merge($record['context'], $this->logHelper->getContext($notification));

            return $record;
        });

        if (!$this->mobileDeviceManager->deviceExists($notification->getDeviceId())) {
            $this->logger->info('device not found', $this->logHelper->getDefaultFailContext());

            return true;
        }

        try {
            if (
                (null !== ($deadline = $notification->getOptions()->getDeadlineTimestamp()))
                && (time() >= $deadline)
            ) {
                // out-of-date message
                $this->logger->error("will not send message, hit deadline", $this->logHelper->getDefaultFailContext());

                return true;
            }

            $this->logger->info('sending message to device');
            $handler = $this->getPlatformHandler($notification->getDeviceType());
            $platformMessage = $handler->prepareMessage($notification);
            $this->sender->send($platformMessage);
            $handler->checkResponses($notification, $this->sender->getResponses($platformMessage->getTargetOS()));
        } catch (\Throwable $e) {
            $this->handleException($e, $notification);
        } finally {
            $this->logger->popProcessor();
        }

        return true; // ACK
    }

    protected function handleException(\Throwable $e, Notification $notification)
    {
        if (
            ($e instanceof \Buzz\Exception\RequestException)
            && (
                ($e->getMessage() === 'SSL connection timeout')
                || (stripos($e->getMessage(), 'Unknown SSL protocol error in connection') !== false)
                || (stripos($e->getMessage(), 'Operation timed out after') !== false)
                || (stripos($e->getMessage(), 'Connection time-out') !== false)
            )
        ) {
            $this->logger->error(TraceProcessor::filterMessage($e), ['_aw_push_fail' => 1]);
            $this->notificationHelper->retry($notification);

            return;
        }

        throw $e;
    }

    private function getPlatformHandler(int $platformId): PlatformHandlerInterface
    {
        if (!isset($this->handlers[$platformId])) {
            $this->logger->error($text = sprintf('undefined platformId: %d', $platformId), $this->logHelper->getDefaultFailContext());

            throw new \RuntimeException($text);
        }

        return $this->handlers[$platformId];
    }
}
