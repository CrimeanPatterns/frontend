<?php

namespace AwardWallet\MainBundle\Entity\Listener;

use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Entity\Providercouponshare;
use AwardWallet\MainBundle\Service\Counter;
use Doctrine\ORM\Event\LifecycleEventArgs;

class ProvidercouponListener
{
    /**
     * @var Counter
     */
    private $counter;

    /**
     * ProvidercouponListener constructor.
     */
    public function __construct(Counter $counter)
    {
        $this->counter = $counter;
    }

    public function postUpdate(Providercoupon $coupon, LifecycleEventArgs $event)
    {
        $changeSet = $event->getEntityManager()->getUnitOfWork()->getEntityChangeSet($coupon);

        if (isset($changeSet['account'])) {
            $this->counter->invalidateTotalAccountsCounter($coupon->getUserid()->getUserid());

            foreach ($coupon->getUseragents() as $useragent) {
                $this->counter->invalidateTotalAccountsCounter($useragent->getAgentid()->getUserid());
            }
        }

        if (isset($changeSet['user'])) {
            if ($coupon->getUser()->isBusiness()) {
                $query = $event->getObjectManager()->createQueryBuilder();
                $query->delete(Providercouponshare::class, 's')
                    ->where($query->expr()->eq('s.providercouponid', $coupon->getProvidercouponid()));
                $query->getQuery()->execute();
            }
        }
    }
}
