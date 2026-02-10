<?php

namespace AwardWallet\MainBundle\Entity\Geo\Adapters;

use AwardWallet\MainBundle\Entity\Geo\OptionalGeoDivisionsAwareInterface;
use AwardWallet\MainBundle\Entity\Usr;

class UsrDivisionsAdapter implements OptionalGeoDivisionsAwareInterface
{
    protected Usr $user;

    public function __construct(Usr $user)
    {
        $this->user = $user;
    }

    public function setCity(?string $city): self
    {
        $this->user->setCity($city);

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->user->getCity();
    }

    public function setCountryid(?int $countryid): self
    {
        $this->user->setCountryid($countryid);

        return $this;
    }

    public function getCountryid(): ?int
    {
        return $this->user->getCountryid();
    }

    public function setStateid(?int $stateid): self
    {
        $this->user->setStateid($stateid);

        return $this;
    }

    public function getStateid(): ?int
    {
        return $this->user->getStateid();
    }
}
