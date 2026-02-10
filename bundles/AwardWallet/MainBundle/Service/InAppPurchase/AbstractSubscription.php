<?php

namespace AwardWallet\MainBundle\Service\InAppPurchase;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\InAppPurchase\Subscription\AwPlus;
use AwardWallet\MainBundle\Service\InAppPurchase\Subscription\AwPlusDiscounted;
use AwardWallet\MainBundle\Service\InAppPurchase\Subscription\AwPlusWeek;

abstract class AbstractSubscription extends AbstractPurchase
{
    protected ?\DateTime $expiresDate;

    protected bool $recurring = false;

    public function __toString(): string
    {
        return sprintf(
            "subscription: " . parent::__toString() . ", expiresDate: %s, recurring: %s",
            isset($this->expiresDate) ? $this->expiresDate->format("Y-m-d H:i:s") : 'null',
            $this->recurring ? 1 : 0
        );
    }

    public function setExpiresDate(?\DateTime $expiresDate): self
    {
        $this->expiresDate = $expiresDate;

        return $this;
    }

    public function getExpiresDate(): ?\DateTime
    {
        return $this->expiresDate;
    }

    public function setRecurring(bool $recurring): self
    {
        $this->recurring = $recurring;

        return $this;
    }

    public function isRecurring(): bool
    {
        return $this->recurring;
    }

    public static function getAvailableProducts(Usr $user, AbstractProvider $provider): array
    {
        return [
            AwPlusWeek::class,
            AwPlusDiscounted::class,
            AwPlus::class,
        ];
    }

    public static function getAvailableSubscription(?Usr $user = null, ?\DateTime $purchaseDate = null): string
    {
        $purchaseDate = is_null($purchaseDate) ? new \DateTime() : $purchaseDate;

        if ($purchaseDate < (new \DateTime("2017-02-17"))) {
            $productId = AwPlusDiscounted::class;
        } else {
            $productId = AwPlus::class;
        }

        return $productId;
    }

    public static function create(
        string $purchaseType,
        Usr $user,
        int $paymentType,
        string $transactionId,
        \DateTime $purchaseDate
    ): AbstractSubscription {
        if (!static::isSubscription($purchaseType)) {
            throw new \InvalidArgumentException('Invalid purchase type');
        }

        return new $purchaseType($user, $paymentType, $transactionId, $purchaseDate);
    }

    public static function isSubscription($productId): bool
    {
        return is_subclass_of($productId, self::class);
    }
}
