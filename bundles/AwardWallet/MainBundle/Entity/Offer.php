<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Offer.
 *
 * @ORM\Table(name="Offer")
 * @ORM\Entity
 */
class Offer
{
    /**
     * @var int
     * @ORM\Column(name="OfferID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $offerid;

    /**
     * @var bool
     * @ORM\Column(name="Enabled", type="boolean", nullable=false)
     */
    protected $enabled;

    /**
     * @var string
     * @ORM\Column(name="Name", type="string", length=250, nullable=false)
     */
    protected $name;

    /**
     * @var string
     * @ORM\Column(name="Description", type="string", length=4000, nullable=true)
     */
    protected $description;

    /**
     * @var string
     * @ORM\Column(name="Code", type="string", length=60, nullable=false)
     */
    protected $code;

    /**
     * @var \DateTime
     * @ORM\Column(name="CreationDate", type="datetime", nullable=false)
     */
    protected $creationdate;

    /**
     * @var string
     * @ORM\Column(name="ApplyURL", type="string", length=250, nullable=false)
     */
    protected $applyurl;

    /**
     * @var int
     * @ORM\Column(name="RemindMeDays", type="integer", nullable=false)
     */
    protected $remindmedays;

    /**
     * @var bool
     * @ORM\Column(name="DisplayType", type="boolean", nullable=true)
     */
    protected $displaytype = false;

    /**
     * @var int
     * @ORM\Column(name="ShowsCount", type="integer", nullable=true)
     */
    protected $showscount = 0;

    /**
     * @var int
     * @ORM\Column(name="Priority", type="integer", nullable=true)
     */
    protected $priority = 0;

    /**
     * @var int
     * @ORM\Column(name="Kind", type="integer", nullable=false)
     */
    protected $kind = 0;

    /**
     * @var int
     * @ORM\Column(name="MaxShows", type="integer", nullable=true)
     */
    protected $maxshows;

    /**
     * @var \DateTime
     * @ORM\Column(name="ShowUntilDate", type="datetime", nullable=true)
     */
    protected $showuntildate;

    /**
     * Get offerid.
     *
     * @return int
     */
    public function getOfferid()
    {
        return $this->offerid;
    }

    /**
     * Set enabled.
     *
     * @param bool $enabled
     * @return Offer
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * Get enabled.
     *
     * @return bool
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * Set name.
     *
     * @param string $name
     * @return Offer
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
     * Set description.
     *
     * @param string $description
     * @return Offer
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set code.
     *
     * @param string $code
     * @return Offer
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
     * Set creationdate.
     *
     * @param \DateTime $creationdate
     * @return Offer
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
     * Set applyurl.
     *
     * @param string $applyurl
     * @return Offer
     */
    public function setApplyurl($applyurl)
    {
        $this->applyurl = $applyurl;

        return $this;
    }

    /**
     * Get applyurl.
     *
     * @return string
     */
    public function getApplyurl()
    {
        return $this->applyurl;
    }

    /**
     * Set remindmedays.
     *
     * @param int $remindmedays
     * @return Offer
     */
    public function setRemindmedays($remindmedays)
    {
        $this->remindmedays = $remindmedays;

        return $this;
    }

    /**
     * Get remindmedays.
     *
     * @return int
     */
    public function getRemindmedays()
    {
        return $this->remindmedays;
    }

    /**
     * Set displaytype.
     *
     * @param bool $displaytype
     * @return Offer
     */
    public function setDisplaytype($displaytype)
    {
        $this->displaytype = $displaytype;

        return $this;
    }

    /**
     * Get displaytype.
     *
     * @return bool
     */
    public function getDisplaytype()
    {
        return $this->displaytype;
    }

    /**
     * Set showscount.
     *
     * @param int $showscount
     * @return Offer
     */
    public function setShowscount($showscount)
    {
        $this->showscount = $showscount;

        return $this;
    }

    /**
     * Get showscount.
     *
     * @return int
     */
    public function getShowscount()
    {
        return $this->showscount;
    }

    /**
     * Set priority.
     *
     * @param int $priority
     * @return Offer
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * Get priority.
     *
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * Set kind.
     *
     * @param int $kind
     * @return Offer
     */
    public function setKind($kind)
    {
        $this->kind = $kind;

        return $this;
    }

    /**
     * Get kind.
     *
     * @return int
     */
    public function getKind()
    {
        return $this->kind;
    }

    /**
     * Set maxshows.
     *
     * @param int $maxshows
     * @return Offer
     */
    public function setMaxshows($maxshows)
    {
        $this->maxshows = $maxshows;

        return $this;
    }

    /**
     * Get maxshows.
     *
     * @return int
     */
    public function getMaxshows()
    {
        return $this->maxshows;
    }

    /**
     * @param \DateTime $showuntildate
     */
    public function setShowuntildate($showuntildate)
    {
        $this->showuntildate = $showuntildate;
    }

    /**
     * @return \DateTime
     */
    public function getShowuntildate()
    {
        return $this->showuntildate;
    }
}
