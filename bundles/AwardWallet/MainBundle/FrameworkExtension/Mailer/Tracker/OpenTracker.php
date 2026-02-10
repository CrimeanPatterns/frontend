<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Tracker;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

class OpenTracker
{
    /**
     * @var LoggerInterface
     */
    private $mailLogger;
    /**
     * @var \Doctrine\DBAL\Statement
     */
    private $updateQuery;
    /**
     * @var \Doctrine\DBAL\Statement
     */
    private $contextQuery;

    public function __construct(Connection $connection, LoggerInterface $mailLogger)
    {
        $this->updateQuery = $connection->prepare("update SentEmail set 
            OpenCount = OpenCount + 1,
            FirstOpenDate = coalesce(FirstOpenDate, now()),
            LastOpenDate = now()
         where ID = ?");
        $this->contextQuery = $connection->prepare("select Context from SentEmail where ID = ?");
        $this->mailLogger = $mailLogger;
    }

    public function logOpen(string $trackingId, Request $request)
    {
        $this->updateQuery->execute([$trackingId]);
        $contextOnSend = [];
        $dbRecordExists = $this->updateQuery->rowCount() > 0;

        if ($dbRecordExists) {
            $this->contextQuery->execute([$trackingId]);
            $contextOnSend = json_decode($this->contextQuery->fetchColumn(), true);
        }
        // should we load context from database and log it ?
        $this->mailLogger->info("email opened", array_merge($contextOnSend, BrowserContextFactory::getContext($request), [
            "id" => bin2hex($trackingId),
            "database_record_exists" => $dbRecordExists,
        ]));
    }
}
