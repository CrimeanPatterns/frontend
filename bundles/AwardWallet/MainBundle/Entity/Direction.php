<?php

namespace AwardWallet\MainBundle\Entity;

use AwardWallet\Common\Entity\Geotag;
use Doctrine\ORM\Mapping as ORM;

/**
 * Direction.
 *
 * @ORM\Table(name="Direction")
 * @ORM\Entity()
 */
class Direction
{
    /**
     * @var int
     * @ORM\Column(name="DirectionID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $directionid;

    /**
     * @var \DateTime
     * @ORM\Column(name="StartDate", type="datetime", nullable=true)
     */
    protected $startdate;

    /**
     * @var string
     * @ORM\Column(name="Name", type="string", length=80, nullable=true)
     */
    protected $name;

    /**
     * @var string
     * @ORM\Column(name="StartAddress", type="string", length=250, nullable=false)
     */
    protected $startaddress;

    /**
     * @var string
     * @ORM\Column(name="EndAddress", type="string", length=250, nullable=false)
     */
    protected $endaddress;

    /**
     * @var int
     * @ORM\Column(name="Hidden", type="integer", nullable=false)
     */
    protected $hidden;

    /**
     * @var int
     * @ORM\Column(name="AccountID", type="integer", nullable=true)
     */
    protected $accountid;

    /**
     * @var int
     * @ORM\Column(name="Moved", type="integer", nullable=false)
     */
    protected $moved = 0;

    /**
     * @var int
     * @ORM\Column(name="Parsed", type="integer", nullable=false)
     */
    protected $parsed = 0;

    /**
     * @var string
     * @ORM\Column(name="StartAddressFound", type="string", length=250, nullable=true)
     */
    protected $startaddressfound;

    /**
     * @var string
     * @ORM\Column(name="EndAddressFound", type="string", length=250, nullable=true)
     */
    protected $endaddressfound;

    /**
     * @var \DateTime
     * @ORM\Column(name="UpdateDate", type="datetime", nullable=true)
     */
    protected $updatedate;

    /**
     * @var \DateTime
     * @ORM\Column(name="CreateDate", type="datetime", nullable=false)
     */
    protected $createdate;

    /**
     * @var string
     * @ORM\Column(name="FromKind", type="string", length=1, nullable=true)
     */
    protected $fromkind;

    /**
     * @var int
     * @ORM\Column(name="FromID", type="integer", nullable=true)
     */
    protected $fromid;

    /**
     * @var string
     * @ORM\Column(name="ToKind", type="string", length=1, nullable=true)
     */
    protected $tokind;

    /**
     * @var int
     * @ORM\Column(name="ToID", type="integer", nullable=true)
     */
    protected $toid;

    /**
     * @var string
     * @ORM\Column(name="ConfFields", type="string", length=250, nullable=true)
     */
    protected $conffields;

    /**
     * @var bool
     * @ORM\Column(name="Copied", type="boolean", nullable=false)
     */
    protected $copied = false;

    /**
     * @var bool
     * @ORM\Column(name="Modified", type="boolean", nullable=false)
     */
    protected $modified = false;

    /**
     * @var int
     * @ORM\Column(name="PlanIndex", type="integer", nullable=false)
     */
    protected $planindex = 0;

    /**
     * @var string
     * @ORM\Column(name="ShareCode", type="string", length=32, nullable=true)
     */
    protected $sharecode;

    /**
     * @var \Travelplan
     * @ORM\ManyToOne(targetEntity="Travelplan")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="TravelPlanID", referencedColumnName="TravelPlanID")
     * })
     */
    protected $travelplanid;

    /**
     * @var \Usr
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserID", referencedColumnName="UserID")
     * })
     */
    protected $userid;

    /**
     * @var \Useragent
     * @ORM\ManyToOne(targetEntity="Useragent")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserAgentID", referencedColumnName="UserAgentID")
     * })
     */
    protected $useragentid;

    /**
     * @var Geotag
     * @ORM\ManyToOne(targetEntity="\AwardWallet\Common\Entity\Geotag")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="EndGeoTagID", referencedColumnName="GeoTagID")
     * })
     */
    protected $endgeotagid;

    /**
     * @var Geotag
     * @ORM\ManyToOne(targetEntity="\AwardWallet\Common\Entity\Geotag")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="StartGeoTagID", referencedColumnName="GeoTagID")
     * })
     */
    protected $startgeotagid;

    /**
     * Get directionid.
     *
     * @return int
     */
    public function getDirectionid()
    {
        return $this->directionid;
    }

    /**
     * Set startdate.
     *
     * @param \DateTime $startdate
     * @return Direction
     */
    public function setStartdate($startdate)
    {
        $this->startdate = $startdate;

        return $this;
    }

    /**
     * Get startdate.
     *
     * @return \DateTime
     */
    public function getStartdate()
    {
        return $this->startdate;
    }

    /**
     * Set name.
     *
     * @param string $name
     * @return Direction
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
     * Set startaddress.
     *
     * @param string $startaddress
     * @return Direction
     */
    public function setStartaddress($startaddress)
    {
        $this->startaddress = $startaddress;

        return $this;
    }

    /**
     * Get startaddress.
     *
     * @return string
     */
    public function getStartaddress()
    {
        return $this->startaddress;
    }

    /**
     * Set endaddress.
     *
     * @param string $endaddress
     * @return Direction
     */
    public function setEndaddress($endaddress)
    {
        $this->endaddress = $endaddress;

        return $this;
    }

    /**
     * Get endaddress.
     *
     * @return string
     */
    public function getEndaddress()
    {
        return $this->endaddress;
    }

    /**
     * Set hidden.
     *
     * @param int $hidden
     * @return Direction
     */
    public function setHidden($hidden)
    {
        $this->hidden = $hidden;

        return $this;
    }

    /**
     * Get hidden.
     *
     * @return int
     */
    public function getHidden()
    {
        return $this->hidden;
    }

    /**
     * Set accountid.
     *
     * @param int $accountid
     * @return Direction
     */
    public function setAccountid($accountid)
    {
        $this->accountid = $accountid;

        return $this;
    }

    /**
     * Get accountid.
     *
     * @return int
     */
    public function getAccountid()
    {
        return $this->accountid;
    }

    /**
     * Set moved.
     *
     * @param int $moved
     * @return Direction
     */
    public function setMoved($moved)
    {
        $this->moved = $moved;

        return $this;
    }

    /**
     * Get moved.
     *
     * @return int
     */
    public function getMoved()
    {
        return $this->moved;
    }

    /**
     * Set parsed.
     *
     * @param int $parsed
     * @return Direction
     */
    public function setParsed($parsed)
    {
        $this->parsed = $parsed;

        return $this;
    }

    /**
     * Get parsed.
     *
     * @return int
     */
    public function getParsed()
    {
        return $this->parsed;
    }

    /**
     * Set startaddressfound.
     *
     * @param string $startaddressfound
     * @return Direction
     */
    public function setStartaddressfound($startaddressfound)
    {
        $this->startaddressfound = $startaddressfound;

        return $this;
    }

    /**
     * Get startaddressfound.
     *
     * @return string
     */
    public function getStartaddressfound()
    {
        return $this->startaddressfound;
    }

    /**
     * Set endaddressfound.
     *
     * @param string $endaddressfound
     * @return Direction
     */
    public function setEndaddressfound($endaddressfound)
    {
        $this->endaddressfound = $endaddressfound;

        return $this;
    }

    /**
     * Get endaddressfound.
     *
     * @return string
     */
    public function getEndaddressfound()
    {
        return $this->endaddressfound;
    }

    /**
     * Set updatedate.
     *
     * @param \DateTime $updatedate
     * @return Direction
     */
    public function setUpdatedate($updatedate)
    {
        $this->updatedate = $updatedate;

        return $this;
    }

    /**
     * Get updatedate.
     *
     * @return \DateTime
     */
    public function getUpdatedate()
    {
        return $this->updatedate;
    }

    /**
     * Set createdate.
     *
     * @param \DateTime $createdate
     * @return Direction
     */
    public function setCreatedate($createdate)
    {
        $this->createdate = $createdate;

        return $this;
    }

    /**
     * Get createdate.
     *
     * @return \DateTime
     */
    public function getCreatedate()
    {
        return $this->createdate;
    }

    /**
     * Set fromkind.
     *
     * @param string $fromkind
     * @return Direction
     */
    public function setFromkind($fromkind)
    {
        $this->fromkind = $fromkind;

        return $this;
    }

    /**
     * Get fromkind.
     *
     * @return string
     */
    public function getFromkind()
    {
        return $this->fromkind;
    }

    /**
     * Set fromid.
     *
     * @param int $fromid
     * @return Direction
     */
    public function setFromid($fromid)
    {
        $this->fromid = $fromid;

        return $this;
    }

    /**
     * Get fromid.
     *
     * @return int
     */
    public function getFromid()
    {
        return $this->fromid;
    }

    /**
     * Set tokind.
     *
     * @param string $tokind
     * @return Direction
     */
    public function setTokind($tokind)
    {
        $this->tokind = $tokind;

        return $this;
    }

    /**
     * Get tokind.
     *
     * @return string
     */
    public function getTokind()
    {
        return $this->tokind;
    }

    /**
     * Set toid.
     *
     * @param int $toid
     * @return Direction
     */
    public function setToid($toid)
    {
        $this->toid = $toid;

        return $this;
    }

    /**
     * Get toid.
     *
     * @return int
     */
    public function getToid()
    {
        return $this->toid;
    }

    /**
     * Set conffields.
     *
     * @param string $conffields
     * @return Direction
     */
    public function setConffields($conffields)
    {
        $this->conffields = $conffields;

        return $this;
    }

    /**
     * Get conffields.
     *
     * @return string
     */
    public function getConffields()
    {
        return $this->conffields;
    }

    /**
     * Set copied.
     *
     * @param bool $copied
     * @return Direction
     */
    public function setCopied($copied)
    {
        $this->copied = $copied;

        return $this;
    }

    /**
     * Get copied.
     *
     * @return bool
     */
    public function getCopied()
    {
        return $this->copied;
    }

    /**
     * Set modified.
     *
     * @param bool $modified
     * @return Direction
     */
    public function setModified($modified)
    {
        $this->modified = $modified;

        return $this;
    }

    /**
     * Get modified.
     *
     * @return bool
     */
    public function getModified()
    {
        return $this->modified;
    }

    /**
     * Set planindex.
     *
     * @param int $planindex
     * @return Direction
     */
    public function setPlanindex($planindex)
    {
        $this->planindex = $planindex;

        return $this;
    }

    /**
     * Get planindex.
     *
     * @return int
     */
    public function getPlanindex()
    {
        return $this->planindex;
    }

    /**
     * Set sharecode.
     *
     * @param string $sharecode
     * @return Direction
     */
    public function setSharecode($sharecode)
    {
        $this->sharecode = $sharecode;

        return $this;
    }

    /**
     * Get sharecode.
     *
     * @return string
     */
    public function getSharecode()
    {
        return $this->sharecode;
    }

    /**
     * Set travelplanid.
     *
     * @return Direction
     */
    public function setTravelplanid(?Travelplan $travelplanid = null)
    {
        $this->travelplanid = $travelplanid;

        return $this;
    }

    /**
     * Get travelplanid.
     *
     * @return \AwardWallet\MainBundle\Entity\Travelplan
     */
    public function getTravelplanid()
    {
        return $this->travelplanid;
    }

    /**
     * Set userid.
     *
     * @return Direction
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
     * Set useragentid.
     *
     * @return Direction
     */
    public function setUseragentid(?Useragent $useragentid = null)
    {
        $this->useragentid = $useragentid;

        return $this;
    }

    /**
     * Get useragentid.
     *
     * @return \AwardWallet\MainBundle\Entity\Useragent
     */
    public function getUseragentid()
    {
        return $this->useragentid;
    }

    /**
     * Set endgeotagid.
     *
     * @return Direction
     */
    public function setEndgeotagid(?Geotag $endgeotagid = null)
    {
        $this->endgeotagid = $endgeotagid;

        return $this;
    }

    /**
     * Get endgeotagid.
     *
     * @return Geotag
     */
    public function getEndgeotagid()
    {
        return $this->endgeotagid;
    }

    /**
     * Set startgeotagid.
     *
     * @return Direction
     */
    public function setStartgeotagid(?Geotag $startgeotagid = null)
    {
        $this->startgeotagid = $startgeotagid;

        return $this;
    }

    /**
     * Get startgeotagid.
     *
     * @return Geotag
     */
    public function getStartgeotagid()
    {
        return $this->startgeotagid;
    }
}
