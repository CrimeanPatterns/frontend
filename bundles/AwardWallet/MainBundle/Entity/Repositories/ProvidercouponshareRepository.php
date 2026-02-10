<?php

namespace AwardWallet\MainBundle\Entity\Repositories;

use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Entity\Providercouponshare;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use Doctrine\ORM\EntityRepository;

class ProvidercouponshareRepository extends EntityRepository
{
    public function addProviderCouponShare(Providercoupon $coupon, Useragent $userAgent)
    {
        $share = $this->findOneBy(['providercouponid' => $coupon, 'useragentid' => $userAgent]);

        if (empty($share)) {
            $em = $this->getEntityManager();
            $couponShare = new Providercouponshare();
            $couponShare->setUseragentid($userAgent);
            $couponShare->setProvidercouponid($coupon);
            $em->persist($couponShare);
            $em->flush();
        }
    }

    public function addShare(Providercoupon $coupon, Useragent $userAgent)
    {
        $this->addProviderCouponShare($coupon, $userAgent);
    }

    public function removeSharedCoupon(Providercoupon $coupon, Useragent $userAgent)
    {
        $share = $this->findOneBy(['providercouponid' => $coupon, 'useragentid' => $userAgent]);

        if ($share) {
            $em = $this->getEntityManager();
            $em->remove($share);
            $em->flush();

            return true;
        }

        return false;
    }

    public function removeShare(Providercoupon $coupon, Useragent $userAgent)
    {
        $this->removeSharedCoupon($coupon, $userAgent);
    }

    public function removeShareWithUser(Providercoupon $coupon, Usr $user)
    {
        $userAgent = $this->getEntityManager()->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class)->findOneBy(['agentid' => $user, 'clientid' => $coupon->getUserid()]);

        if ($userAgent !== null) {
            return $this->removeSharedCoupon($coupon, $userAgent);
        } else {
            return false;
        }
    }

    public function shareCoupons($couponIds, Useragent $userAgent)
    {
        $em = $this->getEntityManager();
        $connection = $em->getConnection();

        $shared = $connection->executeQuery("SELECT ProviderCouponID FROM ProviderCouponShare WHERE ProviderCouponID in (?) and UserAgentID = ?",
            [$couponIds, $userAgent->getUseragentid()],
            [\Doctrine\DBAL\Connection::PARAM_INT_ARRAY, \PDO::PARAM_INT]
        )->fetchAll(\PDO::FETCH_ASSOC);
        $shared = array_map(function ($v) {return $v['ProviderCouponID']; }, $shared);
        $couponIds = array_filter($couponIds, function ($v) use ($shared) { return !in_array($v, $shared); });

        $smt = $connection->prepare("INSERT INTO ProviderCouponShare(ProviderCouponID, UserAgentID) values(?, ?)");

        foreach ($couponIds as $couponId) {
            $smt->execute([$couponId, $userAgent->getUseragentid()]);
        }
    }
}
