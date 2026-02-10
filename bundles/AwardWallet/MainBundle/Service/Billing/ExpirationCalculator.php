<?php

namespace AwardWallet\MainBundle\Service\Billing;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem;
use AwardWallet\MainBundle\Globals\Cart\AwPlusUpgradableInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @psalm-type ExpirationResult = array{
 *   date: int|null,
 *   lastPrice: float|null,
 *   lastItemType: int|null
 * }
 */
class ExpirationCalculator
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @return ExpirationResult
     */
    public function getAccountExpiration($userID, $cartItemType = AwPlusUpgradableInterface::class): array
    {
        //	    if(empty(self::$accountExpirationQuery)) {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select(['ci'])
            ->from(Cart::class, 'c')
            ->join(CartItem::class, 'ci', 'WITH', 'c.cartid = ci.cart')
            ->where('c.user = :userid')
            ->andWhere('c.paydate IS NOT NULL')
            ->orderBy('c.paydate');

        $query = $qb->getQuery();
        //        }

        $query->setParameter('userid', $userID);

        $cartItems = $query->getResult();

        $result = [
            'date' => null,
            'lastPrice' => null,
            'lastItemType' => null,
        ];

        /** @var CartItem[] $cartItems */
        foreach ($cartItems as $cartItem) {
            if (
                $cartItem instanceof $cartItemType
                && (!$cartItem->isSubscription() || ($cartItem->isSubscription() && $cartItem->getPrice() > 0 && empty($cartItem->getScheduledDate())))
            ) {
                $d = $cartItem->getCart()->getPaydate()->getTimestamp();
                $dateRange = $cartItem->getDuration();

                if (!isset($result['date'])) {
                    $result['date'] = strtotime($dateRange, $d);
                } elseif ($cartItem instanceof ExpirationCalculatorInterface) {
                    $result['date'] = $cartItem->calcExpirationDate($result['date'], $cartItemType);
                }

                $result['lastPrice'] = $cartItem->getCart()->getTotalPrice();
                $result['lastItemType'] = $cartItem::TYPE;
            }
        }

        if (!isset($result['date'])) {
            $result['date'] = time();
            $result['lastPrice'] = null;
            $result['lastItemType'] = null;
        }

        return $result;
    }
}
