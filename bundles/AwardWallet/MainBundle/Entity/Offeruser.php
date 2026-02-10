<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Offeruser.
 *
 * @ORM\Table(name="OfferUser")
 * @ORM\Entity
 */
class Offeruser
{
    /**
     * @var int
     * @ORM\Column(name="OfferUserID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $offeruserid;

    /**
     * @var \DateTime
     * @ORM\Column(name="CreationDate", type="datetime", nullable=false)
     */
    protected $creationdate;

    /**
     * @var bool
     * @ORM\Column(name="Manual", type="boolean", nullable=false)
     */
    protected $manual = true;

    /**
     * @var string
     * @ORM\Column(name="Params", type="text", nullable=true)
     */
    protected $params;

    /**
     * @var bool
     * @ORM\Column(name="Agreed", type="boolean", nullable=true)
     */
    protected $agreed;

    /**
     * @var \DateTime
     * @ORM\Column(name="ShowDate", type="datetime", nullable=true)
     */
    protected $showdate;

    /**
     * @var int
     * @ORM\Column(name="ShowsCount", type="integer", nullable=false)
     */
    protected $showscount = 0;

    /**
     * @var \Usr
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserID", referencedColumnName="UserID")
     * })
     */
    protected $userid;

    /**
     * @var \Offer
     * @ORM\ManyToOne(targetEntity="Offer")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="OfferID", referencedColumnName="OfferID")
     * })
     */
    protected $offerid;

    /**
     * Get offeruserid.
     *
     * @return int
     */
    public function getOfferuserid()
    {
        return $this->offeruserid;
    }

    /**
     * Set creationdate.
     *
     * @param \DateTime $creationdate
     * @return Offeruser
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
     * Set manual.
     *
     * @param bool $manual
     * @return Offeruser
     */
    public function setManual($manual)
    {
        $this->manual = $manual;

        return $this;
    }

    /**
     * Get manual.
     *
     * @return bool
     */
    public function getManual()
    {
        return $this->manual;
    }

    /**
     * Set params.
     *
     * @param string $params
     * @return Offeruser
     */
    public function setParams($params)
    {
        $this->params = $params;

        return $this;
    }

    /**
     * Get params.
     *
     * @return string
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Set agreed.
     *
     * @param bool $agreed
     * @return Offeruser
     */
    public function setAgreed($agreed)
    {
        $this->agreed = $agreed;

        return $this;
    }

    /**
     * Get agreed.
     *
     * @return bool
     */
    public function getAgreed()
    {
        return $this->agreed;
    }

    /**
     * Set showdate.
     *
     * @param \DateTime $showdate
     * @return Offeruser
     */
    public function setShowdate($showdate)
    {
        $this->showdate = $showdate;

        return $this;
    }

    /**
     * Get showdate.
     *
     * @return \DateTime
     */
    public function getShowdate()
    {
        return $this->showdate;
    }

    /**
     * Set showscount.
     *
     * @param int $showscount
     * @return Offeruser
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
     * Set userid.
     *
     * @return Offeruser
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
     * Set offerid.
     *
     * @return Offeruser
     */
    public function setOfferid(?Offer $offerid = null)
    {
        $this->offerid = $offerid;

        return $this;
    }

    /**
     * Get offerid.
     *
     * @return \AwardWallet\MainBundle\Entity\Offer
     */
    public function getOfferid()
    {
        return $this->offerid;
    }
}
