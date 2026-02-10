<?php

namespace AwardWallet\MainBundle\Service\Billing\UserPurchase;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Entity\Cart;

/**
 * @NoDI()
 */
class Info
{
    /**
     * @var Period[]
     */
    private array $periods;

    public function __construct(array $periods)
    {
        $this->periods = $periods;
    }

    /**
     * @param $now int|\DateTime|null
     */
    public function getCurrentPeriod($now = null): ?Period
    {
        $periods = $this->getCurrentAndNextPeriods($now);

        return $periods[0] ?? null;
    }

    /**
     * @param $now int|\DateTime|null
     */
    public function getCurrentExpirationDate($now = null): ?\DateTime
    {
        $period = $this->getCurrentAndNextPeriods($now);

        if (empty($period)) {
            return null;
        }

        $currentPeriod = $period[0];
        $lastPeriod = $period[count($period) - 1];

        if (!$currentPeriod->isGracePeriod() && $lastPeriod->isGracePeriod()) {
            array_pop($period);
        }

        return $period[count($period) - 1]->getEndDate();
    }

    /**
     * @param $now int|\DateTime|null
     */
    public function getCurrentExpirationDateTs($now = null): ?int
    {
        $date = $this->getCurrentExpirationDate($now);

        return $date ? $date->getTimestamp() : null;
    }

    /**
     * @param $now int|\DateTime|null
     */
    public function getSubscriptionPaymentCheckDate($now = null): ?\DateTime
    {
        $currentPeriod = $this->getCurrentPeriod($now);

        if (is_null($currentPeriod)) {
            return null;
        }

        if (
            !$currentPeriod->isGracePeriod()
            && $currentPeriod->isActive()
            && $currentPeriod->getCart()->isAwPlusSubscription()
        ) {
            return $currentPeriod->getEndDate();
        }

        return null;
    }

    /**
     * @param $now int|\DateTime|null
     */
    public function getSubscriptionPaymentCheckDateTs($now = null): ?int
    {
        $date = $this->getSubscriptionPaymentCheckDate($now);

        return $date ? $date->getTimestamp() : null;
    }

    /**
     * @param $now int|\DateTime|null
     * @return Period[]
     */
    public function getCurrentAndNextPeriods($now = null): array
    {
        if (is_null($now)) {
            $now = new \DateTime();
        } elseif (is_int($now)) {
            $now = new \DateTime('@' . $now);
        }

        $startIndex = null;

        foreach ($this->periods as $index => $period) {
            if ($period->getStartDate() <= $now && $period->getEndDate() > $now) {
                $startIndex = $index;

                break;
            }
        }

        if (is_null($startIndex)) {
            return [];
        }

        return array_slice($this->periods, $startIndex);
    }

    public function getLastPeriod(): ?Period
    {
        return $this->periods[count($this->periods) - 1] ?? null;
    }

    /**
     * @return Cart[]
     */
    public function getCarts(): array
    {
        $carts = [];

        foreach ($this->periods as $period) {
            if (is_null($period->getCart())) {
                continue;
            }

            $carts[] = $period->getCart();
        }

        return array_values(array_unique($carts));
    }

    public function getLastCart(): ?Cart
    {
        $carts = $this->getCarts();

        return $carts[count($carts) - 1] ?? null;
    }

    public function getLastPayment(): ?float
    {
        $lastCart = $this->getLastCart();

        return $lastCart ? $lastCart->getTotalPrice() : null;
    }
}
