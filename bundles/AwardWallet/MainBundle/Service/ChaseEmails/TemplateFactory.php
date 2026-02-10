<?php

namespace AwardWallet\MainBundle\Service\ChaseEmails;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Psr\Log\LoggerInterface;

class TemplateFactory
{
    private Connection $connection;

    private LoggerInterface $logger;

    public function __construct(Connection $connection, LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->logger = $logger;
    }

    /**
     * @return array [<CardID> => ['Template1', 'Temmplate2', ..], <CardID2> => [...
     */
    public function getEnabledTemplates(): array
    {
        $enabledTemplates = $this->connection->executeQuery("select Template from CreditCardEmail where Enabled = 1")->fetchAll(FetchMode::COLUMN);
        $result = array_map(function (array $templates) use ($enabledTemplates): array {
            return array_intersect($enabledTemplates, $templates);
        }, Constants::CARD_TEMPLATES);
        $result = array_filter($result, function (array $templates) {
            return count($templates) > 0;
        });
        $this->logger->info("enabled templates: " . json_encode($result));

        return $result;
    }
}
