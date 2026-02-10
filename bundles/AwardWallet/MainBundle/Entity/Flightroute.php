<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Flightroute.
 *
 * @ORM\Table(name="FlightRoute")
 * @ORM\Entity
 */
class Flightroute
{
    /**
     * @var string
     * @ORM\Column(name="FlightNumber", type="string", length=10, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $flightnumber;

    /**
     * @var string
     * @ORM\Column(name="DepCode", type="string", length=3, nullable=false)
     */
    protected $depcode;

    /**
     * @var string
     * @ORM\Column(name="ArrCode", type="string", length=3, nullable=false)
     */
    protected $arrcode;

    /**
     * Get flightnumber.
     *
     * @return string
     */
    public function getFlightnumber()
    {
        return $this->flightnumber;
    }

    /**
     * Set depcode.
     *
     * @param string $depcode
     * @return Flightroute
     */
    public function setDepcode($depcode)
    {
        $this->depcode = $depcode;

        return $this;
    }

    /**
     * Get depcode.
     *
     * @return string
     */
    public function getDepcode()
    {
        return $this->depcode;
    }

    /**
     * Set arrcode.
     *
     * @param string $arrcode
     * @return Flightroute
     */
    public function setArrcode($arrcode)
    {
        $this->arrcode = $arrcode;

        return $this;
    }

    /**
     * Get arrcode.
     *
     * @return string
     */
    public function getArrcode()
    {
        return $this->arrcode;
    }
}
