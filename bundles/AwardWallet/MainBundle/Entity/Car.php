<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Car.
 *
 * @ORM\Table(name="Car")
 * @ORM\Entity
 */
class Car
{
    /**
     * @var int
     * @ORM\Column(name="CarID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $carid;

    /**
     * @var string
     * @ORM\Column(name="Company", type="string", length=80, nullable=false)
     */
    protected $company;

    /**
     * @var string
     * @ORM\Column(name="CarTypeLogo", type="string", length=160, nullable=true)
     */
    protected $cartypelogo;

    /**
     * @var string
     * @ORM\Column(name="CarType", type="string", length=80, nullable=false)
     */
    protected $cartype;

    /**
     * @var string
     * @ORM\Column(name="CarName", type="string", length=80, nullable=false)
     */
    protected $carname;

    /**
     * @var float
     * @ORM\Column(name="Total", type="float", nullable=false)
     */
    protected $total = 0;

    /**
     * @var string
     * @ORM\Column(name="PickUpCode", type="string", length=80, nullable=true)
     */
    protected $pickupcode;

    /**
     * @var string
     * @ORM\Column(name="PickUpAddress", type="string", length=80, nullable=true)
     */
    protected $pickupaddress;

    /**
     * @var string
     * @ORM\Column(name="PickUpCity", type="string", length=80, nullable=true)
     */
    protected $pickupcity;

    /**
     * @var string
     * @ORM\Column(name="PickUpState", type="string", length=80, nullable=true)
     */
    protected $pickupstate;

    /**
     * @var string
     * @ORM\Column(name="PickUpZipCode", type="string", length=80, nullable=true)
     */
    protected $pickupzipcode;

    /**
     * @var string
     * @ORM\Column(name="PickUpCountry", type="string", length=80, nullable=true)
     */
    protected $pickupcountry;

    /**
     * @var string
     * @ORM\Column(name="PickUpDescription", type="string", length=80, nullable=true)
     */
    protected $pickupdescription;

    /**
     * @var string
     * @ORM\Column(name="DropOffCode", type="string", length=80, nullable=true)
     */
    protected $dropoffcode;

    /**
     * @var string
     * @ORM\Column(name="DropOffAddress", type="string", length=80, nullable=true)
     */
    protected $dropoffaddress;

    /**
     * @var string
     * @ORM\Column(name="DropOffCity", type="string", length=80, nullable=true)
     */
    protected $dropoffcity;

    /**
     * @var string
     * @ORM\Column(name="DropOffState", type="string", length=80, nullable=true)
     */
    protected $dropoffstate;

    /**
     * @var string
     * @ORM\Column(name="DropOffZipCode", type="string", length=80, nullable=true)
     */
    protected $dropoffzipcode;

    /**
     * @var string
     * @ORM\Column(name="DropOffCountry", type="string", length=80, nullable=true)
     */
    protected $dropoffcountry;

    /**
     * @var string
     * @ORM\Column(name="DropOffDescription", type="string", length=80, nullable=true)
     */
    protected $dropoffdescription;

    /**
     * @var string
     * @ORM\Column(name="XML", type="text", nullable=true)
     */
    protected $xml;

    /**
     * @var \Carquery
     * @ORM\ManyToOne(targetEntity="Carquery")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="CarQueryID", referencedColumnName="CarQueryID")
     * })
     */
    protected $carqueryid;

    /**
     * Get carid.
     *
     * @return int
     */
    public function getCarid()
    {
        return $this->carid;
    }

    /**
     * Set company.
     *
     * @param string $company
     * @return Car
     */
    public function setCompany($company)
    {
        $this->company = $company;

        return $this;
    }

    /**
     * Get company.
     *
     * @return string
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * Set cartypelogo.
     *
     * @param string $cartypelogo
     * @return Car
     */
    public function setCartypelogo($cartypelogo)
    {
        $this->cartypelogo = $cartypelogo;

        return $this;
    }

    /**
     * Get cartypelogo.
     *
     * @return string
     */
    public function getCartypelogo()
    {
        return $this->cartypelogo;
    }

    /**
     * Set cartype.
     *
     * @param string $cartype
     * @return Car
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
     * Set carname.
     *
     * @param string $carname
     * @return Car
     */
    public function setCarname($carname)
    {
        $this->carname = $carname;

        return $this;
    }

    /**
     * Get carname.
     *
     * @return string
     */
    public function getCarname()
    {
        return $this->carname;
    }

    /**
     * Set total.
     *
     * @param float $total
     * @return Car
     */
    public function setTotal($total)
    {
        $this->total = $total;

        return $this;
    }

    /**
     * Get total.
     *
     * @return float
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * Set pickupcode.
     *
     * @param string $pickupcode
     * @return Car
     */
    public function setPickupcode($pickupcode)
    {
        $this->pickupcode = $pickupcode;

        return $this;
    }

    /**
     * Get pickupcode.
     *
     * @return string
     */
    public function getPickupcode()
    {
        return $this->pickupcode;
    }

    /**
     * Set pickupaddress.
     *
     * @param string $pickupaddress
     * @return Car
     */
    public function setPickupaddress($pickupaddress)
    {
        $this->pickupaddress = $pickupaddress;

        return $this;
    }

    /**
     * Get pickupaddress.
     *
     * @return string
     */
    public function getPickupaddress()
    {
        return $this->pickupaddress;
    }

    /**
     * Set pickupcity.
     *
     * @param string $pickupcity
     * @return Car
     */
    public function setPickupcity($pickupcity)
    {
        $this->pickupcity = $pickupcity;

        return $this;
    }

    /**
     * Get pickupcity.
     *
     * @return string
     */
    public function getPickupcity()
    {
        return $this->pickupcity;
    }

    /**
     * Set pickupstate.
     *
     * @param string $pickupstate
     * @return Car
     */
    public function setPickupstate($pickupstate)
    {
        $this->pickupstate = $pickupstate;

        return $this;
    }

    /**
     * Get pickupstate.
     *
     * @return string
     */
    public function getPickupstate()
    {
        return $this->pickupstate;
    }

    /**
     * Set pickupzipcode.
     *
     * @param string $pickupzipcode
     * @return Car
     */
    public function setPickupzipcode($pickupzipcode)
    {
        $this->pickupzipcode = $pickupzipcode;

        return $this;
    }

    /**
     * Get pickupzipcode.
     *
     * @return string
     */
    public function getPickupzipcode()
    {
        return $this->pickupzipcode;
    }

    /**
     * Set pickupcountry.
     *
     * @param string $pickupcountry
     * @return Car
     */
    public function setPickupcountry($pickupcountry)
    {
        $this->pickupcountry = $pickupcountry;

        return $this;
    }

    /**
     * Get pickupcountry.
     *
     * @return string
     */
    public function getPickupcountry()
    {
        return $this->pickupcountry;
    }

    /**
     * Set pickupdescription.
     *
     * @param string $pickupdescription
     * @return Car
     */
    public function setPickupdescription($pickupdescription)
    {
        $this->pickupdescription = $pickupdescription;

        return $this;
    }

    /**
     * Get pickupdescription.
     *
     * @return string
     */
    public function getPickupdescription()
    {
        return $this->pickupdescription;
    }

    /**
     * Set dropoffcode.
     *
     * @param string $dropoffcode
     * @return Car
     */
    public function setDropoffcode($dropoffcode)
    {
        $this->dropoffcode = $dropoffcode;

        return $this;
    }

    /**
     * Get dropoffcode.
     *
     * @return string
     */
    public function getDropoffcode()
    {
        return $this->dropoffcode;
    }

    /**
     * Set dropoffaddress.
     *
     * @param string $dropoffaddress
     * @return Car
     */
    public function setDropoffaddress($dropoffaddress)
    {
        $this->dropoffaddress = $dropoffaddress;

        return $this;
    }

    /**
     * Get dropoffaddress.
     *
     * @return string
     */
    public function getDropoffaddress()
    {
        return $this->dropoffaddress;
    }

    /**
     * Set dropoffcity.
     *
     * @param string $dropoffcity
     * @return Car
     */
    public function setDropoffcity($dropoffcity)
    {
        $this->dropoffcity = $dropoffcity;

        return $this;
    }

    /**
     * Get dropoffcity.
     *
     * @return string
     */
    public function getDropoffcity()
    {
        return $this->dropoffcity;
    }

    /**
     * Set dropoffstate.
     *
     * @param string $dropoffstate
     * @return Car
     */
    public function setDropoffstate($dropoffstate)
    {
        $this->dropoffstate = $dropoffstate;

        return $this;
    }

    /**
     * Get dropoffstate.
     *
     * @return string
     */
    public function getDropoffstate()
    {
        return $this->dropoffstate;
    }

    /**
     * Set dropoffzipcode.
     *
     * @param string $dropoffzipcode
     * @return Car
     */
    public function setDropoffzipcode($dropoffzipcode)
    {
        $this->dropoffzipcode = $dropoffzipcode;

        return $this;
    }

    /**
     * Get dropoffzipcode.
     *
     * @return string
     */
    public function getDropoffzipcode()
    {
        return $this->dropoffzipcode;
    }

    /**
     * Set dropoffcountry.
     *
     * @param string $dropoffcountry
     * @return Car
     */
    public function setDropoffcountry($dropoffcountry)
    {
        $this->dropoffcountry = $dropoffcountry;

        return $this;
    }

    /**
     * Get dropoffcountry.
     *
     * @return string
     */
    public function getDropoffcountry()
    {
        return $this->dropoffcountry;
    }

    /**
     * Set dropoffdescription.
     *
     * @param string $dropoffdescription
     * @return Car
     */
    public function setDropoffdescription($dropoffdescription)
    {
        $this->dropoffdescription = $dropoffdescription;

        return $this;
    }

    /**
     * Get dropoffdescription.
     *
     * @return string
     */
    public function getDropoffdescription()
    {
        return $this->dropoffdescription;
    }

    /**
     * Set xml.
     *
     * @param string $xml
     * @return Car
     */
    public function setXml($xml)
    {
        $this->xml = $xml;

        return $this;
    }

    /**
     * Get xml.
     *
     * @return string
     */
    public function getXml()
    {
        return $this->xml;
    }

    /**
     * Set carqueryid.
     *
     * @return Car
     */
    public function setCarqueryid(?Carquery $carqueryid = null)
    {
        $this->carqueryid = $carqueryid;

        return $this;
    }

    /**
     * Get carqueryid.
     *
     * @return \AwardWallet\MainBundle\Entity\Carquery
     */
    public function getCarqueryid()
    {
        return $this->carqueryid;
    }
}
