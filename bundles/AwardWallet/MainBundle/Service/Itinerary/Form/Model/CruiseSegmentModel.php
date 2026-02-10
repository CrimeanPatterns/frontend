<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;

class CruiseSegmentModel extends AbstractSegmentModel
{
    /**
     * @Assert\NotBlank()
     * @Assert\Length(max=250, maxMessage="max-length")
     */
    protected ?string $cruiseShip = null;

    /**
     * @Assert\Length(max=20, maxMessage="max-length")
     */
    protected ?string $route = null;

    /**
     * @Assert\NotBlank()
     * @Assert\Length(max=250, maxMessage="max-length")
     */
    protected ?string $departurePort = null;

    /**
     * @Assert\NotBlank()
     * @Assert\Length(max=250, maxMessage="max-length")
     */
    protected ?string $arrivalPort = null;

    public function getCruiseShip(): ?string
    {
        return $this->cruiseShip;
    }

    public function setCruiseShip(?string $cruiseShip): self
    {
        $this->cruiseShip = $cruiseShip;

        return $this;
    }

    public function getRoute(): ?string
    {
        return $this->route;
    }

    public function setRoute(?string $route): self
    {
        $this->route = $route;

        return $this;
    }

    public function getDeparturePort(): ?string
    {
        return $this->departurePort;
    }

    public function setDeparturePort(?string $departurePort): self
    {
        $this->departurePort = $departurePort;

        return $this;
    }

    public function getArrivalPort(): ?string
    {
        return $this->arrivalPort;
    }

    public function setArrivalPort(?string $arrivalPort): self
    {
        $this->arrivalPort = $arrivalPort;

        return $this;
    }

    public function getStartLocation(): ?string
    {
        return $this->getDeparturePort();
    }

    public function getEndLocation(): ?string
    {
        return $this->getArrivalPort();
    }
}
