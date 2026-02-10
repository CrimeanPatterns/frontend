<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;

class ReservationModel extends AbstractModel
{
    /**
     * @Assert\NotBlank()
     */
    protected ?string $confirmationNumber = null;

    /**
     * @Assert\NotBlank(message="notblank")
     * @Assert\Length(max="80", maxMessage="max-length")
     */
    protected ?string $hotelName = null;

    /**
     * @Assert\NotBlank()
     * @Assert\Expression(
     *     "null === this.getCheckindate() || null === this.getCheckoutdate() || this.getCheckoutdate() >= this.getCheckindate()",
     *     message="reservation.dates-inconsistent"
     * )
     */
    protected ?\DateTime $checkInDate = null;

    /**
     * @Assert\NotBlank()
     */
    protected ?\DateTime $checkOutDate = null;

    /**
     * @Assert\NotBlank()
     * @Assert\Length(max="250", maxMessage="max-length")
     */
    protected ?string $address = null;

    /**
     * @Assert\Length(max="80", maxMessage="max-length")
     */
    protected ?string $phone = null;

    public function getHotelName(): ?string
    {
        return $this->hotelName;
    }

    public function setHotelName(?string $hotelName): self
    {
        $this->hotelName = $hotelName;

        return $this;
    }

    public function getCheckInDate(): ?\DateTime
    {
        return $this->checkInDate;
    }

    public function setCheckInDate(?\DateTime $checkInDate): self
    {
        $this->checkInDate = $checkInDate;

        return $this;
    }

    public function getCheckOutDate(): ?\DateTime
    {
        return $this->checkOutDate;
    }

    public function setCheckOutDate(?\DateTime $checkOutDate): self
    {
        $this->checkOutDate = $checkOutDate;

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

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }
}
