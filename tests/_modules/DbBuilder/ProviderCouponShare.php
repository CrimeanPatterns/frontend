<?php

namespace AwardWallet\Tests\Modules\DbBuilder;

class ProviderCouponShare extends AbstractDbEntity
{
    private ?UserAgent $connection;

    private ?ProviderCoupon $providerCoupon;

    public function __construct(?UserAgent $connection = null, ?ProviderCoupon $providerCoupon = null, array $fields = [])
    {
        parent::__construct($fields);

        $this->connection = $connection;
        $this->providerCoupon = $providerCoupon;
    }

    public function getConnection(): ?UserAgent
    {
        return $this->connection;
    }

    public function setConnection(?UserAgent $connection): self
    {
        $this->connection = $connection;

        return $this;
    }

    public function getProviderCoupon(): ?ProviderCoupon
    {
        return $this->providerCoupon;
    }

    public function setProviderCoupon(?ProviderCoupon $providerCoupon): self
    {
        $this->providerCoupon = $providerCoupon;

        return $this;
    }
}
