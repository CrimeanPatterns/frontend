<?php

namespace AwardWallet\MainBundle\Entity\Geo;

interface OptionalGeoDivisionsAwareInterface
{
    public function setCity(?string $city): self;

    public function getCity(): ?string;

    public function setCountryid(?int $countryid): self;

    public function getCountryid(): ?int;

    public function setStateid(?int $stateid): self;

    public function getStateid(): ?int;
}
