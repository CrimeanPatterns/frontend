<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;

class TrainSegmentModel extends AbstractSegmentModel
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
     * @Assert\Length(max=10, maxMessage="max-length")
     */
    protected ?string $departureStationCode = null;

    /**
     * @Assert\NotBlank()
     * @Assert\Length(max=250, maxMessage="max-length")
     */
    protected ?string $arrivalStation = null;

    /**
     * @Assert\Length(max=10, maxMessage="max-length")
     */
    protected ?string $arrivalStationCode = null;

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

    public function getDepartureStationCode(): ?string
    {
        return $this->departureStationCode;
    }

    public function setDepartureStationCode(?string $departureStationCode): self
    {
        $this->departureStationCode = $departureStationCode;

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

    public function getArrivalStationCode(): ?string
    {
        return $this->arrivalStationCode;
    }

    public function setArrivalStationCode(?string $arrivalStationCode): self
    {
        $this->arrivalStationCode = $arrivalStationCode;

        return $this;
    }

    public function getStartLocation()
    {
        return $this->getDepartureStation();
    }

    public function getEndLocation()
    {
        return $this->getArrivalStation();
    }

    public function getDateSequenceViolationMessage(): string
    {
        return 'itineraries.dates-inconsistent.train';
    }
}
