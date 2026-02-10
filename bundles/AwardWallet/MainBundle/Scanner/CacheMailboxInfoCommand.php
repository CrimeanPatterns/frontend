<?php

namespace AwardWallet\MainBundle\Scanner;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Api\EmailScannerApi;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Model\Mailbox;
use AwardWallet\MainBundle\Service\ProgressLogger;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CacheMailboxInfoCommand extends Command
{
    public static $defaultName = 'aw:scanner:cache-mailbox-info';
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var EmailScannerApi
     */
    private $emailScannerApi;
    /**
     * @var Connection
     */
    private $unbufConnection;
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(
        LoggerInterface $logger,
        EmailScannerApi $emailScannerApi,
        Connection $unbufConnection,
        Connection $connection
    ) {
        parent::__construct();
        $this->logger = $logger;
        $this->emailScannerApi = $emailScannerApi;
        $this->unbufConnection = $unbufConnection;
        $this->connection = $connection;
    }

    public function configure()
    {
        $this
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'max records');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $pageToken = null;
        $usersWithValidMailboxes = [];
        $limit = $input->getOption('limit');

        do {
            $this->logger->info("loading mailbox list from scanner, pageToken: {$pageToken}, loaded: " . count($usersWithValidMailboxes));
            $response = $this->emailScannerApi->scrollMailboxes(null, ['listening'], null, null, $pageToken);

            foreach ($response->getItems() as $mailbox) {
                if ($userId = $this->getMailboxUserId($mailbox)) {
                    $usersWithValidMailboxes[$userId] = ($usersWithValidMailboxes[$userId] ?? 0) + 1;
                }
            }
            $pageToken = $response->getNextPageToken();
        } while ($response->getNextPageToken() !== null && ($limit === null || count($usersWithValidMailboxes) < $limit));

        if ($limit !== null) {
            $usersWithValidMailboxes = array_slice($usersWithValidMailboxes, 0, $limit);
        }

        $this->logger->info("loaded " . count($usersWithValidMailboxes) . " valid mailboxes");

        $this->updateUserMailboxes($usersWithValidMailboxes);

        $this->logger->info("done");
    }

    private function getMailboxUserId(Mailbox $mailbox): ?int
    {
        $data = @json_decode($mailbox->getUserData(), true);

        if (!isset($data['user'])) {
            // we now have non-user mailboxes
            return null;
            // throw new \Exception("failed to detect user id, mailbox: {$mailbox->getId()}, tags: " . implode(", ", $mailbox->getTags()));
        }

        return $data['user'];
    }

    private function updateUserMailboxes(array $usersWithValidMailboxes): void
    {
        $this->logger->info("updating users with mailbox count");
        $updateQ = $this->connection->prepare("update Usr set ValidMailboxesCount = :count where UserID = :userId");
        $q = $this->unbufConnection->executeQuery("select UserID, ValidMailboxesCount from Usr");
        $progress = new ProgressLogger($this->logger, 100, 30);
        $processed = 0;
        $updated = 0;

        while ($row = $q->fetch(FetchMode::ASSOCIATIVE)) {
            $progress->showProgress("updating mailboxes info, processed {$processed}, updated {$updated}..", $processed);
            $validMailboxesCount = $usersWithValidMailboxes[$row['UserID']] ?? 0;

            if ($validMailboxesCount !== (int) $row['ValidMailboxesCount']) {
                $updateQ->execute(["count" => $validMailboxesCount, "userId" => $row['UserID']]);
                $updated++;
            }
            $processed++;
        }
        $this->logger->info("processed {$processed} mailboxes, updated: {$updated}");
    }
}
