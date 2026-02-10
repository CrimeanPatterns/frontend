<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Aircraft.
 *
 * @ORM\Table(name="Aircraft")
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\AircraftRepository")
 */
class Aircraft
{
    /**
     * @var int
     * @ORM\Column(name="AircraftID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $AircraftId;

    /**
     * @var string
     * @ORM\Column(name="IataCode", type="string", length=3, nullable=false)
     */
    protected $IataCode;

    /**
     * @var string
     * @ORM\Column(name="IcaoCode", type="string", length=4, nullable=true)
     */
    protected $IcaoCode;

    /**
     * @var string
     * @ORM\Column(name="Name", type="string", length=255, nullable=false)
     */
    protected $Name;

    /**
     * @var bool
     * @ORM\Column(name="TurboProp", type="boolean", nullable=false)
     */
    protected $TurboProp;

    /**
     * @var bool
     * @ORM\Column(name="Jet", type="boolean", nullable=false)
     */
    protected $Jet;

    /**
     * @var bool
     * @ORM\Column(name="WideBody", type="boolean", nullable=false)
     */
    protected $WideBody;

    /**
     * @var bool
     * @ORM\Column(name="Regional", type="boolean", nullable=false)
     */
    protected $Regional;

    /**
     * @var string
     * @ORM\Column(name="ShortName", type="string", length=255, nullable=false)
     */
    protected $ShortName;

    /**
     * @var string
     * @ORM\Column(name="Icon", type="string", length=255, nullable=false)
     */
    protected $Icon;

    /**
     * @var \DateTime
     * @ORM\Column(name="UpdatedAt", type="datetime", length=255, nullable=false)
     */
    protected $UpdatedAt;

    /**
     * @return int
     */
    public function getAircraftId()
    {
        return $this->AircraftId;
    }

    /**
     * @return string
     */
    public function getIataCode()
    {
        return $this->IataCode;
    }

    /**
     * @param string $IataCode
     * @return $this
     */
    public function setIataCode($IataCode)
    {
        $this->IataCode = $IataCode;

        return $this;
    }

    public function getIcaoCode(): ?string
    {
        return $this->IcaoCode;
    }

    public function setIcaoCode(?string $icaoCode): self
    {
        $this->IcaoCode = $icaoCode;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->Name;
    }

    /**
     * @param string $Name
     * @return $this
     */
    public function setName($Name)
    {
        $this->Name = $Name;

        return $this;
    }

    /**
     * @return bool
     */
    public function isTurboProp()
    {
        return $this->TurboProp;
    }

    /**
     * @param bool $TurboProp
     * @return $this
     */
    public function setTurboProp($TurboProp)
    {
        $this->TurboProp = $TurboProp;

        return $this;
    }

    /**
     * @return bool
     */
    public function isJet()
    {
        return $this->Jet;
    }

    /**
     * @param bool $Jet
     * @return $this
     */
    public function setJet($Jet)
    {
        $this->Jet = $Jet;

        return $this;
    }

    /**
     * @return bool
     */
    public function isWideBody()
    {
        return $this->WideBody;
    }

    /**
     * @param bool $WideBody
     * @return $this
     */
    public function setWideBody($WideBody)
    {
        $this->WideBody = $WideBody;

        return $this;
    }

    /**
     * @return bool
     */
    public function isRegional()
    {
        return $this->Regional;
    }

    /**
     * @param bool $Regional
     * @return $this
     */
    public function setRegional($Regional)
    {
        $this->Regional = $Regional;

        return $this;
    }

    /**
     * @return string
     */
    public function getShortName()
    {
        return $this->ShortName;
    }

    /**
     * @param string $ShortName
     * @return $this
     */
    public function setShortName($ShortName)
    {
        $this->ShortName = $ShortName;

        return $this;
    }

    /**
     * @return string
     */
    public function getIcon()
    {
        return $this->Icon;
    }

    /**
     * @param string $Icon
     * @return $this
     */
    public function setIcon($Icon)
    {
        $this->Icon = $Icon;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->UpdatedAt;
    }

    /**
     * @param \DateTime $UpdatedAt
     * @return $this
     */
    public function setUpdatedAt($UpdatedAt)
    {
        $this->UpdatedAt = $UpdatedAt;

        return $this;
    }
}
