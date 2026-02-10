<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Travelplannew.
 *
 * @ORM\Table(name="TravelPlanNew")
 * @ORM\Entity
 */
class Travelplannew
{
    /**
     * @var bool
     * @ORM\Column(name="Hidden", type="boolean", nullable=false)
     */
    protected $hidden = false;

    /**
     * @var string
     * @ORM\Column(name="Name", type="string", length=250, nullable=false)
     */
    protected $name;

    /**
     * @var string
     * @ORM\Column(name="Code", type="string", length=20, nullable=true)
     */
    protected $code;

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
     * @var int
     * @ORM\Column(name="TravelPlanNewID", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $travelplannewid;

    /**
     * @var \AwardWallet\MainBundle\Entity\Usr
     * @ORM\ManyToOne(targetEntity="AwardWallet\MainBundle\Entity\Usr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserID", referencedColumnName="UserID")
     * })
     */
    protected $userid;

    /**
     * @var \AwardWallet\MainBundle\Entity\Useragent
     * @ORM\ManyToOne(targetEntity="AwardWallet\MainBundle\Entity\Useragent")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserAgentID", referencedColumnName="UserAgentID")
     * })
     */
    protected $useragentid;

    /**
     * Set hidden.
     *
     * @param bool $hidden
     * @return Travelplannew
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
     * Set name.
     *
     * @param string $name
     * @return Travelplannew
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
     * @return Travelplannew
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
     * Set picturever.
     *
     * @param int $picturever
     * @return Travelplannew
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
     * @return Travelplannew
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
     * Set public.
     *
     * @param bool $public
     * @return Travelplannew
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
     * @return Travelplannew
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
     * @return Travelplannew
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
     * Get travelplannewid.
     *
     * @return int
     */
    public function getTravelplannewid()
    {
        return $this->travelplannewid;
    }

    /**
     * Set userid.
     *
     * @return Travelplannew
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
     * @return Travelplannew
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
}
