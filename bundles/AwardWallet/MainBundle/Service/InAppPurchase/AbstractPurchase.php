<?php

namespace AwardWallet\MainBundle\Service\InAppPurchase;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\Usr;

abstract class AbstractPurchase implements PurchaseInterface
{
    protected Usr $user;

    protected int $paymentType;

    protected string $transactionId;

    protected ?int $secondaryTransactionId = null;

    protected \DateTime $purchaseDate;

    protected ?string $purchaseToken = null;

    protected ?string $userToken = null;

    protected bool $canceled = false;

    protected ?\DateTime $cancellationDate;

    public function __construct(
        Usr $user,
        int $paymentType,
        string $transactionId,
        \DateTime $purchaseDate
    ) {
        $this->user = $user;
        $this->paymentType = $paymentType;
        $this->transactionId = $transactionId;
        $this->purchaseDate = $purchaseDate;
    }

    public function __toString()
    {
        return sprintf(
            "product: %s, userId: %d, userName: %s, paymentType: %s, transaction: %s, secTransaction: %s, purchaseDate: %s, " .
            "canceled: %s, cancelDate: %s, userToken: %s, purchaseToken: %s",
            static::class,
            $this->user->getId(),
            $this->user->getFullName(),
            $this->paymentType,
            $this->transactionId,
            $this->secondaryTransactionId,
            $this->purchaseDate->format("Y-m-d H:i:s"),
            $this->canceled ? 1 : 0,
            isset($this->cancellationDate) ? $this->cancellationDate->format("Y-m-d H:i:s") : 'null',
            isset($this->userToken) ? 'hidden' : 'null',
            $this->purchaseToken
        );
    }

    public function setUser(Usr $user): PurchaseInterface
    {
        $this->user = $user;

        return $this;
    }

    public function getUser(): Usr
    {
        return $this->user;
    }

    public function setPaymentType(int $paymentType): PurchaseInterface
    {
        $this->paymentType = $paymentType;

        return $this;
    }

    public function getPaymentType(): int
    {
        return $this->paymentType;
    }

    public function isAppStorePurchase(): bool
    {
        return $this->getPaymentType() === Cart::PAYMENTTYPE_APPSTORE;
    }

    public function setTransactionId(string $transactionId): PurchaseInterface
    {
        $this->transactionId = $transactionId;

        return $this;
    }

    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    public function setSecondaryTransactionId(int $secondaryTransactionId): PurchaseInterface
    {
        $this->secondaryTransactionId = $secondaryTransactionId;

        return $this;
    }

    public function getSecondaryTransactionId(): ?int
    {
        return $this->secondaryTransactionId;
    }

    public function setPurchaseDate(\DateTime $purchaseDate): PurchaseInterface
    {
        $this->purchaseDate = $purchaseDate;

        return $this;
    }

    public function getPurchaseDate(): \DateTime
    {
        return $this->purchaseDate;
    }

    public function setPurchaseToken(?string $purchaseToken): PurchaseInterface
    {
        $this->purchaseToken = $purchaseToken;

        return $this;
    }

    public function getPurchaseToken(): ?string
    {
        return $this->purchaseToken;
    }

    public function setUserToken(?string $userToken): PurchaseInterface
    {
        $this->userToken = $userToken;

        return $this;
    }

    public function getUserToken(): ?string
    {
        return $this->userToken;
    }

    public function setCancellationDate(?\DateTime $cancellationDate): PurchaseInterface
    {
        $this->cancellationDate = $cancellationDate;
        $this->canceled = !is_null($cancellationDate);

        return $this;
    }

    public function getCancellationDate(): ?\DateTime
    {
        return $this->cancellationDate;
    }

    public function isCanceled(): bool
    {
        return $this->canceled;
    }

    public function setCanceled(bool $canceled): PurchaseInterface
    {
        $this->canceled = $canceled;

        if (!$canceled) {
            $this->cancellationDate = null;
        }

        return $this;
    }

    public function getPurchaseType(): string
    {
        return get_class($this);
    }

    public static function getAvailableProducts(Usr $user, AbstractProvider $provider): array
    {
        return array_merge(
            AbstractSubscription::getAvailableProducts($user, $provider),
            AbstractConsumable::getAvailableProducts($user, $provider)
        );
    }
}
