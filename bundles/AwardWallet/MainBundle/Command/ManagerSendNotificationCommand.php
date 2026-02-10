<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Entity\NotificationTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\Service\ManagerSendNotification;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ManagerSendNotificationCommand extends Command
{
    public const CACHE_KEY = 'manager_send_notification_worker';
    public const TIME_LIMIT = 30; // sec
    protected static $defaultName = 'aw:manager-send-notification';

    private LoggerInterface $logger;
    private \Memcached $memcache;
    private EntityManagerInterface $entityManager;
    private ManagerSendNotification $managerSendNotification;

    public function __construct(
        LoggerInterface $logger,
        \Memcached $memcache,
        EntityManagerInterface $entityManager,
        ManagerSendNotification $managerSendNotification
    ) {
        parent::__construct();
        $this->logger = $logger;
        $this->memcache = $memcache;
        $this->entityManager = $entityManager;
        $this->managerSendNotification = $managerSendNotification;
    }

    protected function configure()
    {
        $this
            ->setDescription('Send Notifications')
            ->addOption('notificationId', 'i', InputOption::VALUE_REQUIRED, 'Notification Template ID')
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'dry-run, do not do anything');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var EntityManagerInterface $em */
        $em = $this->entityManager;
        $ntRep = $em->getRepository(\AwardWallet\MainBundle\Entity\NotificationTemplate::class);
        $sendService = $this->managerSendNotification;
        $sendService->setLoggerPrefix('ManagerSendNotificationCommand');
        $sendService->setEntityManager($em);

        $notificationId = intval($input->getOption("notificationId"));

        if (!$notificationId) {
            $this->logger->notice("notification id required");

            return 0;
        }

        $this->logger->info("start sending: " . $notificationId . ", memory: " . memory_get_usage(true));

        try {
            $notification = $ntRep->find($notificationId);
        } catch (\Exception $e) {
            $this->logger->notice($e->getMessage());

            return 0;
        }

        if (empty($notification)) {
            $this->logger->notice("not found Notification record: " . $notificationId);

            return 0;
        }

        if (!$this->lock($notificationId)) {
            $this->logger->notice("locked by other worker: " . $notificationId);

            return 0;
        }

        if (!in_array($notification->getState(), [NotificationTemplate::STATE_NEW, NotificationTemplate::STATE_TESTED, NotificationTemplate::STATE_SENDING])) {
            $this->logger->notice("not allowed Notification state: " . $notificationId);

            return 0;
        }

        $dryRun = !empty($input->getOption('dry-run'));

        if ($dryRun) {
            $this->logger->debug("dry run");
            $sendService->setTestMode(true);
        }

        $sendService->send($notification);

        $this->logger->info("end sending: " . $notificationId . ", memory: " . memory_get_usage(true));
        $this->unlock($notificationId);

        return 0;
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
}
