<?php

namespace AwardWallet\MainBundle\Entity\Repositories;

use Doctrine\ORM\EntityRepository;

class RewardsTransferRepository extends EntityRepository
{
    public function lookupAvailableTransfersForUser($userID)
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = '
			SELECT
				rt.RewardsTransferID AS "RewardsTransferID",
				rt.SourceProviderID AS "SourceProviderID",
				rt.TargetProviderID AS "TargetProviderID",
				rt.SourceRate AS "SourceRate",
				rt.TargetRate AS "TargetRate",
				a.AccountID AS "AccountID",
				a.Balance AS "Balance"
			FROM RewardsTransfer AS rt
			INNER JOIN
				Account AS a
					ON a.ProviderID = rt.SourceProviderID
					AND a.UserID = ?
			WHERE
				Balance IS NOT NULL
				AND rt.Enabled = 1
		';

        return $conn->executeQuery($sql, [$userID])->fetchAll();
    }
}
