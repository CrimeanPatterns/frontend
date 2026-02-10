<?php

namespace AwardWallet\MainBundle\Service\Billing\UserPurchase;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\Cart\AT201SubscriptionInterface;
use AwardWallet\MainBundle\Globals\Cart\AwPlusUpgradableInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

/**
 * @NoDI()
 */
class History
{
    private Usr $user;

    /**
     * @var Cart[]
     */
    private array $carts;

    public function __construct(Usr $user)
    {
        $this->user = $user;
        $this->carts = it($user->getCarts()->toArray())
            ->filter(fn (Cart $cart) => !is_null($cart->getPaydate()))
            ->usort(function (Cart $a, Cart $b) {
                return $a->getPaydate() <=> $b->getPaydate();
            })
            ->toArray();
    }

    public function getUser(): Usr
    {
        return $this->user;
    }

    public function getAwPlusInfo(): Info
    {
        return $this->getPurchaseInfo(AwPlusUpgradableInterface::class);
    }

    public function getAT201Info(): Info
    {
        return $this->getPurchaseInfo(AT201SubscriptionInterface::class);
    }

    public static function makeAwPlusInfo(Usr $user): Info
    {
        return (new self($user))->getAwPlusInfo();
    }

    public static function makeAT201Info(Usr $user): Info
    {
        return (new self($user))->getAT201Info();
    }

    private function getPurchaseInfo(string $purchaseType, int $gracePeriodDays = 7): Info
    {
        /** @var Period[] $periods */
        $periods = [];
        $additionPeriods = [];

        foreach ($this->carts as $cart) {
            $targetItem = $cart->findItemByClass($purchaseType);

            if (
                is_null($targetItem)
                // we do not expect at201 subscriptions in mobile app
                || ($targetItem->isAwPlusSubscription() && ($targetItem->isScheduled() || $targetItem->getPrice() == 0))
            ) {
                continue;
            }

            $dateRange = $targetItem->getDuration();
            $startDateTs = $cart->getPaydate()->getTimestamp();
            $endDateTs = strtotime($dateRange, $startDateTs);
            $periods = array_values($periods);
            $countPeriods = count($periods);

            if ($countPeriods > 0) {
                $lastPeriod = $periods[$countPeriods - 1];

                if ($lastPeriod->isGracePeriod()) {
                    unset($periods[$countPeriods - 1]);
                    $periods = array_values($periods);
                    $countPeriods = count($periods);
                    $lastPeriod = $periods[$countPeriods - 1];
                }

                $lastPeriodEndDateTs = $lastPeriod->getEndDate()->getTimestamp();

                if ($startDateTs < $lastPeriodEndDateTs) {
                    $left = $lastPeriodEndDateTs - $startDateTs;
                    $lastPeriod->setEndDate(new \DateTime('@' . $startDateTs));

                    if (date('Y-m-d', $startDateTs) !== date('Y-m-d', $lastPeriodEndDateTs)) {
                        $additionPeriods[] = [$lastPeriod->getCart(), $left];
                    }
                }
            }

            $periods[] = new Period(
                new \DateTime('@' . $startDateTs),
                new \DateTime('@' . $endDateTs),
                $cart
            );

            if ($targetItem->isAwPlusSubscription()) {
                $periods[] = new Period(
                    new \DateTime('@' . $endDateTs),
                    new \DateTime('@' . strtotime('+' . $gracePeriodDays . ' days', $endDateTs))
                );
            }
        }

        if (count($additionPeriods) > 0 && count($periods) > 0) {
            $lastPeriod = $periods[count($periods) - 1];

            if ($lastPeriod->isGracePeriod()) {
                unset($periods[count($periods) - 1]);
                $periods = array_values($periods);
                $lastPeriod = $periods[count($periods) - 1];
            }

            $startDateTs = $lastPeriod->getEndDate()->getTimestamp();

            foreach ($additionPeriods as [$cart, $left]) {
                $periods[] = new Period(
                    new \DateTime('@' . $startDateTs),
                    new \DateTime('@' . ($startDateTs + $left)),
                    $cart,
                    false
                );

                $startDateTs += $left;
            }
        }

        return new Info($periods);
    }
}
