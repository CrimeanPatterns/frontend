<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Tracker;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

class ClickTracker
{
    protected const VALID_TRACKING_ID_REGEXP = '#^[a-z\d]{32}$#ims';

    /**
     * @var \Doctrine\DBAL\Statement
     */
    private $updateQuery;
    /**
     * @var \Doctrine\DBAL\Statement
     */
    private $contextQuery;
    /**
     * @var LoggerInterface
     */
    private $mailLogger;
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection, LoggerInterface $mailLogger)
    {
        $this->mailLogger = $mailLogger;
        $this->connection = $connection;
    }

    public function trackClick(string $binaryTrackingId, string $url, Request $request): void
    {
        if ($this->updateQuery === null) {
            $this->updateQuery = $this->connection->prepare("update SentEmail set 
            ClickCount = ClickCount + 1,
            FirstClickDate = coalesce(FirstClickDate, now()),
            LastClickDate = now()
         where ID = ?");
        }

        $this->updateQuery->execute([$binaryTrackingId]);
        $dbRecordExists = $this->updateQuery->rowCount() > 0;
        $contextOnSend = [];

        if ($dbRecordExists) {
            if ($this->contextQuery === null) {
                $this->contextQuery = $this->connection->prepare("select Context from SentEmail where ID = ?");
            }
            $this->contextQuery->execute([$binaryTrackingId]);
            $contextOnSend = json_decode($this->contextQuery->fetchColumn(), true);
        }
        // should we load context from database and log it ?
        $this->mailLogger->info("email clicked", array_merge($contextOnSend, BrowserContextFactory::getContext($request), [
            "id" => bin2hex($binaryTrackingId),
            "database_record_exists" => $dbRecordExists,
            "url" => $url,
        ]));
    }

    public function trackClickUnencodedId(string $unencodedTrackingId, string $url, Request $request): void
    {
        $this->trackClick(@\hex2bin($unencodedTrackingId), $url, $request);
    }

    public static function isValidTrackingId(string $id): bool
    {
        return (bool) \preg_match(self::VALID_TRACKING_ID_REGEXP, $id);
    }
}
