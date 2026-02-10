<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Airline.
 *
 * @ORM\Table(name="AirlineAlias")
 * @ORM\Entity
 */
class AirlineAlias
{
    /**
     * @var int
     * @ORM\Column(name="AirlineAliasID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $AirlineAliasID;

    /**
     * @var string
     * @ORM\Column(name="Alias", type="string", length=250, nullable=false)
     */
    protected $Alias;

    /**
     * @var Airline
     * @ORM\ManyToOne(targetEntity="Airline", inversedBy="Aliases")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="AirlineID", referencedColumnName="AirlineID")
     * })
     */
    protected $Airline;

    /**
     * @var \DateTime
     * @ORM\Column(name="LastUpdateDate", type="datetime", nullable=true)
     */
    protected $LastUpdateDate;

    /**
     * @return int
     */
    public function getAirlineAliasID()
    {
        return $this->AirlineAliasID;
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->Alias;
    }

    /**
     * @param string $Alias
     * @return $this
     */
    public function setAlias($Alias)
    {
        $this->Alias = $Alias;

        return $this;
    }

    /**
     * @return Airline
     */
    public function getAirline()
    {
        return $this->Airline;
    }

    /**
     * @param Airline $Airline
     * @return $this
     */
    public function setAirline($Airline)
    {
        $this->Airline = $Airline;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getLastUpdateDate()
    {
        return $this->LastUpdateDate;
    }

    /**
     * @param \DateTime $LastUpdateDate
     * @return $this
     */
    public function setLastUpdateDate($LastUpdateDate)
    {
        $this->LastUpdateDate = $LastUpdateDate;

        return $this;
    }
}
