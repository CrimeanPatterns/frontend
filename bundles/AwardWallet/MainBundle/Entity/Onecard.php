<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Onecard.
 *
 * @ORM\Table(name="OneCard")
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\OnecardRepository")
 */
class Onecard
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
     * @ORM\Column(name="UserAgentID", type="integer", nullable=true)
     */
    protected $useragentid;

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
     * @var \Usr
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserID", referencedColumnName="UserID")
     * })
     */
    protected $userid;

    /**
     * @var \Cart
     * @ORM\ManyToOne(targetEntity="Cart")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="CartID", referencedColumnName="CartID")
     * })
     */
    protected $cartid;

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
     * Set useragentid.
     *
     * @param int $useragentid
     * @return Onecard
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
     * Set state.
     *
     * @param int $state
     * @return Onecard
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
     * @return Onecard
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
     * @return Onecard
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
     * @return Onecard
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
     * @return Onecard
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
     * @return Onecard
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
     * @return Onecard
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
     * @return Onecard
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
     * @return Onecard
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
     * @return Onecard
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
     * @return Onecard
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
     * @return Onecard
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
     * @return Onecard
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
     * @return Onecard
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
     * @return Onecard
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
     * @return Onecard
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
     * @return Onecard
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
     * @return Onecard
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
     * @return Onecard
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
     * Set userid.
     *
     * @return Onecard
     */
    public function setUserid(?Usr $userid = null)
    {
        $this->userid = $userid;

        return $this;
    }

    /**
     * Get userid.
     *
     * @return \AwardWallet\MainBundle\Entity\Usr
     */
    public function getUserid()
    {
        return $this->userid;
    }

    /**
     * Set cartid.
     *
     * @return Onecard
     */
    public function setCartid(?Cart $cartid = null)
    {
        $this->cartid = $cartid;

        return $this;
    }

    /**
     * Get cartid.
     *
     * @return \AwardWallet\MainBundle\Entity\Cart
     */
    public function getCartid()
    {
        return $this->cartid;
    }
}
