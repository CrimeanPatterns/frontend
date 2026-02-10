<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="SignalAttribute")
 * @ORM\Entity
 */
class Signalattribute
{
    public const TYPE_INT = 1;
    public const TYPE_FLOAT = 2;
    public const TYPE_STRING = 3;

    /**
     * @var int
     * @ORM\Column(name="SignalAttributeID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var Providersignal
     * @ORM\ManyToOne(targetEntity="Providersignal", inversedBy="attributes")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ProviderSignalID", referencedColumnName="ProviderSignalID")
     * })
     */
    protected $providerSignalId;

    /**
     * @var string
     * @ORM\Column(name="Name", type="string", length=255, nullable=false)
     */
    protected $name;

    /**
     * @var int
     * @ORM\Column(name="Type", type="integer", nullable=false)
     */
    protected $type;

    /**
     * @var string
     * @ORM\Column(name="PromptHelper", type="string", length=255, nullable=false)
     */
    protected $promptHelper;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): Signalattribute
    {
        $this->id = $id;

        return $this;
    }

    public function getProviderSignalId(): Providersignal
    {
        return $this->providerSignalId;
    }

    public function setProviderSignalId(Providersignal $providerSignalId): Signalattribute
    {
        $this->providerSignalId = $providerSignalId;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Signalattribute
    {
        $this->name = $name;

        return $this;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function setType(int $type): Signalattribute
    {
        $this->type = $type;

        return $this;
    }

    public function getPromptHelper(): string
    {
        return $this->promptHelper;
    }

    public function setPromptHelper(string $promptHelper): Signalattribute
    {
        $this->promptHelper = $promptHelper;

        return $this;
    }
}
