<?php

namespace AwardWallet\MainBundle\Worker\PushNotification;

use AwardWallet\MainBundle\Entity\MobileDevice;
use AwardWallet\MainBundle\Manager\MobileDeviceManager;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\DeviceAction;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\OutdatedClientException;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\OutdatedMessageException;
use Doctrine\DBAL\Connection;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;

class DeviceWorker implements ConsumerInterface
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var MobileDeviceManager
     */
    private $manager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(Connection $connection, MobileDeviceManager $manager, LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->manager = $manager;
        $this->logger = $logger;

        $this->logHelper = new LogHelper('device');
    }

    public function execute(AMQPMessage $msg)
    {
        try {
            $deviceAction = @unserialize($msg->body);
        } catch (OutdatedClientException $e) {
            $this->logger->error($e->getMessage(), $this->logHelper->getDefaultFailContext());

            throw $e;
        } catch (OutdatedMessageException $e) {
            $this->logger->error($e->getMessage(), $this->logHelper->getDefaultFailContext());

            return true;
        }

        if (!$deviceAction instanceof DeviceAction) {
            $this->logger->error(sprintf('device worker: invalid message, body "%s"', $msg->body), $this->logHelper->getDefaultFailContext());

            return true;
        }

        $notification = $deviceAction->getNotification();
        $logContext = $this->logHelper->getContext($notification, ['_aw_push_device_action' => DeviceAction::getActionName($deviceAction->getAction())]);
        $this->connection->executeQuery("select 1"); // reconnect to mysql, because delete would not reconnect

        switch ($deviceAction->getAction()) {
            case DeviceAction::RENEW:
                $newDeviceKey = $deviceAction->getData();

                if (!isset($newDeviceKey)) {
                    break;
                }
                $this->logger->info(sprintf('renewing deviceKey: from "%s" to "%s" for deviceId: "%d"', $notification->getDeviceKey(), $newDeviceKey, $notification->getDeviceId()), $logContext);
                $this->manager->renewDeviceKeyByNotification($notification, $newDeviceKey);

                break;

            case DeviceAction::REMOVE:
                $this->logger->info(sprintf('removing deviceId: "%d"', $notification->getDeviceId()), $logContext);
                $this->manager->removeDeviceById($notification->getDeviceId(), $notification->getDeviceType() !== MobileDevice::TYPE_IOS);

                break;

            case DeviceAction::QUIET:
                break;

            case DeviceAction::ERROR:
                // increase error counter
                break;
        }

        return true;
    }
}
