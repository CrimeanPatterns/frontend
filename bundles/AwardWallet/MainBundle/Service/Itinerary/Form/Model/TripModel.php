<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Form\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

class TripModel extends AbstractModel
{
    /**
     * @Assert\NotBlank()
     */
    protected ?string $confirmationNumber = null;

    /**
     * @Assert\Length(max=80, maxMessage="max-length")
     */
    protected ?string $phone = null;

    /**
     * @var AbstractSegmentModel[]|ArrayCollection
     * @Assert\Valid()
     * @Assert\Count(min=1, minMessage="segments.at-least-one")
     */
    protected $segments;

    public function __construct()
    {
        $this->segments = new ArrayCollection();
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

    /**
     * @return AbstractSegmentModel[]|ArrayCollection
     */
    public function getSegments()
    {
        return $this->segments;
    }

    public function setSegments($segments): self
    {
        $this->segments = $segments;

        return $this;
    }
}
