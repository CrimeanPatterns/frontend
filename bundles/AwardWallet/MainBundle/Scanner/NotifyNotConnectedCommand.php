<?php

namespace AwardWallet\MainBundle\Scanner;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Api\EmailScannerApi;
use AwardWallet\MainBundle\Service\ProgressLogger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class NotifyNotConnectedCommand extends Command
{
    public static $defaultName = 'aw:scanner:notify-not-connected';
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var EmailScannerApi
     */
    private $emailScannerApi;
    /**
     * @var Messenger
     */
    private $messenger;

    public function __construct(LoggerInterface $logger, EmailScannerApi $emailScannerApi, Messenger $messenger)
    {
        parent::__construct();
        $this->logger = $logger;
        $this->emailScannerApi = $emailScannerApi;
        $this->messenger = $messenger;
    }

    public function configure()
    {
        $this
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'max records')
            ->addOption('userId', null, InputOption::VALUE_REQUIRED, 'only this userId')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'dry ryn')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $pageToken = null;
        $mailboxes = [];
        $limit = $input->getOption('limit');
        $tags = null;
        $userId = $input->getOption('userId');
        $dryRun = $input->getOption('dry-run');

        if ($userId !== null) {
            $tags = ['user_' . $userId];
        }

        if ($dryRun) {
            $this->logger->info("dry run");
        }

        do {
            $this->logger->info("loading mailbox list from scanner, pageToken: {$pageToken}, loaded: " . count($mailboxes) . ", limit: " . json_encode($limit));
            $response = $this->emailScannerApi->scrollMailboxes($tags, ['error'], ['google', 'yahoo', 'microsoft', 'aol'],
                ['authentication'], $pageToken);
            $mailboxes = array_merge($mailboxes, $response->getItems());
            $pageToken = $response->getNextPageToken();
        } while ($response->getNextPageToken() !== null && ($limit === null || count($mailboxes) < $limit));

        if ($limit !== null) {
            $mailboxes = array_slice($mailboxes, 0, $limit);
        }

        $this->logger->info("loaded " . count($mailboxes) . " mailboxes");
        $sent = 0;
        $progress = new ProgressLogger($this->logger, 100, 30);
        $processed = 0;

        foreach ($mailboxes as $mailbox) {
            $progress->showProgress("checking mailboxes, processed: $processed, sent: $sent", $processed);

            if ($dryRun) {
                if ($this->messenger->wantToNotifyUser($mailbox)) {
                    $sent++;
                }
            } else {
                if ($this->messenger->notifyUser($mailbox)) {
                    $sent++;
                }
            }
            $processed++;
        }
        $this->logger->info("done, sent {$sent} messages");
    }
}
