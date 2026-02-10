<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Service\AppBot\Adapter\Slack;
use AwardWallet\MainBundle\Service\AppBot\AppBot;
use AwardWallet\MainBundle\Service\Charts\QsTransactionChart;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Api\EmailScannerApi;
use AwardWallet\MainBundle\Service\Statistics;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DailyHeartbeatCommand extends Command
{
    protected static $defaultName = 'aw:daily-heartbeat';

    private LoggerInterface $logger;
    private AppBot $appBot;
    private Statistics $statistics;
    private QsTransactionChart $qsTransactionChart;
    private EmailScannerApi $emailScannerApi;
    private EntityManagerInterface $entityManager;

    private bool $isAllNotify = false;

    public function __construct(
        LoggerInterface $logger,
        AppBot $appBot,
        Statistics $statistics,
        QsTransactionChart $qsTransactionChart,
        EmailScannerApi $emailScannerApi,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct();
        $this->logger = $logger;
        $this->appBot = $appBot;
        $this->statistics = $statistics;
        $this->qsTransactionChart = $qsTransactionChart;
        $this->emailScannerApi = $emailScannerApi;
        $this->entityManager = $entityManager;
    }

    protected function configure()
    {
        $this
            ->setDescription('Daily business heartbeat message to Slack')
            ->addOption('beta', null, InputOption::VALUE_NONE, 'beta only')
            ->addOption('day', null, InputOption::VALUE_REQUIRED, 'calc this day')
            ->addOption('notify', null, InputOption::VALUE_NONE, 'send notification to aw_all');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->isAllNotify = !empty($input->getOption('notify'));

        if (!empty($input->getOption('day'))) {
            $this->statistics->setDay(new \DateTime($input->getOption('day')));
        } else {
            $this->isAllNotify = true;
        }

        if (!$input->getOption('beta')) {
            $this->statistics->extendMessages['mailboxes'] = $this->getMailboxUsers();
            $this->statistics->fetchAll();

            $this->appBot->send(
                $this->isAllNotify ? Slack::CHANNEL_AW_ALL : Slack::CHANNEL_AW_STATS,
                $this->statistics->getStats()
            );
        }
        /*
        $this->logger->info('sending to beta');
        $this->appBot->send(Slack::CHANNEL_AW_STATS, $this->statistics->fetchAffiliateQsTransaction());
        $this->sendQsTransactionCharts();
        */

        return 0;
    }

    private function getMailboxUsers()
    {
        [$filterDateAfter, $filterDateBefore] = $this->statistics->getDateFilter();

        $registeredUsersId = $this->entityManager->getConnection()->fetchFirstColumn('
            SELECT UserID
            FROM Usr
            WHERE
                CreationDateTime BETWEEN ' . $this->entityManager->getConnection()->quote($filterDateAfter->format('Y-m-d H:i:s')) . '
                                     AND ' . $this->entityManager->getConnection()->quote($filterDateBefore->format('Y-m-d H:i:s'))
        );

        $pageToken = null;
        $usersWithValidMailboxes = [];

        do {
            $response = $this->emailScannerApi->scrollMailboxes(null, ['listening'], null, null, $pageToken);

            foreach ($response->getItems() as $mailbox) {
                $data = json_decode($mailbox->getUserData(), true);

                if (!isset($data['user'])) {
                    continue;
                }

                $userId = (int) $data['user'];
                $creationDate = new \DateTime($mailbox->getCreationDate());

                if (!in_array($userId, $registeredUsersId)
                    || $creationDate->getTimestamp() < $filterDateAfter->getTimestamp()
                    || $creationDate->getTimestamp() > $filterDateBefore->getTimestamp()
                ) {
                    continue;
                }

                $usersWithValidMailboxes[$userId] = ($usersWithValidMailboxes[$userId] ?? 0) + 1;
            }

            $pageToken = $response->getNextPageToken();
        } while (null !== $response->getNextPageToken());

        return $usersWithValidMailboxes;
    }

    /*
        private function sendQsTransactionCharts() : void
        {
            $date = $this->qsTransactionChart->fetchDate();
            $prevMonth = new \DateTime('@' . strtotime('first day of last month'));

            $clickGraph = $this->qsTransactionChart->getClicksGraph();
            if (empty($clickGraph)) {
                $date = $prevMonth;
                $clickGraph = $this->qsTransactionChart->getClicksGraph($prevMonth);
            }
            if (!empty($clickGraph)) {
                $clicks_tempFile = tempnam(sys_get_temp_dir(), 'qsCharts');
                $clickGraph->Stroke($clicks_tempFile);
                $clicksUpload = $this->appBot->uploadFile($clicks_tempFile);
            }

            $revenueGraph = $this->qsTransactionChart->getRevenueGraph();
            if (empty($revenueGraph)) {
                $revenueGraph = $this->qsTransactionChart->getRevenueGraph($prevMonth);
            }
            if (!empty($revenueGraph)) {
                $revenue_tempFile = tempnam(sys_get_temp_dir(), 'qsCharts');
                $revenueGraph->Stroke($revenue_tempFile);
                $revenueUpload = $this->appBot->uploadFile($revenue_tempFile);
            }

            $cardsGraph = $this->qsTransactionChart->getCardsGraph();
            if (empty($cardsGraph)) {
                $cardsGraph = $this->qsTransactionChart->getCardsGraph($prevMonth);
            }
            if (!empty($cardsGraph)) {
                $cards_tempFile = tempnam(sys_get_temp_dir(), 'qsCharts');
                $cardsGraph->Stroke($cards_tempFile);
                $cardsUpload = $this->appBot->uploadFile($cards_tempFile);
            }

            $attachments = [];

            if (isset($clicksUpload['success']) && $clicksUpload['success']) {
                $attachments[] = [
                    'color' => QsTransactionChart::COLORS[QsTransaction::ACCOUNT_DIRECT],
                    'title' => $date->format('F Y') . ' - Credit Card Total Clicks per Day',
                    'title_link' => 'https://awardwallet.com/manager/list.php?Schema=Qs_Transaction',
                    'image_url' => $clicksUpload['publicUrl'],
                ];
            }
            if (isset($revenueUpload['success']) && $revenueUpload['success']) {
                $attachments[] = [
                    'color' => QsTransactionChart::COLORS[QsTransaction::ACCOUNT_AWARDTRAVEL101],
                    'title' => $date->format('F Y') . ' - Credit Card Revenue per Day',
                    'title_link' => 'https://awardwallet.com/manager/list.php?Schema=Qs_Transaction',
                    'image_url' => $revenueUpload['publicUrl'],
                ];
            }
            if (isset($cardsUpload['success']) && $cardsUpload['success']) {
                $attachments[] = [
                    'color' => QsTransactionChart::COLORS[QsTransaction::ACCOUNT_CARDRATINGS],
                    'title' => $date->format('F Y') . ' - Card Breakdown',
                    'title_link' => 'https://awardwallet.com/manager/list.php?Schema=Qs_Transaction',
                    'image_url' => $cardsUpload['publicUrl'],
                ];
            }

            if (!empty($attachments)) {
                $this->appBot->send(Slack::CHANNEL_AW_STATS, [
                    'attachments' => $attachments,
                ]);
            }

            isset($clicks_tempFile) ? unlink($clicks_tempFile) : null;
            isset($revenue_tempFile) ? unlink($revenue_tempFile) : null;
            isset($cards_tempFile) ? unlink($cards_tempFile) : null;
        }
    */
}
