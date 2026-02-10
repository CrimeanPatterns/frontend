<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Carquery.
 *
 * @ORM\Table(name="CarQuery")
 * @ORM\Entity
 */
class Carquery
{
    /**
     * @var int
     * @ORM\Column(name="CarQueryID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $carqueryid;

    /**
     * @var \DateTime
     * @ORM\Column(name="CreationDate", type="datetime", nullable=false)
     */
    protected $creationdate;

    /**
     * @var string
     * @ORM\Column(name="PickUp", type="string", length=80, nullable=false)
     */
    protected $pickup;

    /**
     * @var string
     * @ORM\Column(name="DropOff", type="string", length=80, nullable=false)
     */
    protected $dropoff;

    /**
     * @var \DateTime
     * @ORM\Column(name="PickUpDate", type="datetime", nullable=false)
     */
    protected $pickupdate;

    /**
     * @var \DateTime
     * @ORM\Column(name="DropOffDate", type="datetime", nullable=false)
     */
    protected $dropoffdate;

    /**
     * @var string
     * @ORM\Column(name="CarType", type="string", length=80, nullable=false)
     */
    protected $cartype;

    /**
     * @var bool
     * @ORM\Column(name="SearchNearby", type="boolean", nullable=false)
     */
    protected $searchnearby = false;

    /**
     * @var string
     * @ORM\Column(name="Providers", type="string", length=250, nullable=true)
     */
    protected $providers;

    public function __construct()
    {
        $this->creationdate = new \DateTime();
        $this->pickupdate = new \DateTime();
        $this->dropoffdate = new \DateTime();
    }

    /**
     * Get carqueryid.
     *
     * @return int
     */
    public function getCarqueryid()
    {
        return $this->carqueryid;
    }

    /**
     * Set creationdate.
     *
     * @param \DateTime $creationdate
     * @return Carquery
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
     * Set pickup.
     *
     * @param string $pickup
     * @return Carquery
     */
    public function setPickup($pickup)
    {
        $this->pickup = $pickup;

        return $this;
    }

    /**
     * Get pickup.
     *
     * @return string
     */
    public function getPickup()
    {
        return $this->pickup;
    }

    /**
     * Set dropoff.
     *
     * @param string $dropoff
     * @return Carquery
     */
    public function setDropoff($dropoff)
    {
        $this->dropoff = $dropoff;

        return $this;
    }

    /**
     * Get dropoff.
     *
     * @return string
     */
    public function getDropoff()
    {
        return $this->dropoff;
    }

    /**
     * Set pickupdate.
     *
     * @param \DateTime $pickupdate
     * @return Carquery
     */
    public function setPickupdate($pickupdate)
    {
        $this->pickupdate = $pickupdate;

        return $this;
    }

    /**
     * Get pickupdate.
     *
     * @return \DateTime
     */
    public function getPickupdate()
    {
        return $this->pickupdate;
    }

    /**
     * Set dropoffdate.
     *
     * @param \DateTime $dropoffdate
     * @return Carquery
     */
    public function setDropoffdate($dropoffdate)
    {
        $this->dropoffdate = $dropoffdate;

        return $this;
    }

    /**
     * Get dropoffdate.
     *
     * @return \DateTime
     */
    public function getDropoffdate()
    {
        return $this->dropoffdate;
    }

    /**
     * Set cartype.
     *
     * @param string $cartype
     * @return Carquery
     */
    public function setCartype($cartype)
    {
        $this->cartype = $cartype;

        return $this;
    }

    /**
     * Get cartype.
     *
     * @return string
     */
    public function getCartype()
    {
        return $this->cartype;
    }

    /**
     * Set searchnearby.
     *
     * @param bool $searchnearby
     * @return Carquery
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
     * Set providers.
     *
     * @param string $providers
     * @return Carquery
     */
    public function setProviders($providers)
    {
        $this->providers = $providers;

        return $this;
    }

    /**
     * Get providers.
     *
     * @return string
     */
    public function getProviders()
    {
        return $this->providers;
    }
}
