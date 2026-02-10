<?php

namespace AwardWallet\MainBundle\Entity\Repositories;

use AwardWallet\MainBundle\Entity\CartItem\OneCard;
use Doctrine\ORM\EntityRepository;

class OnecardRepository extends EntityRepository
{
    public function OneCardsCountByUser($userID)
    {
        $connection = $this->getEntityManager()->getConnection();
        $res = [
            "Total" => 0,
            "Used" => 0,
            "Left" => 0,
        ];
        $sql = "
		SELECT SUM(IF(ci.Cnt > 0,ci.Cnt,0)) Total 
		FROM   Usr u 
		       JOIN Cart c 
		         ON u.UserID = c.UserID 
		            AND c.PayDate IS NOT NULL 
		       LEFT JOIN CartItem ci 
		         ON ci.CartID = c.CartID 
		            AND ci.TypeID = ?
		WHERE  u.UserID = ?
		";
        $stmt = $connection->executeQuery($sql,
            [OneCard::TYPE, $userID],
            [\PDO::PARAM_INT, \PDO::PARAM_INT]
        );
        $r = $stmt->fetch(\PDO::FETCH_ASSOC);
        $res['Total'] = (int) $r['Total'];
        $sql = "
		SELECT COUNT(*) Used 
		FROM   (SELECT CartID, 
		               UserAgentID, 
		               COUNT(OneCardID) 
		        FROM   OneCard 
		        WHERE  UserID = ?
		               AND State <> ?
		        GROUP  BY CartID, 
		                  UserAgentID) cards 
		";
        $stmt = $connection->executeQuery($sql,
            [$userID, ONECARD_STATE_REFUNDED],
            [\PDO::PARAM_INT, \PDO::PARAM_INT]
        );
        $r = $stmt->fetch(\PDO::FETCH_ASSOC);
        $res['Used'] = (int) $r['Used'];
        $res['Left'] = $res['Total'] - $res['Used'];

        return $res;
    }
}
