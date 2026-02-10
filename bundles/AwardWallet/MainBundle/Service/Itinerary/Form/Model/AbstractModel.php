<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Form\Model;

use AwardWallet\MainBundle\Entity\Owner;
use Symfony\Component\Validator\Constraints as Assert;

abstract class AbstractModel
{
    protected ?Owner $owner = null;

    /**
     * @Assert\Length(max=100, maxMessage="max-length")
     */
    protected ?string $confirmationNumber = null;

    /**
     * @Assert\Length(max=4000, maxMessage="max-length")
     */
    protected ?string $notes = null;

    public function getOwner(): ?Owner
    {
        return $this->owner;
    }

    public function setOwner(?Owner $owner): self
    {
        $this->owner = $owner;

        return $this;
    }

    public function getConfirmationNumber(): ?string
    {
        return $this->confirmationNumber;
    }

    public function setConfirmationNumber(?string $confirmationNumber): self
    {
        $this->confirmationNumber = $confirmationNumber;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;

        return $this;
    }
}
