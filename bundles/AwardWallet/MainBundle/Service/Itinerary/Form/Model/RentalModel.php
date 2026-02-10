<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Form\Model;

use AwardWallet\MainBundle\Service\Itinerary\Form\Validator\DateSequence;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @DateSequence()
 */
class RentalModel extends AbstractModel implements DateSequenceInterface
{
    /**
     * @Assert\NotBlank()
     */
    protected ?string $confirmationNumber = null;

    /**
     * @Assert\NotBlank()
     * @Assert\Length(max=80, maxMessage="max-length")
     */
    protected ?string $rentalCompany = null;

    /**
     * @Assert\NotBlank()
     * @Assert\Length(max=160, maxMessage="max-length")
     */
    protected ?string $pickUpAddress = null;

    /**
     * @Assert\NotBlank()
     */
    protected ?\DateTime $pickUpDate = null;

    /**
     * @Assert\NotBlank()
     * @Assert\Length(max=160, maxMessage="max-length")
     */
    protected ?string $dropOffAddress = null;

    /**
     * @Assert\NotBlank()
     */
    protected ?\DateTime $dropOffDate = null;

    /**
     * @Assert\Length(max=20, maxMessage="max-length")
     */
    protected ?string $phone = null;

    public function getRentalCompany(): ?string
    {
        return $this->rentalCompany;
    }

    public function setRentalCompany(?string $rentalCompany): self
    {
        $this->rentalCompany = $rentalCompany;

        return $this;
    }

    public function getPickUpAddress(): ?string
    {
        return $this->pickUpAddress;
    }

    public function setPickUpAddress(?string $pickUpAddress): self
    {
        $this->pickUpAddress = $pickUpAddress;

        return $this;
    }

    public function getPickUpDate(): ?\DateTime
    {
        return $this->pickUpDate;
    }

    public function setPickUpDate(?\DateTime $pickUpDate): self
    {
        $this->pickUpDate = $pickUpDate;

        return $this;
    }

    public function getDropOffAddress(): ?string
    {
        return $this->dropOffAddress;
    }

    public function setDropOffAddress(?string $dropOffAddress): self
    {
        $this->dropOffAddress = $dropOffAddress;

        return $this;
    }

    public function getDropOffDate(): ?\DateTime
    {
        return $this->dropOffDate;
    }

    public function setDropOffDate(?\DateTime $dropOffDate): self
    {
        $this->dropOffDate = $dropOffDate;

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

    public function getStartDate(): ?\DateTime
    {
        return $this->getPickUpDate();
    }

    public function getStartLocation(): ?string
    {
        return $this->getPickUpAddress();
    }

    public function getEndDate(): ?\DateTime
    {
        return $this->getDropOffDate();
    }

    public function getEndLocation(): ?string
    {
        return $this->getDropOffAddress();
    }

    public function getDateSequenceViolationMessage(): string
    {
        return 'rental.dates-inconsistent';
    }

    public function getDateSequenceViolationPath(): string
    {
        return 'pickUpDate';
    }
}
