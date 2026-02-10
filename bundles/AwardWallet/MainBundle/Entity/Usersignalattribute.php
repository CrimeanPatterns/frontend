<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="UserSignalAttribute")
 * @ORM\Entity
 */
class Usersignalattribute
{
    /**
     * @var int
     * @ORM\Column(name="UserSignalAttributeID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var Usersignal
     * @ORM\ManyToOne(targetEntity="Usersignal", inversedBy="attributes")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserSignalID", referencedColumnName="UserSignalID")
     * })
     */
    protected $userSignalId;

    /**
     * @var Signalattribute
     * @ORM\ManyToOne(targetEntity="Signalattribute")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="SignalAttributeID", referencedColumnName="SignalAttributeID")
     * })
     */
    protected $signalAttributeId;

    /**
     * @var string
     * @ORM\Column(name="Value", type="string", length=2000, nullable=true)
     */
    protected $value;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): Usersignalattribute
    {
        $this->id = $id;

        return $this;
    }

    public function getUserSignalId(): Usersignal
    {
        return $this->userSignalId;
    }

    public function setUserSignalId(Usersignal $userSignalId): Usersignalattribute
    {
        $this->userSignalId = $userSignalId;

        return $this;
    }

    public function getSignalAttributeId(): Signalattribute
    {
        return $this->signalAttributeId;
    }

    public function setSignalAttributeId(Signalattribute $signalAttributeId): Usersignalattribute
    {
        $this->signalAttributeId = $signalAttributeId;

        return $this;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): Usersignalattribute
    {
        $this->value = $value;

        return $this;
    }
}
