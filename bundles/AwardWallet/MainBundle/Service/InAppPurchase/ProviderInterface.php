<?php

namespace AwardWallet\MainBundle\Service\InAppPurchase;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\InAppPurchase\Exception\VerificationException;

interface ProviderInterface
{
    /**
     * @return PurchaseInterface[]
     * @throws VerificationException
     */
    public function validate(array $data, ?Usr $currentUser = null, array $options = []): array;

    public function scanSubscriptions(Usr $user, Billing $billing): void;

    /**
     * @return PurchaseInterface[]
     */
    public function findSubscriptions(Usr $user): array;

    public function getPlatformId(): string;

    public function getCompanyName(): string;

    public function getSubscriptionsForSale(): array;

    public function getConsumablesForSale(): array;

    public function getPlatformProductId(string $productId): ?string;
}
