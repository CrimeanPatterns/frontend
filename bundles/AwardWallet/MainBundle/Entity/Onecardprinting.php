<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Onecardprinting.
 *
 * @ORM\Table(name="OneCardPrinting")
 * @ORM\Entity
 */
class Onecardprinting
{
    /**
     * @var int
     * @ORM\Column(name="OneCardID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $onecardid;

    /**
     * @var int
     * @ORM\Column(name="UserID", type="integer", nullable=false)
     */
    protected $userid;

    /**
     * @var int
     * @ORM\Column(name="UserAgentID", type="integer", nullable=true)
     */
    protected $useragentid;

    /**
     * @var int
     * @ORM\Column(name="CartID", type="integer", nullable=false)
     */
    protected $cartid;

    /**
     * @var int
     * @ORM\Column(name="State", type="integer", nullable=false)
     */
    protected $state;

    /**
     * @var \DateTime
     * @ORM\Column(name="OrderDate", type="datetime", nullable=false)
     */
    protected $orderdate;

    /**
     * @var \DateTime
     * @ORM\Column(name="PrintDate", type="datetime", nullable=true)
     */
    protected $printdate;

    /**
     * @var string
     * @ORM\Column(name="FullName", type="string", length=250, nullable=false)
     */
    protected $fullname;

    /**
     * @var string
     * @ORM\Column(name="TotalMiles", type="string", length=80, nullable=false)
     */
    protected $totalmiles;

    /**
     * @var float
     * @ORM\Column(name="TotalMilesNum", type="float", nullable=false)
     */
    protected $totalmilesnum;

    /**
     * @var string
     * @ORM\Column(name="TxtDate", type="string", length=250, nullable=false)
     */
    protected $txtdate;

    /**
     * @var string
     * @ORM\Column(name="CardIndex", type="string", length=40, nullable=true)
     */
    protected $cardindex;

    /**
     * @var string
     * @ORM\Column(name="Track1", type="text", nullable=true)
     */
    protected $track1;

    /**
     * @var string
     * @ORM\Column(name="AccFront", type="text", nullable=false)
     */
    protected $accfront;

    /**
     * @var string
     * @ORM\Column(name="PFront", type="text", nullable=false)
     */
    protected $pfront;

    /**
     * @var string
     * @ORM\Column(name="AFront", type="text", nullable=false)
     */
    protected $afront;

    /**
     * @var string
     * @ORM\Column(name="SFront", type="text", nullable=false)
     */
    protected $sfront;

    /**
     * @var string
     * @ORM\Column(name="PhFront", type="text", nullable=false)
     */
    protected $phfront;

    /**
     * @var string
     * @ORM\Column(name="AccBack", type="text", nullable=false)
     */
    protected $accback;

    /**
     * @var string
     * @ORM\Column(name="PBack", type="text", nullable=false)
     */
    protected $pback;

    /**
     * @var string
     * @ORM\Column(name="ABack", type="text", nullable=false)
     */
    protected $aback;

    /**
     * @var string
     * @ORM\Column(name="SBack", type="text", nullable=false)
     */
    protected $sback;

    /**
     * @var string
     * @ORM\Column(name="PhBack", type="text", nullable=false)
     */
    protected $phback;

    /**
     * @var string
     * @ORM\Column(name="ShipFirstName", type="string", length=40, nullable=false)
     */
    protected $shipfirstname;

    /**
     * @var string
     * @ORM\Column(name="ShipLastName", type="string", length=40, nullable=false)
     */
    protected $shiplastname;

    /**
     * @var string
     * @ORM\Column(name="ShipAddress1", type="string", length=250, nullable=false)
     */
    protected $shipaddress1;

    /**
     * @var string
     * @ORM\Column(name="ShipAddress2", type="string", length=250, nullable=true)
     */
    protected $shipaddress2;

    /**
     * @var string
     * @ORM\Column(name="ShipCity", type="string", length=80, nullable=false)
     */
    protected $shipcity;

    /**
     * @var string
     * @ORM\Column(name="ShipZip", type="string", length=40, nullable=false)
     */
    protected $shipzip;

    /**
     * @var string
     * @ORM\Column(name="ShipCountryName", type="string", length=120, nullable=false)
     */
    protected $shipcountryname;

    /**
     * @var string
     * @ORM\Column(name="ShipCountryCode", type="string", length=40, nullable=false)
     */
    protected $shipcountrycode;

    /**
     * @var string
     * @ORM\Column(name="ShipStateName", type="string", length=250, nullable=false)
     */
    protected $shipstatename;

    /**
     * @var string
     * @ORM\Column(name="ShipStateCode", type="string", length=40, nullable=false)
     */
    protected $shipstatecode;

    /**
     * Get onecardid.
     *
     * @return int
     */
    public function getOnecardid()
    {
        return $this->onecardid;
    }

    /**
     * Set userid.
     *
     * @param int $userid
     * @return Onecardprinting
     */
    public function setUserid($userid)
    {
        $this->userid = $userid;

        return $this;
    }

    /**
     * Get userid.
     *
     * @return int
     */
    public function getUserid()
    {
        return $this->userid;
    }

    /**
     * Set useragentid.
     *
     * @param int $useragentid
     * @return Onecardprinting
     */
    public function setUseragentid($useragentid)
    {
        $this->useragentid = $useragentid;

        return $this;
    }

    /**
     * Get useragentid.
     *
     * @return int
     */
    public function getUseragentid()
    {
        return $this->useragentid;
    }

    /**
     * Set cartid.
     *
     * @param int $cartid
     * @return Onecardprinting
     */
    public function setCartid($cartid)
    {
        $this->cartid = $cartid;

        return $this;
    }

    /**
     * Get cartid.
     *
     * @return int
     */
    public function getCartid()
    {
        return $this->cartid;
    }

    /**
     * Set state.
     *
     * @param int $state
     * @return Onecardprinting
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Get state.
     *
     * @return int
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Set orderdate.
     *
     * @param \DateTime $orderdate
     * @return Onecardprinting
     */
    public function setOrderdate($orderdate)
    {
        $this->orderdate = $orderdate;

        return $this;
    }

    /**
     * Get orderdate.
     *
     * @return \DateTime
     */
    public function getOrderdate()
    {
        return $this->orderdate;
    }

    /**
     * Set printdate.
     *
     * @param \DateTime $printdate
     * @return Onecardprinting
     */
    public function setPrintdate($printdate)
    {
        $this->printdate = $printdate;

        return $this;
    }

    /**
     * Get printdate.
     *
     * @return \DateTime
     */
    public function getPrintdate()
    {
        return $this->printdate;
    }

    /**
     * Set fullname.
     *
     * @param string $fullname
     * @return Onecardprinting
     */
    public function setFullname($fullname)
    {
        $this->fullname = $fullname;

        return $this;
    }

    /**
     * Get fullname.
     *
     * @return string
     */
    public function getFullname()
    {
        return $this->fullname;
    }

    /**
     * Set totalmiles.
     *
     * @param string $totalmiles
     * @return Onecardprinting
     */
    public function setTotalmiles($totalmiles)
    {
        $this->totalmiles = $totalmiles;

        return $this;
    }

    /**
     * Get totalmiles.
     *
     * @return string
     */
    public function getTotalmiles()
    {
        return $this->totalmiles;
    }

    /**
     * Set totalmilesnum.
     *
     * @param float $totalmilesnum
     * @return Onecardprinting
     */
    public function setTotalmilesnum($totalmilesnum)
    {
        $this->totalmilesnum = $totalmilesnum;

        return $this;
    }

    /**
     * Get totalmilesnum.
     *
     * @return float
     */
    public function getTotalmilesnum()
    {
        return $this->totalmilesnum;
    }

    /**
     * Set txtdate.
     *
     * @param string $txtdate
     * @return Onecardprinting
     */
    public function setTxtdate($txtdate)
    {
        $this->txtdate = $txtdate;

        return $this;
    }

    /**
     * Get txtdate.
     *
     * @return string
     */
    public function getTxtdate()
    {
        return $this->txtdate;
    }

    /**
     * Set cardindex.
     *
     * @param string $cardindex
     * @return Onecardprinting
     */
    public function setCardindex($cardindex)
    {
        $this->cardindex = $cardindex;

        return $this;
    }

    /**
     * Get cardindex.
     *
     * @return string
     */
    public function getCardindex()
    {
        return $this->cardindex;
    }

    /**
     * Set track1.
     *
     * @param string $track1
     * @return Onecardprinting
     */
    public function setTrack1($track1)
    {
        $this->track1 = $track1;

        return $this;
    }

    /**
     * Get track1.
     *
     * @return string
     */
    public function getTrack1()
    {
        return $this->track1;
    }

    /**
     * Set accfront.
     *
     * @param string $accfront
     * @return Onecardprinting
     */
    public function setAccfront($accfront)
    {
        $this->accfront = $accfront;

        return $this;
    }

    /**
     * Get accfront.
     *
     * @return string
     */
    public function getAccfront()
    {
        return $this->accfront;
    }

    /**
     * Set pfront.
     *
     * @param string $pfront
     * @return Onecardprinting
     */
    public function setPfront($pfront)
    {
        $this->pfront = $pfront;

        return $this;
    }

    /**
     * Get pfront.
     *
     * @return string
     */
    public function getPfront()
    {
        return $this->pfront;
    }

    /**
     * Set afront.
     *
     * @param string $afront
     * @return Onecardprinting
     */
    public function setAfront($afront)
    {
        $this->afront = $afront;

        return $this;
    }

    /**
     * Get afront.
     *
     * @return string
     */
    public function getAfront()
    {
        return $this->afront;
    }

    /**
     * Set sfront.
     *
     * @param string $sfront
     * @return Onecardprinting
     */
    public function setSfront($sfront)
    {
        $this->sfront = $sfront;

        return $this;
    }

    /**
     * Get sfront.
     *
     * @return string
     */
    public function getSfront()
    {
        return $this->sfront;
    }

    /**
     * Set phfront.
     *
     * @param string $phfront
     * @return Onecardprinting
     */
    public function setPhfront($phfront)
    {
        $this->phfront = $phfront;

        return $this;
    }

    /**
     * Get phfront.
     *
     * @return string
     */
    public function getPhfront()
    {
        return $this->phfront;
    }

    /**
     * Set accback.
     *
     * @param string $accback
     * @return Onecardprinting
     */
    public function setAccback($accback)
    {
        $this->accback = $accback;

        return $this;
    }

    /**
     * Get accback.
     *
     * @return string
     */
    public function getAccback()
    {
        return $this->accback;
    }

    /**
     * Set pback.
     *
     * @param string $pback
     * @return Onecardprinting
     */
    public function setPback($pback)
    {
        $this->pback = $pback;

        return $this;
    }

    /**
     * Get pback.
     *
     * @return string
     */
    public function getPback()
    {
        return $this->pback;
    }

    /**
     * Set aback.
     *
     * @param string $aback
     * @return Onecardprinting
     */
    public function setAback($aback)
    {
        $this->aback = $aback;

        return $this;
    }

    /**
     * Get aback.
     *
     * @return string
     */
    public function getAback()
    {
        return $this->aback;
    }

    /**
     * Set sback.
     *
     * @param string $sback
     * @return Onecardprinting
     */
    public function setSback($sback)
    {
        $this->sback = $sback;

        return $this;
    }

    /**
     * Get sback.
     *
     * @return string
     */
    public function getSback()
    {
        return $this->sback;
    }

    /**
     * Set phback.
     *
     * @param string $phback
     * @return Onecardprinting
     */
    public function setPhback($phback)
    {
        $this->phback = $phback;

        return $this;
    }

    /**
     * Get phback.
     *
     * @return string
     */
    public function getPhback()
    {
        return $this->phback;
    }

    /**
     * Set shipfirstname.
     *
     * @param string $shipfirstname
     * @return Onecardprinting
     */
    public function setShipfirstname($shipfirstname)
    {
        $this->shipfirstname = $shipfirstname;

        return $this;
    }

    /**
     * Get shipfirstname.
     *
     * @return string
     */
    public function getShipfirstname()
    {
        return $this->shipfirstname;
    }

    /**
     * Set shiplastname.
     *
     * @param string $shiplastname
     * @return Onecardprinting
     */
    public function setShiplastname($shiplastname)
    {
        $this->shiplastname = $shiplastname;

        return $this;
    }

    /**
     * Get shiplastname.
     *
     * @return string
     */
    public function getShiplastname()
    {
        return $this->shiplastname;
    }

    /**
     * Set shipaddress1.
     *
     * @param string $shipaddress1
     * @return Onecardprinting
     */
    public function setShipaddress1($shipaddress1)
    {
        $this->shipaddress1 = $shipaddress1;

        return $this;
    }

    /**
     * Get shipaddress1.
     *
     * @return string
     */
    public function getShipaddress1()
    {
        return $this->shipaddress1;
    }

    /**
     * Set shipaddress2.
     *
     * @param string $shipaddress2
     * @return Onecardprinting
     */
    public function setShipaddress2($shipaddress2)
    {
        $this->shipaddress2 = $shipaddress2;

        return $this;
    }

    /**
     * Get shipaddress2.
     *
     * @return string
     */
    public function getShipaddress2()
    {
        return $this->shipaddress2;
    }

    /**
     * Set shipcity.
     *
     * @param string $shipcity
     * @return Onecardprinting
     */
    public function setShipcity($shipcity)
    {
        $this->shipcity = $shipcity;

        return $this;
    }

    /**
     * Get shipcity.
     *
     * @return string
     */
    public function getShipcity()
    {
        return $this->shipcity;
    }

    /**
     * Set shipzip.
     *
     * @param string $shipzip
     * @return Onecardprinting
     */
    public function setShipzip($shipzip)
    {
        $this->shipzip = $shipzip;

        return $this;
    }

    /**
     * Get shipzip.
     *
     * @return string
     */
    public function getShipzip()
    {
        return $this->shipzip;
    }

    /**
     * Set shipcountryname.
     *
     * @param string $shipcountryname
     * @return Onecardprinting
     */
    public function setShipcountryname($shipcountryname)
    {
        $this->shipcountryname = $shipcountryname;

        return $this;
    }

    /**
     * Get shipcountryname.
     *
     * @return string
     */
    public function getShipcountryname()
    {
        return $this->shipcountryname;
    }

    /**
     * Set shipcountrycode.
     *
     * @param string $shipcountrycode
     * @return Onecardprinting
     */
    public function setShipcountrycode($shipcountrycode)
    {
        $this->shipcountrycode = $shipcountrycode;

        return $this;
    }

    /**
     * Get shipcountrycode.
     *
     * @return string
     */
    public function getShipcountrycode()
    {
        return $this->shipcountrycode;
    }

    /**
     * Set shipstatename.
     *
     * @param string $shipstatename
     * @return Onecardprinting
     */
    public function setShipstatename($shipstatename)
    {
        $this->shipstatename = $shipstatename;

        return $this;
    }

    /**
     * Get shipstatename.
     *
     * @return string
     */
    public function getShipstatename()
    {
        return $this->shipstatename;
    }

    /**
     * Set shipstatecode.
     *
     * @param string $shipstatecode
     * @return Onecardprinting
     */
    public function setShipstatecode($shipstatecode)
    {
        $this->shipstatecode = $shipstatecode;

        return $this;
    }

    /**
     * Get shipstatecode.
     *
     * @return string
     */
    public function getShipstatecode()
    {
        return $this->shipstatecode;
    }
}
