<?php

namespace AwardWallet\Tests\Modules\DbBuilder;

abstract class AbstractItinerary extends AbstractDbEntity implements OwnableInterface
{
    /**
     * @var User|UserAgent|null
     */
    protected $user;

    protected ?Provider $provider = null;

    protected ?Provider $travelAgency = null;

    protected ?Account $account = null;

    protected ?TravelPlan $travelPlan = null;

    public function getUser()
    {
        return $this->user;
    }

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

    public function getTravelAgency(): ?Provider
    {
        return $this->travelAgency;
    }

    public function setTravelAgency(?Provider $travelAgency): self
    {
        $this->travelAgency = $travelAgency;

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

    public function getTravelPlan(): ?TravelPlan
    {
        return $this->travelPlan;
    }

    public function setTravelPlan(?TravelPlan $travelPlan): self
    {
        $this->travelPlan = $travelPlan;

        return $this;
    }
}
