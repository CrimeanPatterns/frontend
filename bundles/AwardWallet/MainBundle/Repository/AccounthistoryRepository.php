<?php

namespace AwardWallet\MainBundle\Repository;

use AwardWallet\MainBundle\Entity\AccountHistory;
use AwardWallet\MainBundle\FrameworkExtension\Repository;
use Doctrine\Persistence\ManagerRegistry;

class AccounthistoryRepository extends Repository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccountHistory::class);
    }

    public function getLastHistoryRowDateBySubAccount(int $subAccId): ?int
    {
        $connection = $this->getEntityManager()->getConnection();
        $stmt = $connection->executeQuery(
            " SELECT MAX(PostingDate) FROM AccountHistory WHERE SubAccountID = ?",
            [$subAccId],
            [\PDO::PARAM_INT]
        );
        $result = $stmt->fetch(\PDO::FETCH_COLUMN);

        return $result ? (new \DateTime($result))->getTimestamp() : null;
    }
}
