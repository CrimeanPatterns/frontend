<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Flightquery.
 *
 * @ORM\Table(name="FlightQuery")
 * @ORM\Entity
 */
class Flightquery
{
    /**
     * @var int
     * @ORM\Column(name="FlightQueryID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $flightqueryid;

    /**
     * @var string
     * @ORM\Column(name="FromAirport", type="string", length=3, nullable=false)
     */
    protected $fromairport;

    /**
     * @var string
     * @ORM\Column(name="ToAirport", type="string", length=3, nullable=false)
     */
    protected $toairport;

    /**
     * @var \DateTime
     * @ORM\Column(name="DepartDate", type="datetime", nullable=false)
     */
    protected $departdate;

    /**
     * @var \DateTime
     * @ORM\Column(name="ArriveDate", type="datetime", nullable=false)
     */
    protected $arrivedate;

    /**
     * @var string
     * @ORM\Column(name="Class", type="string", length=1, nullable=false)
     */
    protected $class;

    /**
     * @var string
     * @ORM\Column(name="Type", type="string", length=2, nullable=false)
     */
    protected $type;

    /**
     * @var int
     * @ORM\Column(name="Travelers", type="integer", nullable=false)
     */
    protected $travelers = 0;

    /**
     * @var string
     * @ORM\Column(name="SessionId", type="string", length=80, nullable=false)
     */
    protected $sessionid;

    /**
     * @var \DateTime
     * @ORM\Column(name="CreationDate", type="datetime", nullable=false)
     */
    protected $creationdate;

    /**
     * @var bool
     * @ORM\Column(name="SearchNearby", type="boolean", nullable=false)
     */
    protected $searchnearby = false;

    /**
     * @var string
     * @ORM\Column(name="Airlines", type="string", length=250, nullable=false)
     */
    protected $airlines;

    /**
     * @var int
     * @ORM\Column(name="Children", type="integer", nullable=false)
     */
    protected $children = 0;

    /**
     * Get flightqueryid.
     *
     * @return int
     */
    public function getFlightqueryid()
    {
        return $this->flightqueryid;
    }

    /**
     * Set fromairport.
     *
     * @param string $fromairport
     * @return Flightquery
     */
    public function setFromairport($fromairport)
    {
        $this->fromairport = $fromairport;

        return $this;
    }

    /**
     * Get fromairport.
     *
     * @return string
     */
    public function getFromairport()
    {
        return $this->fromairport;
    }

    /**
     * Set toairport.
     *
     * @param string $toairport
     * @return Flightquery
     */
    public function setToairport($toairport)
    {
        $this->toairport = $toairport;

        return $this;
    }

    /**
     * Get toairport.
     *
     * @return string
     */
    public function getToairport()
    {
        return $this->toairport;
    }

    /**
     * Set departdate.
     *
     * @param \DateTime $departdate
     * @return Flightquery
     */
    public function setDepartdate($departdate)
    {
        $this->departdate = $departdate;

        return $this;
    }

    /**
     * Get departdate.
     *
     * @return \DateTime
     */
    public function getDepartdate()
    {
        return $this->departdate;
    }

    /**
     * Set arrivedate.
     *
     * @param \DateTime $arrivedate
     * @return Flightquery
     */
    public function setArrivedate($arrivedate)
    {
        $this->arrivedate = $arrivedate;

        return $this;
    }

    /**
     * Get arrivedate.
     *
     * @return \DateTime
     */
    public function getArrivedate()
    {
        return $this->arrivedate;
    }

    /**
     * Set class.
     *
     * @param string $class
     * @return Flightquery
     */
    public function setClass($class)
    {
        $this->class = $class;

        return $this;
    }

    /**
     * Get class.
     *
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * Set type.
     *
     * @param string $type
     * @return Flightquery
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set travelers.
     *
     * @param int $travelers
     * @return Flightquery
     */
    public function setTravelers($travelers)
    {
        $this->travelers = $travelers;

        return $this;
    }

    /**
     * Get travelers.
     *
     * @return int
     */
    public function getTravelers()
    {
        return $this->travelers;
    }

    /**
     * Set sessionid.
     *
     * @param string $sessionid
     * @return Flightquery
     */
    public function setSessionid($sessionid)
    {
        $this->sessionid = $sessionid;

        return $this;
    }

    /**
     * Get sessionid.
     *
     * @return string
     */
    public function getSessionid()
    {
        return $this->sessionid;
    }

    /**
     * Set creationdate.
     *
     * @param \DateTime $creationdate
     * @return Flightquery
     */
    public function setCreationdate($creationdate)
    {
        $this->creationdate = $creationdate;

        return $this;
    }

    /**
     * Get creationdate.
     *
     * @return \DateTime
     */
    public function getCreationdate()
    {
        return $this->creationdate;
    }

    /**
     * Set searchnearby.
     *
     * @param bool $searchnearby
     * @return Flightquery
     */
    public function setSearchnearby($searchnearby)
    {
        $this->searchnearby = $searchnearby;

        return $this;
    }

    /**
     * Get searchnearby.
     *
     * @return bool
     */
    public function getSearchnearby()
    {
        return $this->searchnearby;
    }

    /**
     * Set airlines.
     *
     * @param string $airlines
     * @return Flightquery
     */
    public function setAirlines($airlines)
    {
        $this->airlines = $airlines;

        return $this;
    }

    /**
     * Get airlines.
     *
     * @return string
     */
    public function getAirlines()
    {
        return $this->airlines;
    }

    /**
     * Set children.
     *
     * @param int $children
     * @return Flightquery
     */
    public function setChildren($children)
    {
        $this->children = $children;

        return $this;
    }

    /**
     * Get children.
     *
     * @return int
     */
    public function getChildren()
    {
        return $this->children;
    }
}
