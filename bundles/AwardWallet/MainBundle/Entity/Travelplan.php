<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Travelplan.
 *
 * @ORM\Table(name="TravelPlan")
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\TravelplanRepository")
 */
class Travelplan
{
    /**
     * @var int
     * @ORM\Column(name="TravelPlanID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $travelplanid;

    /**
     * @var string
     * @ORM\Column(name="Name", type="string", length=250, nullable=false)
     */
    protected $name;

    /**
     * @var \DateTime
     * @ORM\Column(name="StartDate", type="datetime", nullable=true)
     */
    protected $startdate;

    /**
     * @var \DateTime
     * @ORM\Column(name="EndDate", type="datetime", nullable=true)
     */
    protected $enddate;

    /**
     * @var int
     * @ORM\Column(name="PictureVer", type="integer", nullable=true)
     */
    protected $picturever;

    /**
     * @var string
     * @ORM\Column(name="PictureExt", type="string", length=5, nullable=true)
     */
    protected $pictureext;

    /**
     * @var string
     * @ORM\Column(name="Code", type="string", length=20, nullable=true)
     */
    protected $code;

    /**
     * @var \DateTime
     * @ORM\Column(name="AutoUpdateDate", type="datetime", nullable=true)
     */
    protected $autoupdatedate;

    /**
     * @var \DateTime
     * @ORM\Column(name="MailDate", type="datetime", nullable=true)
     */
    protected $maildate;

    /**
     * @var bool
     * @ORM\Column(name="Public", type="boolean", nullable=false)
     */
    protected $public = true;

    /**
     * @var bool
     * @ORM\Column(name="CustomDates", type="boolean", nullable=false)
     */
    protected $customdates = false;

    /**
     * @var bool
     * @ORM\Column(name="CustomName", type="boolean", nullable=false)
     */
    protected $customname = false;

    /**
     * @var bool
     * @ORM\Column(name="Hidden", type="boolean", nullable=false)
     */
    protected $hidden = false;

    /**
     * @var bool
     * @ORM\Column(name="CustomUserAgent", type="boolean", nullable=false)
     */
    protected $customuseragent = false;

    /**
     * @var \Plangroup
     * @ORM\ManyToOne(targetEntity="Plangroup")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="PlanGroupID", referencedColumnName="PlanGroupID")
     * })
     */
    protected $plangroupid;

    /**
     * @var \Useragent
     * @ORM\ManyToOne(targetEntity="Useragent")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserAgentID", referencedColumnName="UserAgentID")
     * })
     */
    protected $useragentid;

    /**
     * @var \Usr
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserID", referencedColumnName="UserID")
     * })
     */
    protected $userid;

    /**
     * Get travelplanid.
     *
     * @return int
     */
    public function getTravelplanid()
    {
        return $this->travelplanid;
    }

    /**
     * Set name.
     *
     * @param string $name
     * @return Travelplan
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
     * Set startdate.
     *
     * @param \DateTime $startdate
     * @return Travelplan
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
     * Set enddate.
     *
     * @param \DateTime $enddate
     * @return Travelplan
     */
    public function setEnddate($enddate)
    {
        $this->enddate = $enddate;

        return $this;
    }

    /**
     * Get enddate.
     *
     * @return \DateTime
     */
    public function getEnddate()
    {
        return $this->enddate;
    }

    /**
     * Set picturever.
     *
     * @param int $picturever
     * @return Travelplan
     */
    public function setPicturever($picturever)
    {
        $this->picturever = $picturever;

        return $this;
    }

    /**
     * Get picturever.
     *
     * @return int
     */
    public function getPicturever()
    {
        return $this->picturever;
    }

    /**
     * Set pictureext.
     *
     * @param string $pictureext
     * @return Travelplan
     */
    public function setPictureext($pictureext)
    {
        $this->pictureext = $pictureext;

        return $this;
    }

    /**
     * Get pictureext.
     *
     * @return string
     */
    public function getPictureext()
    {
        return $this->pictureext;
    }

    /**
     * Set code.
     *
     * @param string $code
     * @return Travelplan
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
     * Set autoupdatedate.
     *
     * @param \DateTime $autoupdatedate
     * @return Travelplan
     */
    public function setAutoupdatedate($autoupdatedate)
    {
        $this->autoupdatedate = $autoupdatedate;

        return $this;
    }

    /**
     * Get autoupdatedate.
     *
     * @return \DateTime
     */
    public function getAutoupdatedate()
    {
        return $this->autoupdatedate;
    }

    /**
     * Set maildate.
     *
     * @param \DateTime $maildate
     * @return Travelplan
     */
    public function setMaildate($maildate)
    {
        $this->maildate = $maildate;

        return $this;
    }

    /**
     * Get maildate.
     *
     * @return \DateTime
     */
    public function getMaildate()
    {
        return $this->maildate;
    }

    /**
     * Set public.
     *
     * @param bool $public
     * @return Travelplan
     */
    public function setPublic($public)
    {
        $this->public = $public;

        return $this;
    }

    /**
     * Get public.
     *
     * @return bool
     */
    public function getPublic()
    {
        return $this->public;
    }

    /**
     * Set customdates.
     *
     * @param bool $customdates
     * @return Travelplan
     */
    public function setCustomdates($customdates)
    {
        $this->customdates = $customdates;

        return $this;
    }

    /**
     * Get customdates.
     *
     * @return bool
     */
    public function getCustomdates()
    {
        return $this->customdates;
    }

    /**
     * Set customname.
     *
     * @param bool $customname
     * @return Travelplan
     */
    public function setCustomname($customname)
    {
        $this->customname = $customname;

        return $this;
    }

    /**
     * Get customname.
     *
     * @return bool
     */
    public function getCustomname()
    {
        return $this->customname;
    }

    /**
     * Set hidden.
     *
     * @param bool $hidden
     * @return Travelplan
     */
    public function setHidden($hidden)
    {
        $this->hidden = $hidden;

        return $this;
    }

    /**
     * Get hidden.
     *
     * @return bool
     */
    public function getHidden()
    {
        return $this->hidden;
    }

    /**
     * Set customuseragent.
     *
     * @param bool $customuseragent
     * @return Travelplan
     */
    public function setCustomuseragent($customuseragent)
    {
        $this->customuseragent = $customuseragent;

        return $this;
    }

    /**
     * Get customuseragent.
     *
     * @return bool
     */
    public function getCustomuseragent()
    {
        return $this->customuseragent;
    }

    /**
     * Set plangroupid.
     *
     * @return Travelplan
     */
    public function setPlangroupid(?Plangroup $plangroupid = null)
    {
        $this->plangroupid = $plangroupid;

        return $this;
    }

    /**
     * Get plangroupid.
     *
     * @return \AwardWallet\MainBundle\Entity\Plangroup
     */
    public function getPlangroupid()
    {
        return $this->plangroupid;
    }

    /**
     * Set useragentid.
     *
     * @return Travelplan
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
     * Set userid.
     *
     * @return Travelplan
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
}
