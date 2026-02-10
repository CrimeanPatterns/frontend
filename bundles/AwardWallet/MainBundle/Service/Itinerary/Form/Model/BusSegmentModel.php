<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;

class BusSegmentModel extends AbstractSegmentModel
{
    /**
     * @Assert\NotBlank()
     * @Assert\Length(max=250, maxMessage="max-length")
     */
    protected ?string $carrier = null;

    /**
     * @Assert\Length(max=20, maxMessage="max-length")
     */
    protected ?string $route = null;

    /**
     * @Assert\NotBlank()
     * @Assert\Length(max=250, maxMessage="max-length")
     */
    protected ?string $departureStation = null;

    /**
     * @Assert\NotBlank()
     * @Assert\Length(max=250, maxMessage="max-length")
     */
    protected ?string $arrivalStation = null;

    public function getCarrier(): ?string
    {
        return $this->carrier;
    }

    public function setCarrier(?string $carrier): self
    {
        $this->carrier = $carrier;

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

    public function getDepartureStation(): ?string
    {
        return $this->departureStation;
    }

    public function setDepartureStation(?string $departureStation): self
    {
        $this->departureStation = $departureStation;

        return $this;
    }

    public function getArrivalStation(): ?string
    {
        return $this->arrivalStation;
    }

    public function setArrivalStation(?string $arrivalStation): self
    {
        $this->arrivalStation = $arrivalStation;

        return $this;
    }

    public function getStartLocation(): ?string
    {
        return $this->getDepartureStation();
    }

    public function getEndLocation(): ?string
    {
        return $this->getArrivalStation();
    }
}
