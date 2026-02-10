<?php

namespace AwardWallet\MainBundle\Service\StoreLocationFinder;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI
 */
class StoreFilter implements \JsonSerializable
{
    /**
     * @var int[]
     */
    protected $userIds = [];
    /**
     * @var int[]
     */
    protected $accountIds = [];
    /**
     * @var int[]
     */
    protected $couponIds = [];
    /**
     * @var int
     */
    protected $afterUserId;

    /**
     * @var int[]
     */
    protected $loyaltyKinds = [
        PROVIDER_KIND_SHOPPING,
        PROVIDER_KIND_DINING,
    ];

    /**
     * @var int
     */
    protected $loyaltyLimitPerGroup = 3;

    /**
     * @var int
     */
    protected $locationsLimit = 20;

    /**
     * @var int meters
     */
    protected $radius = 10 * 1600;

    /**
     * @return int[]
     */
    public function getUserIds(): array
    {
        return $this->userIds;
    }

    /**
     * @param int[] $userIds
     */
    public function setUserIds(array $userIds): self
    {
        $this->userIds = $userIds;

        return $this;
    }

    /**
     * @return int[]
     */
    public function getAccountIds(): array
    {
        return $this->accountIds;
    }

    /**
     * @param int[] $accountIds
     */
    public function setAccountIds(array $accountIds): self
    {
        $this->accountIds = $accountIds;

        return $this;
    }

    /**
     * @return int[]
     */
    public function getCouponIds(): array
    {
        return $this->couponIds;
    }

    /**
     * @param int[] $couponIds
     */
    public function setCouponIds(array $couponIds): self
    {
        $this->couponIds = $couponIds;

        return $this;
    }

    public function getAfterUserId(): ?int
    {
        return $this->afterUserId;
    }

    public function setAfterUserId(?int $afterUserId): self
    {
        $this->afterUserId = $afterUserId;

        return $this;
    }

    /**
     * @return int[]
     */
    public function getLoyaltyKinds(): array
    {
        return $this->loyaltyKinds;
    }

    /**
     * @param int[] $loyaltyKinds
     */
    public function setLoyaltyKinds(array $loyaltyKinds): self
    {
        $this->loyaltyKinds = $loyaltyKinds;

        return $this;
    }

    public function getLoyaltyLimitPerGroup(): int
    {
        return $this->loyaltyLimitPerGroup;
    }

    public function setLoyaltyLimitPerGroup(int $loyaltyLimitPerGroup): self
    {
        $this->loyaltyLimitPerGroup = $loyaltyLimitPerGroup;

        return $this;
    }

    public function getLocationsLimit(): int
    {
        return $this->locationsLimit;
    }

    public function setLocationsLimit(int $locationsLimit): self
    {
        $this->locationsLimit = $locationsLimit;

        return $this;
    }

    public function getRadius(): int
    {
        return $this->radius;
    }

    public function setRadius(int $radius): self
    {
        $this->radius = $radius;

        return $this;
    }

    public function jsonSerialize()
    {
        return [
            'radius' => $this->radius,
            'locationsLimit' => $this->locationsLimit,
            'userids' => $this->userIds,
            'accountids' => $this->accountIds,
            'couponids' => $this->couponIds,
            'loyaltyKinds' => $this->loyaltyKinds,
            'loyaltyLimitPerGroup' => $this->loyaltyLimitPerGroup,
        ];
    }
}
