<?php

namespace AwardWallet\MainBundle\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="UserSignal")
 * @ORM\Entity
 */
class Usersignal
{
    /**
     * @var int
     * @ORM\Column(name="UserSignalID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var Providersignal
     * @ORM\ManyToOne(targetEntity="Providersignal")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ProviderSignalID", referencedColumnName="ProviderSignalID")
     * })
     */
    protected $providerSignalId;

    /**
     * @var Usr
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserID", referencedColumnName="UserID")
     * })
     */
    protected $userId;

    /**
     * @var \DateTime
     * @ORM\Column(name="DetectedOn", type="datetime", nullable=false)
     */
    protected $detectedOn;

    /**
     * @var Usersignalattribute[]|Collection
     * @ORM\OneToMany(targetEntity="Usersignalattribute", mappedBy="userSignalId", cascade={"persist", "remove"})
     */
    protected $attributes;

    public function __construct()
    {
        $this->attributes = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): Usersignal
    {
        $this->id = $id;

        return $this;
    }

    public function getProviderSignalId(): Providersignal
    {
        return $this->providerSignalId;
    }

    public function setProviderSignalId(Providersignal $providerSignalId): Usersignal
    {
        $this->providerSignalId = $providerSignalId;

        return $this;
    }

    public function getUserId(): Usr
    {
        return $this->userId;
    }

    public function setUserId(Usr $userId): Usersignal
    {
        $this->userId = $userId;

        return $this;
    }

    public function getDetectedOn(): \DateTime
    {
        return $this->detectedOn;
    }

    public function setDetectedOn(\DateTime $detectedOn): Usersignal
    {
        $this->detectedOn = $detectedOn;

        return $this;
    }

    /**
     * @return Usersignalattribute[]|Collection
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @param Usersignalattribute[]|Collection $attributes
     * @return Usersignal
     */
    public function setAttributes($attributes)
    {
        $this->attributes = $attributes;

        return $this;
    }
}
