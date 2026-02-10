<?php

namespace AwardWallet\Tests\Modules\DbBuilder;

class ProviderCoupon extends AbstractDbEntity implements OwnableInterface
{
    /**
     * @var User|UserAgent|null
     */
    private $user;

    private ?Currency $currency = null;

    private ?Account $account = null;

    private ?ProviderCouponShare $providerCouponShare = null;

    /**
     * @param User|UserAgent $user
     */
    public function __construct(string $name, ?string $value = null, $user = null, int $kind = PROVIDER_KIND_AIRLINE, array $fields = [])
    {
        parent::__construct(array_merge([
            'CreationDate' => date('Y-m-d'),
        ], $fields, [
            'ProgramName' => $name,
            'Kind' => $kind,
            'Value' => $value,
        ]));

        $this->user = $user;
    }

    /**
     * @return User|UserAgent|null
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User|UserAgent|null $user
     */
    public function setUser($user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getCurrency(): ?Currency
    {
        return $this->currency;
    }

    public function setCurrency(?Currency $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getAccount(): ?Account
    {
        return $this->account;
    }

    public function setAccount(?Account $account): self
    {
        $this->account = $account;

        return $this;
    }

    public function getProviderCouponShare(): ?ProviderCouponShare
    {
        return $this->providerCouponShare;
    }

    public function shareTo(?UserAgent $connection): self
    {
        $this->providerCouponShare = new ProviderCouponShare($connection, $this);

        return $this;
    }

    public function share(?ProviderCouponShare $providerCouponShare): self
    {
        $this->providerCouponShare = $providerCouponShare;

        return $this;
    }
}
