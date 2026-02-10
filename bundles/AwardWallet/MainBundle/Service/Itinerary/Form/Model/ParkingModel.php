<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;

class ParkingModel extends AbstractModel
{
    /**
     * @Assert\NotBlank()
     */
    protected ?string $confirmationNumber = null;

    /**
     * @Assert\NotBlank()
     * @Assert\Length(max=80, maxMessage="max-length")
     */
    protected ?string $parkingCompanyName = null;

    /**
     * @Assert\NotBlank()
     * @Assert\Length(max=160, maxMessage="max-length")
     */
    protected ?string $address = null;

    /**
     * @Assert\NotBlank()
     * @Assert\Expression(
     *     "this.getStartDate() === null || this.getEndDate() === null || this.getStartDate() <= this.getEndDate()",
     *     message="event.dates-inconsistent"
     * )
     */
    protected ?\DateTime $startDate = null;

    /**
     * @Assert\NotBlank()
     */
    protected ?\DateTime $endDate = null;

    /**
     * @Assert\Length(max=20, maxMessage="max-length")
     */
    protected ?string $phone = null;

    /**
     * @Assert\Length(max=20, maxMessage="max-length")
     */
    protected ?string $plate = null;

    /**
     * @Assert\Length(max=30, maxMessage="max-length")
     */
    protected ?string $spot = null;

    public function getParkingCompanyName(): ?string
    {
        return $this->parkingCompanyName;
    }

    public function setParkingCompanyName(?string $parkingCompanyName): self
    {
        $this->parkingCompanyName = $parkingCompanyName;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getStartDate(): ?\DateTime
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTime $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTime
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTime $endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getPlate(): ?string
    {
        return $this->plate;
    }

    public function setPlate(?string $plate): self
    {
        $this->plate = $plate;

        return $this;
    }

    public function getSpot(): ?string
    {
        return $this->spot;
    }

    public function setSpot(?string $spot): self
    {
        $this->spot = $spot;

        return $this;
    }
}
