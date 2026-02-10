<?php

namespace AwardWallet\MainBundle\Service\InAppPurchase;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\InAppPurchase\Consumable\AwPlus;
use AwardWallet\MainBundle\Service\InAppPurchase\Consumable\Credit1;
use AwardWallet\MainBundle\Service\InAppPurchase\Consumable\Credit10;
use AwardWallet\MainBundle\Service\InAppPurchase\Consumable\Credit3;
use AwardWallet\MainBundle\Service\InAppPurchase\Consumable\Credit5;

abstract class AbstractConsumable extends AbstractPurchase
{
    public function __toString(): string
    {
        return "consumable: " . parent::__toString();
    }

    public static function getAvailableProducts(Usr $user, AbstractProvider $provider): array
    {
        return [
            AwPlus::class,
            Credit1::class,
            Credit3::class,
            Credit5::class,
            Credit10::class,
        ];
    }

    public static function create(
        string $purchaseType,
        Usr $user,
        int $paymentType,
        string $transactionId,
        \DateTime $purchaseDate
    ): AbstractConsumable {
        if (!static::isConsumable($purchaseType)) {
            throw new \InvalidArgumentException('Invalid purchase type');
        }

        return new $purchaseType($user, $paymentType, $transactionId, $purchaseDate);
    }

    public static function isConsumable($productId): bool
    {
        return is_subclass_of($productId, self::class);
    }
}
