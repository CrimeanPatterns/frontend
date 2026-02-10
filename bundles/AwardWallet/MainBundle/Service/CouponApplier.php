<?php

namespace AwardWallet\MainBundle\Service;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem;
use AwardWallet\MainBundle\Entity\CartItem\Discount;
use AwardWallet\MainBundle\Entity\CartItem\OneCard;
use AwardWallet\MainBundle\Entity\CartItem\PlusItems;
use AwardWallet\MainBundle\Entity\Coupon;
use AwardWallet\MainBundle\Entity\CouponItem;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\Cart\Manager;
use AwardWallet\MainBundle\Globals\Cart\SubscriptionPeriod;
use AwardWallet\MainBundle\Globals\Cart\SubscriptionPrice;
use Doctrine\ORM\EntityManagerInterface;

class CouponApplier
{
    private EntityManagerInterface $entityManager;

    private Manager $cartManager;

    public function __construct(EntityManagerInterface $entityManager, Manager $cartManager)
    {
        $this->entityManager = $entityManager;
        $this->cartManager = $cartManager;
    }

    public function applyCouponToCart(Coupon $coupon, Cart $cart): void
    {
        $isFirstTimeCoupon = $coupon->getFirsttimeonly();

        if ($isFirstTimeCoupon) {
            $trialCart = $cart->getUser()->getCarts()->filter(function ($cart) {
                /** @var Cart $cart */
                return $cart->isPaid() && $cart->hasItemsByType(CartItem::TRIAL_TYPES);
            });

            if ($trialCart->count() === 1) {
                $this->cartManager->refund($trialCart->first());
            } else {
                // why we do not apply coupon when there is no trial ?
                // return;
            }
        }

        $startSubscriptionDate = date_create();

        foreach ($coupon->getItems() as $couponItem) {
            /** @var CouponItem $couponItem */
            $cartItemType = $couponItem->getCartItemType();

            if (in_array($cartItemType, PlusItems::getTypes())) {
                $cart->removeItemsByType(PlusItems::getTypes());
            }

            if (in_array($cartItemType, [
                Coupon::SERVICE_AWPLUS_1_YEAR_AND_ONE_CARD,
                Coupon::SERVICE_AWPLUS_ONE_CARD,
            ])) {
                $item = new OneCard();
                $cart->addItem($item);
            }

            $item = $this->createCartItem($cartItemType);

            if ($cartItemType === CartItem\AwPlus::TYPE) {
                $item->setPrice(SubscriptionPrice::getPrice(Usr::SUBSCRIPTION_TYPE_AWPLUS, SubscriptionPeriod::DURATION_6_MONTHS));
                $startSubscriptionDate->modify(SubscriptionPeriod::DURATION_6_MONTHS);
            }

            $item->setCnt($couponItem->getCount());
            $cart->addItem($item);
        }

        if ($isFirstTimeCoupon) {
            $discount = new Discount();
            $discount->setId(Discount::ID_COUPON);
            $discount->setPrice(-SubscriptionPrice::getPrice(Usr::SUBSCRIPTION_TYPE_AWPLUS, SubscriptionPeriod::DURATION_6_MONTHS));
            $discount->setName($coupon->getName());
            $cart->addItem($discount);

            $subscriptionItem = $this->cartManager->addAwSubscriptionItem($cart, clone $startSubscriptionDate, false);
            $subscriptionItem->setScheduledDate(clone $startSubscriptionDate);
        } else {
            $discount = new Discount();
            $discount->setId(Discount::ID_COUPON);
            $discount->setPrice(-$cart->getDiscountAmount($coupon));
            $discount->setName($coupon->getName());
            $cart->addItem($discount);
        }

        $cart->setCalcDate(new \DateTime());
        $this->cartManager->save($cart);

        if ($cart->getTotalPrice() == 0) {
            $this->cartManager->markAsPayed($cart);
        }
    }

    private function createCartItem(?int $service): CartItem
    {
        switch ($service) {
            case Coupon::SERVICE_AWPLUS_ONE_CARD:
                return new CartItem\AwPlus();

            case Coupon::SERVICE_AWPLUS_1_YEAR_AND_ONE_CARD:
                return new CartItem\AwPlus1Year();

            default:
                $metadata = $this->entityManager->getClassMetadata(CartItem::class);
                $class = $metadata->discriminatorMap[$service];

                return new $class();
        }
    }
}
