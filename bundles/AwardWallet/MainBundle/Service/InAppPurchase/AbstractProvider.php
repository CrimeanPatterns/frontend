<?php

namespace AwardWallet\MainBundle\Service\InAppPurchase;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem\AwPlus;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusWeekSubscription;
use AwardWallet\MainBundle\Entity\CartItem\BalanceWatchCredit;
use AwardWallet\MainBundle\Entity\CartItem\Discount;
use AwardWallet\MainBundle\Service\InAppPurchase\Consumable\AwPlus as ConsumableAwPlus;
use AwardWallet\MainBundle\Service\InAppPurchase\Consumable\Credit1;
use AwardWallet\MainBundle\Service\InAppPurchase\Consumable\Credit10;
use AwardWallet\MainBundle\Service\InAppPurchase\Consumable\Credit3;
use AwardWallet\MainBundle\Service\InAppPurchase\Consumable\Credit5;
use AwardWallet\MainBundle\Service\InAppPurchase\Subscription\AwPlus as SubscriptionAwPlus;
use AwardWallet\MainBundle\Service\InAppPurchase\Subscription\AwPlusDiscounted;
use AwardWallet\MainBundle\Service\InAppPurchase\Subscription\AwPlusWeek;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;

abstract class AbstractProvider implements ProviderInterface
{
    protected EntityManagerInterface $em;

    /**
     * @var Product[]
     */
    protected array $products = [];

    protected bool $useLatestMobileVersion = false;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function getPlatformProductId(string $productId): ?string
    {
        if (!isset($this->products[$productId])) {
            return null;
        }

        return $this->products[$productId]->id;
    }

    public function getProductId(string $platformProductId): ?string
    {
        foreach ($this->products as $id => $product) {
            if ($product->id === $platformProductId) {
                return $id;
            }
        }

        return null;
    }

    public function getPlatformProductIdByCart(Cart $cart): ?string
    {
        $productId = $this->getProductIdByCart($cart);

        if (is_null($productId)) {
            return null;
        }

        return $this->getPlatformProductId($productId);
    }

    public function getSubscriptionsForSale(): array
    {
        return array_map(
            function (Product $product) {
                return array_filter(get_object_vars($product));
            },
            array_values(
                array_filter($this->products, function ($productId) {
                    return AbstractSubscription::isSubscription($productId);
                }, ARRAY_FILTER_USE_KEY)
            )
        );
    }

    public function getConsumablesForSale(): array
    {
        return array_map(
            function (Product $product) {
                return array_filter(get_object_vars($product), function ($value) {
                    return !is_null($value);
                });
            },
            array_values(
                array_filter($this->products, function ($productId) {
                    return AbstractConsumable::isConsumable($productId) && !in_array($productId, [ConsumableAwPlus::class]);
                }, ARRAY_FILTER_USE_KEY)
            )
        );
    }

    public function setUseLatestMobileVersion(bool $useLatestMobileVersion): self
    {
        $this->useLatestMobileVersion = $useLatestMobileVersion;

        return $this;
    }

    protected function findProductIdByTransactionId(string $transactionId, int $paymentType): ?string
    {
        $cart = $this->findCartByTransactionId($transactionId, $paymentType);

        if (!$cart) {
            return null;
        }

        return $this->getProductIdByCart($cart);
    }

    protected function findCartByTransactionId(string $transactionId, int $paymentType): ?Cart
    {
        $criteria = Criteria::create()
            ->where(
                Criteria::expr()->andX(
                    Criteria::expr()->neq('paydate', null),
                    Criteria::expr()->eq('paymenttype', $paymentType),
                    Criteria::expr()->eq('billingtransactionid', $transactionId)
                )
            );
        $carts = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Cart::class)->matching($criteria);

        if ($carts->count() === 0) {
            return null;
        }

        return $carts->first();
    }

    protected function getProductIdByCart(Cart $cart): ?string
    {
        if (!$cart->isAwPlusSubscription()) {
            $awPlus6months = $cart->getItemsByType([AwPlus::TYPE]);

            if ($awPlus6months->count() > 0 && $cart->getItems()->count() === 1) {
                return ConsumableAwPlus::class;
            }

            $credits = $cart->getItemsByType([BalanceWatchCredit::TYPE]);

            if ($credits->count() === 0) {
                return null;
            }
            /** @var BalanceWatchCredit $credit */
            $credit = $credits->first();

            switch ($credit->getCnt()) {
                case 1:
                    return Credit1::class;

                    break;

                case 3:
                    return Credit3::class;

                    break;

                case 5:
                    return Credit5::class;

                    break;

                case 10:
                    return Credit10::class;

                    break;

                default:
                    return null;

                    break;
            }
        }

        if ($cart->hasItemsByType([Discount::TYPE])) {
            return AwPlusDiscounted::class;
        } elseif ($cart->hasItemsByType([AwPlusWeekSubscription::TYPE])) {
            return AwPlusWeek::class;
        } else {
            return SubscriptionAwPlus::class;
        }
    }
}
