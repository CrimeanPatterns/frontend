<?php

namespace AwardWallet\Tests\Modules\DbBuilder;

class Account extends AbstractDbEntity implements OwnableInterface
{
    /**
     * @var User|UserAgent|null
     */
    private $user;

    private ?Provider $provider;

    /**
     * @var AccountProperty[]
     */
    private array $properties;

    /**
     * @var SubAccount[]
     */
    private array $subAccounts;

    /**
     * @var AbstractItinerary[]
     */
    private array $itineraries = [];

    private ?Currency $currency = null;

    private ?AccountShare $accountShare = null;

    /**
     * @param User|UserAgent $user
     */
    public function __construct(
        $user = null,
        ?Provider $provider = null,
        array $properties = [],
        array $fields = [],
        array $subAccounts = []
    ) {
        parent::__construct(array_merge([
            'State' => ACCOUNT_ENABLED,
        ], $fields, [
            'SubAccounts' => count($subAccounts),
        ]));

        $this->user = $user;
        $this->provider = $provider;
        $this->properties = $properties;
        $this->subAccounts = $subAccounts;
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

    public function getProvider(): ?Provider
    {
        return $this->provider;
    }

    public function setProvider(?Provider $provider): self
    {
        $this->provider = $provider;

        return $this;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    public function setProperties(array $properties): self
    {
        $this->properties = $properties;

        return $this;
    }

    public function getSubAccounts(): array
    {
        return $this->subAccounts;
    }

    public function setSubAccounts(array $subAccounts): self
    {
        $this->subAccounts = $subAccounts;

        return $this;
    }

    /**
     * @return AbstractItinerary[]
     */
    public function getItineraries(): array
    {
        return $this->itineraries;
    }

    public function setItineraries(array $itineraries): self
    {
        $this->itineraries = $itineraries;

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

    public function getAccountShare(): ?AccountShare
    {
        return $this->accountShare;
    }

    public function shareTo(?UserAgent $connection): self
    {
        $this->accountShare = new AccountShare($connection, $this);

        return $this;
    }

    public function share(?AccountShare $accountShare): self
    {
        $this->accountShare = $accountShare;

        return $this;
    }
}
