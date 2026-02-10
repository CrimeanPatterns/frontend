<?php

namespace AwardWallet\MainBundle\Service\Lounge\DTO;

class LocationDTO
{
    private ?string $terminal = null;

    private ?string $gate = null;

    private ?string $gate2 = null;

    public function getTerminal(): ?string
    {
        return $this->terminal;
    }

    public function setTerminal(?string $terminal): self
    {
        $this->terminal = $terminal;

        return $this;
    }

    public function getGate(): ?string
    {
        return $this->gate;
    }

    public function setGate(?string $gate): self
    {
        $this->gate = $gate;

        return $this;
    }

    public function getGate2(): ?string
    {
        return $this->gate2;
    }

    public function setGate2(?string $gate2): self
    {
        $this->gate2 = $gate2;

        return $this;
    }

    public static function make(
        ?string $terminal = null,
        ?string $gate = null,
        ?string $gate2 = null
    ): self {
        $location = new self();
        $location->setTerminal($terminal);
        $location->setGate($gate);
        $location->setGate2($gate2);

        return $location;
    }
}
