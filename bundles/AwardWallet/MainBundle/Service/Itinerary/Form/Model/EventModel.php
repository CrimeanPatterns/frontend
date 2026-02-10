<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;

class EventModel extends AbstractModel
{
    /**
     * @Assert\NotBlank()
     * @Assert\Choice(choices=\AwardWallet\MainBundle\Entity\Restaurant::EVENT_TYPES)
     */
    protected ?int $eventType = null;

    /**
     * @Assert\NotBlank()
     * @Assert\Length(max=80, maxMessage="max-length")
     */
    protected ?string $title = null;

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
     * @Assert\NotBlank()
     * @Assert\Length(max=160, maxMessage="max-length")
     */
    protected ?string $address = null;

    /**
     * @Assert\Length(max=80, maxMessage="max-length")
     */
    protected ?string $phone = null;

    public function getEventType(): ?int
    {
        return $this->eventType;
    }

    public function setEventType(?int $eventType): self
    {
        $this->eventType = $eventType;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

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
