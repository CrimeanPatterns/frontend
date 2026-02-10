<?php

namespace AwardWallet\MainBundle\Worker;

use AwardWallet\MainBundle\Entity\NotificationTemplate;
use AwardWallet\MainBundle\Service\ManagerSendNotification;
use Doctrine\Persistence\ManagerRegistry;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;

class ManagerSendNotificationWorker implements ConsumerInterface
{
    public const CACHE_KEY = 'manager_send_notification_worker';
    public const TIME_LIMIT = 60 * 60 * 24; // sec

    /** @var LoggerInterface */
    private $logger;

    /** @var ManagerRegistry */
    private $doctrine;

    /** @var \Memcached */
    private $memcache;

    /** @var \Doctrine\Persistence\ObjectManager */
    private $em;

    /** @var ManagerSendNotification */
    private $sendService;

    private $ntRep;

    public function __construct(ManagerRegistry $doctrine, \Memcached $memcache, ManagerSendNotification $sendService, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->doctrine = $doctrine;
        $this->memcache = $memcache;
        $this->sendService = $sendService;

        $this->sendService->setLoggerPrefix('ManagerSendNotificationWorker');
        //        $this->sendService->setTestMode(true);

        $this->em = $doctrine->getManager();
        $this->ntRep = $doctrine->getRepository(\AwardWallet\MainBundle\Entity\NotificationTemplate::class);
    }

    public function execute(AMQPMessage $message)
    {
        $this->em->clear();
        $task = @unserialize($message->body);

        if (empty($task) || !is_array($task)) {
            $this->error("broken task format", ['task' => $task]);

            return true;
        }
        $notificationId = $task['id'];

        $this->info("start sending", ['notificationId' => $notificationId, 'memory' => memory_get_usage(true)]);

        try {
            $notification = $this->ntRep->find($notificationId);
        } catch (\Exception $e) {
            $this->error($e->getMessage(), ['notificationId' => $notificationId]);
        }

        if (empty($notification)) {
            $this->error("not found Notification record", ['notificationId' => $notificationId]);

            return true;
        }

        if (!$this->lock($notificationId)) {
            $this->warning("locked by other worker", ['notificationId' => $notificationId]);

            return true;
        }

        if (!in_array($notification->getState(), [NotificationTemplate::STATE_SENDING])) {
            $this->error("not allowed Notification state", ['notificationId' => $notificationId]);

            return true;
        }

        $this->sendService->send($notification);

        $this->info("end sending", ['notificationId' => $notificationId, 'memory' => memory_get_usage(true)]);
        $this->unlock($notificationId);

        return true;
    }

    private function lock($notificationId)
    {
        $workers = $this->memcache->increment(self::CACHE_KEY . '_' . $notificationId, 1, 1, self::TIME_LIMIT);

        if ($workers > 1) {
            return false;
        }

        return true;
    }

    private function unlock($notificationId)
    {
        $this->memcache->delete(self::CACHE_KEY . '_' . $notificationId);
    }

    private function info($message, $extra = [])
    {
        $this->logger->info('ManagerSendNotificationWorker: ' . $message, $extra);
    }

    private function warning($message, $extra = [])
    {
        $this->logger->warning('ManagerSendNotificationWorker: ' . $message, $extra);
    }

    private function error($message, $extra = [])
    {
        $this->logger->error('ManagerSendNotificationWorker: ' . $message, $extra);
    }
}
