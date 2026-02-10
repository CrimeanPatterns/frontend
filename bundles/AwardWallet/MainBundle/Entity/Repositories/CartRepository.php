<?php

namespace AwardWallet\MainBundle\Entity\Repositories;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem;
use AwardWallet\MainBundle\Entity\CartItem\AT201Subscription1Month;
use AwardWallet\MainBundle\Entity\CartItem\AT201Subscription1Year;
use AwardWallet\MainBundle\Entity\CartItem\AT201Subscription6Months;
use AwardWallet\MainBundle\Entity\CartItem\AwPlus;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusRecurring;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription6Months;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusWeekSubscription;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\Cart\AT201SubscriptionInterface;
use AwardWallet\MainBundle\Globals\Cart\AwPlusUpgradableInterface;
use Doctrine\ORM\EntityRepository;

/**
 * @template-extends EntityRepository<Cart>
 */
class CartRepository extends EntityRepository
{
    public function getNumberOfUses($coupon)
    {
        return $this->createQueryBuilder('c')
            ->select('count(c)')
            ->where('c.coupon = :id')
            ->andWhere('c.paydate is not null')
            ->setParameter('id', $coupon)
            ->getQuery()->getSingleScalarResult();
    }

    /**
     * @return Cart|null
     */
    public function getActiveAwSubscription(Usr $user, bool $onlyActiveSubscription = true, bool $includeMissingPayments = false)
    {
        $conn = $this->getEntityManager()->getConnection();
        $stmt = $conn->executeQuery("
            SELECT
                c.CartID
            FROM
                Cart c
                JOIN CartItem ci ON c.CartID = ci.CartID
                JOIN Usr u ON u.UserID = c.UserID
            WHERE
                c.UserID = :userId
                AND c.PayDate IS NOT NULL
                AND (
                
                    # Mobile subscriptions
                    (
                        c.PaymentType IN (:mobilePaymentTypes)
                        " . ($onlyActiveSubscription ? "AND u.Subscription in(" . implode(", ", [Usr::SUBSCRIPTION_MOBILE]) . ")" : "") . "
                        AND (
                        
                            (
                                (ci.TypeID = :awSubscr1Year AND PayDate > (NOW() - INTERVAL " . str_replace("+", "", AwPlusSubscription::DURATION) . "))
                                OR (ci.TypeID = :awSubscr1Week AND PayDate > (NOW() - INTERVAL " . str_replace("+", "", AwPlusWeekSubscription::DURATION) . "))
                            )
                            
                            OR
                            
                            (
                                u.AccountLevel = :accountLevel
                                AND (
                                    (ci.TypeID = :awSubscr1Year" . ($includeMissingPayments ? "" : " AND PayDate > (NOW() - INTERVAL " . str_replace("+", "", AwPlusSubscription::DURATION) . " - INTERVAL 7 DAY)") . ") 
                                    OR (ci.TypeID = :awSubscr1Week" . ($includeMissingPayments ? "" : " AND PayDate > (NOW() - INTERVAL " . str_replace("+", "", AwPlusWeekSubscription::DURATION) . " - INTERVAL 7 DAY)") . ")
                                )
                            )
                        
                        )
                        
                    )
                    
                    OR
                    
                    # Desktop subscriptions
                    (
                        c.PaymentType NOT IN (:mobilePaymentTypes)
                        " . ($onlyActiveSubscription ? "AND u.Subscription not in(" . implode(", ", [Usr::SUBSCRIPTION_MOBILE]) . ")" : "") . "
                        AND c.PaymentType <> " . PAYMENTTYPE_BUSINESS_BALANCE . "
                        AND ci.TypeID IN (:desktopItemTypes)
                        " . ($onlyActiveSubscription ? " AND u.Subscription IS NOT NULL" : "") . "
                    )
                
                )
            ORDER BY c.PayDate DESC, c.CartID DESC
            LIMIT 1
        ", [
            ':userId' => $user->getUserid(),
            ':mobilePaymentTypes' => [Cart::PAYMENTTYPE_APPSTORE, Cart::PAYMENTTYPE_ANDROIDMARKET],
            ':awSubscr1Year' => AwPlusSubscription::TYPE,
            ':awSubscr1Week' => AwPlusWeekSubscription::TYPE,
            ':accountLevel' => ACCOUNT_LEVEL_AWPLUS,
            ':desktopItemTypes' => [
                AwPlusSubscription::TYPE, AwPlusWeekSubscription::TYPE, AwPlusRecurring::TYPE, AwPlusSubscription6Months::TYPE,
                AT201Subscription1Month::TYPE, AT201Subscription6Months::TYPE, AT201Subscription1Year::TYPE,
                AwPlus::TYPE,
            ],
        ], [
            ':userId' => \PDO::PARAM_INT,
            ':mobilePaymentTypes' => \Doctrine\DBAL\Connection::PARAM_INT_ARRAY,
            ':awSubscr1Year' => \PDO::PARAM_INT,
            ':awSubscr1Week' => \PDO::PARAM_INT,
            ':accountLevel' => \PDO::PARAM_INT,
            ':desktopItemTypes' => \Doctrine\DBAL\Connection::PARAM_INT_ARRAY,
        ]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return $this->find($row['CartID']);
    }

    public function getLastAwPlusCart(Usr $user)
    {
        $cartItems = $this->getPayedCarts($user);

        /** @var CartItem[] $cartItems */
        foreach ($cartItems as $cartItem) {
            if (
                $cartItem instanceof AwPlusUpgradableInterface
                && (!$cartItem->isAwPlusSubscription() || ($cartItem->isAwPlusSubscription() && $cartItem->getPrice() > 0))
            ) {
                return $cartItem->getCart();
            }
        }

        return null;
    }

    public function getLastAT201Cart(Usr $user): ?Cart
    {
        $cartItems = $this->getPayedCarts($user);

        /** @var CartItem[] $cartItems */
        foreach ($cartItems as $cartItem) {
            if ($cartItem instanceof AT201SubscriptionInterface) {
                return $cartItem->getCart();
            }
        }

        return null;
    }

    /**
     * @return iterable<CartItem>
     */
    public function getPayedCarts(Usr $user): iterable
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $qb->select(['ci'])
            ->from(Cart::class, 'c')
            ->join(CartItem::class, 'ci', 'WITH', 'c.cartid = ci.cart')
            ->where('c.user = :userid')
            ->andWhere('c.paydate IS NOT NULL')
            ->orderBy('c.paydate', 'DESC')
            ->setParameter('userid', $user->getId());

        return $qb->getQuery()->getResult();
    }
}
