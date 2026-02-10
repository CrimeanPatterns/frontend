<?php

namespace AwardWallet\MainBundle\Service\InAppPurchase;

use AwardWallet\MainBundle\Entity\Usr;

interface PurchaseInterface
{
    public function setUser(Usr $user): self;

    public function getUser(): Usr;

    public function setPaymentType(int $paymentType): self;

    public function getPaymentType(): int;

    public function isAppStorePurchase(): bool;

    public function setTransactionId(string $transactionId): self;

    public function getTransactionId(): string;

    public function setSecondaryTransactionId(int $transactionId): self;

    public function getSecondaryTransactionId(): ?int;

    public function setPurchaseDate(\DateTime $purchaseDate): self;

    public function getPurchaseDate(): \DateTime;

    public function setPurchaseToken(?string $purchaseToken): self;

    public function getPurchaseToken(): ?string;

    public function setUserToken(?string $userToken): self;

    public function getUserToken(): ?string;

    public function setCancellationDate(?\DateTime $cancellationDate): self;

    public function getCancellationDate(): ?\DateTime;

    public function isCanceled(): bool;

    public function setCanceled(bool $canceled): self;

    public function getPurchaseType(): string;

    public static function getAvailableProducts(Usr $user, AbstractProvider $provider): array;
}
