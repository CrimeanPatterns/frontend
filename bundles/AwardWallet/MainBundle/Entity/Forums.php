<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Forums.
 *
 * @ORM\Table(name="forums")
 * @ORM\Entity
 */
class Forums
{
    /**
     * @var int
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var int
     * @ORM\Column(name="forumID", type="integer", nullable=false)
     */
    protected $forumid;

    /**
     * @var string
     * @ORM\Column(name="vName", type="text", nullable=false)
     */
    protected $vname;

    /**
     * @var string
     * @ORM\Column(name="vEmail", type="text", nullable=true)
     */
    protected $vemail;

    /**
     * @var string
     * @ORM\Column(name="vText", type="text", nullable=false)
     */
    protected $vtext;

    /**
     * @var string
     * @ORM\Column(name="vTitle", type="text", nullable=false)
     */
    protected $vtitle;

    /**
     * @var string
     * @ORM\Column(name="vLanguage", type="string", length=30, nullable=true)
     */
    protected $vlanguage;

    /**
     * @var string
     * @ORM\Column(name="vIP", type="text", nullable=false)
     */
    protected $vip;

    /**
     * @var bool
     * @ORM\Column(name="vDisplay", type="boolean", nullable=false)
     */
    protected $vdisplay = false;

    /**
     * @var \DateTime
     * @ORM\Column(name="vTime", type="datetime", nullable=false)
     */
    protected $vtime;

    /**
     * @var int
     * @ORM\Column(name="vPiority", type="integer", nullable=true)
     */
    protected $vpiority;

    public function __construct()
    {
        $this->vtime = new \DateTime();
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set forumid.
     *
     * @param int $forumid
     * @return Forums
     */
    public function setForumid($forumid)
    {
        $this->forumid = $forumid;

        return $this;
    }

    /**
     * Get forumid.
     *
     * @return int
     */
    public function getForumid()
    {
        return $this->forumid;
    }

    /**
     * Set vname.
     *
     * @param string $vname
     * @return Forums
     */
    public function setVname($vname)
    {
        $this->vname = $vname;

        return $this;
    }

    /**
     * Get vname.
     *
     * @return string
     */
    public function getVname()
    {
        return $this->vname;
    }

    /**
     * Set vemail.
     *
     * @param string $vemail
     * @return Forums
     */
    public function setVemail($vemail)
    {
        $this->vemail = $vemail;

        return $this;
    }

    /**
     * Get vemail.
     *
     * @return string
     */
    public function getVemail()
    {
        return $this->vemail;
    }

    /**
     * Set vtext.
     *
     * @param string $vtext
     * @return Forums
     */
    public function setVtext($vtext)
    {
        $this->vtext = $vtext;

        return $this;
    }

    /**
     * Get vtext.
     *
     * @return string
     */
    public function getVtext()
    {
        return $this->vtext;
    }

    /**
     * Set vtitle.
     *
     * @param string $vtitle
     * @return Forums
     */
    public function setVtitle($vtitle)
    {
        $this->vtitle = $vtitle;

        return $this;
    }

    /**
     * Get vtitle.
     *
     * @return string
     */
    public function getVtitle()
    {
        return $this->vtitle;
    }

    /**
     * Set vlanguage.
     *
     * @param string $vlanguage
     * @return Forums
     */
    public function setVlanguage($vlanguage)
    {
        $this->vlanguage = $vlanguage;

        return $this;
    }

    /**
     * Get vlanguage.
     *
     * @return string
     */
    public function getVlanguage()
    {
        return $this->vlanguage;
    }

    /**
     * Set vip.
     *
     * @param string $vip
     * @return Forums
     */
    public function setVip($vip)
    {
        $this->vip = $vip;

        return $this;
    }

    /**
     * Get vip.
     *
     * @return string
     */
    public function getVip()
    {
        return $this->vip;
    }

    /**
     * Set vdisplay.
     *
     * @param bool $vdisplay
     * @return Forums
     */
    public function setVdisplay($vdisplay)
    {
        $this->vdisplay = $vdisplay;

        return $this;
    }

    /**
     * Get vdisplay.
     *
     * @return bool
     */
    public function getVdisplay()
    {
        return $this->vdisplay;
    }

    /**
     * Set vtime.
     *
     * @param \DateTime $vtime
     * @return Forums
     */
    public function setVtime($vtime)
    {
        $this->vtime = $vtime;

        return $this;
    }

    /**
     * Get vtime.
     *
     * @return \DateTime
     */
    public function getVtime()
    {
        return $this->vtime;
    }

    /**
     * Set vpiority.
     *
     * @param int $vpiority
     * @return Forums
     */
    public function setVpiority($vpiority)
    {
        $this->vpiority = $vpiority;

        return $this;
    }

    /**
     * Get vpiority.
     *
     * @return int
     */
    public function getVpiority()
    {
        return $this->vpiority;
    }
}
