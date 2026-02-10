<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="ProviderSignal")
 * @ORM\Entity
 */
class Providersignal
{
    /**
     * @var int
     * @ORM\Column(name="ProviderSignalID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(name="Name", type="string", length=255, nullable=false)
     */
    protected $name;

    /**
     * @var string
     * @ORM\Column(name="Code", type="string", length=80, nullable=false)
     */
    protected $code;

    /**
     * @var Signalattribute[]|Collection
     * @ORM\OneToMany(targetEntity="Signalattribute", mappedBy="providerSignalId", cascade={"persist", "remove"})
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

    public function setId(int $id): Providersignal
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Providersignal
    {
        $this->name = $name;

        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): Providersignal
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return Signalattribute[]|ArrayCollection|Collection
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @param Signalattribute[]|ArrayCollection|Collection $attributes
     * @return Providersignal
     */
    public function setAttributes($attributes)
    {
        $this->attributes = $attributes;

        return $this;
    }
}
