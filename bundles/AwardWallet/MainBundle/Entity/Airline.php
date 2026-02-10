<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;

/**
 * Airline.
 *
 * @ORM\Table(name="Airline")
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\AirlineRepository")
 * @JMS\ExclusionPolicy("All")
 */
class Airline
{
    /**
     * @var int
     * @ORM\Column(name="AirlineID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $airlineid;

    /**
     * @var string
     * @JMS\Expose()
     * @ORM\Column(name="Name", type="string", length=250, nullable=true)
     */
    protected $name;

    /**
     * @var string
     * @JMS\Expose()
     * @ORM\Column(name="Code", type="string", length=2, nullable=true)
     */
    protected $code;

    /**
     * @var string
     * @ORM\Column(name="ICAO", type="string", length=3, nullable=true)
     */
    protected $icao;

    /**
     * @var string
     * @ORM\Column(name="FSCode", type="string", length=5)
     */
    protected $fsCode;

    /**
     * @var bool
     * @ORM\Column(name="Active", type="boolean")
     */
    protected $active;

    /**
     * @var \DateTime
     * @ORM\Column(name="LastUpdateDate", type="datetime", nullable=true)
     */
    protected $lastupdatedate;

    /**
     * @var AirlineAlias[]|ArrayCollection
     * @ORM\OneToMany(targetEntity="AirlineAlias", mappedBy="Airline", cascade={"persist", "remove"})
     */
    protected $Aliases;

    /**
     * @ORM\ManyToOne(targetEntity="Alliance")
     * @ORM\JoinColumn(name="AllianceID", referencedColumnName="AllianceID")
     */
    private ?Alliance $alliance;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->Aliases = new ArrayCollection();
    }

    public function __toString()
    {
        if (empty($this->getCode())) {
            return $this->getName();
        }

        return $this->getName() . ' (' . $this->getCode() . ')';
    }

    /**
     * Get airlineid.
     *
     * @return int
     */
    public function getAirlineid()
    {
        return $this->airlineid;
    }

    /**
     * Set name.
     *
     * @param string $name
     * @return Airline
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set code.
     *
     * @param string $code
     * @return Airline
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code.
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set icao.
     *
     * @param string $icao
     * @return Airline
     */
    public function setIcao($icao)
    {
        $this->icao = $icao;

        return $this;
    }

    /**
     * Get icao.
     *
     * @return string
     */
    public function getIcao()
    {
        return $this->icao;
    }

    /**
     * Set lastupdatedate.
     *
     * @param \DateTime $lastupdatedate
     * @return Airline
     */
    public function setLastupdatedate($lastupdatedate)
    {
        $this->lastupdatedate = $lastupdatedate;

        return $this;
    }

    /**
     * Get lastupdatedate.
     *
     * @return \DateTime
     */
    public function getLastupdatedate()
    {
        return $this->lastupdatedate;
    }

    /**
     * @return AirlineAlias[]|ArrayCollection
     */
    public function getAliases()
    {
        return $this->Aliases;
    }

    /**
     * @param AirlineAlias[]|ArrayCollection $Aliases
     * @return $this
     */
    public function setAliases($Aliases)
    {
        $this->Aliases = $Aliases;

        return $this;
    }

    /**
     * @return $this
     */
    public function addLevel(AirlineAlias $AirlineAlias)
    {
        $this->Aliases[] = $AirlineAlias;

        return $this;
    }

    /**
     * @return $this
     */
    public function removeLevel(AirlineAlias $AirlineAlias)
    {
        $this->Aliases->removeElement($AirlineAlias);

        return $this;
    }

    public function getFsCode(): ?string
    {
        return $this->fsCode;
    }

    public function setFsCode(string $fsCode): self
    {
        $this->fsCode = $fsCode;

        return $this;
    }

    public function getAlliance(): ?Alliance
    {
        return $this->alliance;
    }

    public function setAlliance(?Alliance $alliance): self
    {
        $this->alliance = $alliance;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): Airline
    {
        $this->active = $active;

        return $this;
    }
}
