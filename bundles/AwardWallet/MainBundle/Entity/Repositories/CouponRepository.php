<?php

namespace AwardWallet\MainBundle\Entity\Repositories;

use AwardWallet\MainBundle\Entity\Usr;
use Doctrine\ORM\EntityRepository;

class CouponRepository extends EntityRepository
{
    public function getCouponsByCode($code)
    {
        $connection = $this->getEntityManager()->getConnection();
        $sql = "
		SELECT Coupon.CouponID, Coupon.Name, Coupon.Code, Coupon.Discount 
		FROM   Coupon 
		       LEFT OUTER JOIN Cart 
		         ON Coupon.Code = Cart.CouponCode 
		            AND Cart.PayDate IS NOT NULL 
		WHERE  Coupon.Code LIKE ?
		       AND Cart.CouponCode IS NULL 
		";
        $stmt = $connection->executeQuery($sql,
            [$code],
            [\PDO::PARAM_STR]
        );

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getFreeCouponsByUser($userID)
    {
        $connection = $this->getEntityManager()->getConnection();
        $sql = "
		SELECT * 
		FROM   Coupon 
		WHERE  Code LIKE 'free-%' 
		       AND UserID = ?
		";
        $stmt = $connection->executeQuery($sql,
            [$userID],
            [\PDO::PARAM_INT]
        );
        $coupons = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (sizeof($coupons) == 0) {
            return false;
        }

        $resultArray = [
            'code' => $coupons[0]['Code'],
            'count' => 0,
        ];
        $sql = "
		SELECT COUNT(*) AS Cnt 
		FROM   Cart 
		WHERE  PayDate IS NOT NULL 
		       AND CouponID = ?
		";
        $stmt = $connection->executeQuery($sql,
            [$coupons[0]['CouponID']],
            [\PDO::PARAM_INT]
        );
        $r = $stmt->fetch(\PDO::FETCH_ASSOC);
        $resultArray['count'] = max($coupons[0]['MaxUses'] - $r['Cnt'], 0);

        return $resultArray;
    }

    public function getInviteCouponByUser(Usr $user)
    {
        $connection = $this->_em->getConnection();
        $sql = "select count(Coupon.Code) as Cnt from Coupon where Coupon.Code like concat('Invite-', :userId, '-%')";

        return $connection->executeQuery($sql, ["userId" => $user->getUserid()])->fetchColumn() - $user->getInviteCouponsCorrection();
    }

    public function deleteCoupon($coupon)
    {
        $this->_em->remove($coupon);
        $this->_em->flush();
    }
}
