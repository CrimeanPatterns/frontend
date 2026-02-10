<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Form\Model;

use AwardWallet\Common\Entity\Aircode;
use Symfony\Component\Validator\Constraints as Assert;

class FlightSegmentModel extends AbstractSegmentModel
{
    /**
     * @Assert\NotBlank()
     * @Assert\Length(max=250, maxMessage="max-length")
     */
    protected ?string $airlineName = null;

    /**
     * @Assert\NotBlank()
     * @Assert\Type("digit", message="digit")
     * @Assert\Length(max=20, maxMessage="max-length")
     */
    protected ?string $flightNumber = null;

    /**
     * @Assert\NotBlank()
     * @Assert\Expression(
     *     "this.getDepartureAirport() === null || this.getArrivalAirport() === null || this.getDepartureAirport() !== this.getArrivalAirport()",
     *     message="itineraries.departure-and-arrival-are-the-same"
     * )
     */
    protected ?Aircode $departureAirport = null;

    /**
     * @Assert\NotBlank()
     */
    protected ?Aircode $arrivalAirport = null;

    public function getAirlineName(): ?string
    {
        return $this->airlineName;
    }

    public function setAirlineName(?string $airlineName): self
    {
        $this->airlineName = $airlineName;

        return $this;
    }

    public function getFlightNumber(): ?string
    {
        return $this->flightNumber;
    }

    public function setFlightNumber(?string $flightNumber): self
    {
        $this->flightNumber = $flightNumber;

        return $this;
    }

    public function getDepartureAirport(): ?Aircode
    {
        return $this->departureAirport;
    }

    public function setDepartureAirport(?Aircode $departureAirport): self
    {
        $this->departureAirport = $departureAirport;

        return $this;
    }

    public function getArrivalAirport(): ?Aircode
    {
        return $this->arrivalAirport;
    }

    public function setArrivalAirport(?Aircode $arrivalAirport): self
    {
        $this->arrivalAirport = $arrivalAirport;

        return $this;
    }

    public function getStartLocation(): ?Aircode
    {
        return $this->getDepartureAirport();
    }

    public function getEndLocation(): ?Aircode
    {
        return $this->getArrivalAirport();
    }
}
