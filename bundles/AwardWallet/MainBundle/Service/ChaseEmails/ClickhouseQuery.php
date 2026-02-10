<?php

namespace AwardWallet\MainBundle\Service\ChaseEmails;

use AwardWallet\MainBundle\Entity\Repositories\ParameterRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Psr\Log\LoggerInterface;

class ClickhouseQuery
{
    private LoggerInterface $logger;

    private Connection $clickhouse;

    private ParameterRepository $parameterRepository;

    public function __construct(LoggerInterface $logger, Connection $clickhouse, ParameterRepository $parameterRepository)
    {
        $this->logger = $logger;
        $this->clickhouse = $clickhouse;
        $this->parameterRepository = $parameterRepository;
    }

    /**
     * @param array $cardIds - array of CreditCardID from CreditCard table
     * @return array - [<UserID1> => [<CreditCardID1>, <CreditCardID2>, ..], <UserID2> => [ ..
     */
    public function getUsersOfCards(array $cardIds, ?int $userId = null): array
    {
        $this->logger->info("loading card users from clickhouse");
        $dbName = sprintf("awardwallet_v%s", $this->parameterRepository->getParam(ParameterRepository::CLICKHOUSE_DB_VERSION));

        $userFilter = "";

        if ($userId !== null) {
            $userFilter = "and a.UserID = " . (int) $userId;
        }

        $rows = $this->clickhouse->executeQuery("
        select distinct 
            UserID,
            CreditCardID
        from (
            select 
                a.UserID,
                sa.CreditCardID
            from
                {$dbName}.SubAccount sa
                join {$dbName}.Account a on sa.AccountID = a.AccountID 
            where 
                sa.CreditCardID in (" . implode(", ", $cardIds) . ")
                {$userFilter}
                
            union all
            
            select
                a.UserID,
                h.CreditCardID
            from
                {$dbName}.AccountHistory h
                join {$dbName}.SubAccount sa on h.SubAccountID = sa.SubAccountID 
                join {$dbName}.Account a on sa.AccountID = a.AccountID
            where 
                h.CreditCardID in (" . implode(", ", $cardIds) . ")
                {$userFilter}
                
            union all
            
            select
                a.UserID,
                dc.CreditCardID
            from
                {$dbName}.DetectedCards dc
                join {$dbName}.Account a on dc.AccountID = a.AccountID 
            where 
                dc.CreditCardID in (" . implode(", ", $cardIds) . ")
                {$userFilter}
        ) Matches
        ")->fetchAll(FetchMode::ASSOCIATIVE);
        $this->logger->info("got " . count($rows) . " rows from clickhouse");

        if (count($rows) === 0) {
            throw new \Exception("No card users, empty clickhouse?");
        }

        $result = [];

        foreach ($rows as $row) {
            $userId = (int) $row['UserID'];

            if (!isset($result[$userId])) {
                $result[$userId] = [];
            }
            $result[$userId][] = (int) $row['CreditCardID'];
        }

        $this->logger->info("loaded " . count($result) . " users with cards");

        return $result;
    }
}
