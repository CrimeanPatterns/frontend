<?php

namespace AwardWallet\MainBundle\Entity\Repositories;

use AwardWallet\MainBundle\Entity\BonusConversion;
use Doctrine\ORM\EntityRepository;

/**
 * @template-extends EntityRepository<BonusConversion>
 */
class BonusConversionRepository extends EntityRepository
{
    public function getRedeemedBonusByUser($userId): int
    {
        $sql = "
   			SELECT
   				COALESCE(SUM(bc.Points), 0) AS Redeemed
   			FROM
   				BonusConversion bc
   			WHERE
   				bc.UserID = ?
   		";
        $stmt = $this->getEntityManager()->getConnection()->executeQuery($sql,
            [$userId],
            [\PDO::PARAM_INT]
        );
        $r = $stmt->fetch(\PDO::FETCH_ASSOC);

        return (int) $r['Redeemed'];
    }
}
