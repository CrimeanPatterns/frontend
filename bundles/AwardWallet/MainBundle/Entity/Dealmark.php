<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Dealmark.
 *
 * @ORM\Table(name="DealMark")
 * @ORM\Entity
 */
class Dealmark
{
    /**
     * @var int
     * @ORM\Column(name="DealMarkID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $dealmarkid;

    /**
     * @var bool
     * @ORM\Column(name="Readed", type="boolean", nullable=false)
     */
    protected $readed = false;

    /**
     * @var bool
     * @ORM\Column(name="Follow", type="boolean", nullable=false)
     */
    protected $follow = false;

    /**
     * @var bool
     * @ORM\Column(name="Applied", type="boolean", nullable=false)
     */
    protected $applied = false;

    /**
     * @var bool
     * @ORM\Column(name="Manual", type="boolean", nullable=false)
     */
    protected $manual = false;

    /**
     * @var \Deal
     * @ORM\ManyToOne(targetEntity="Deal")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="DealID", referencedColumnName="DealID")
     * })
     */
    protected $dealid;

    /**
     * @var \Usr
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserID", referencedColumnName="UserID")
     * })
     */
    protected $userid;

    /**
     * Get dealmarkid.
     *
     * @return int
     */
    public function getDealmarkid()
    {
        return $this->dealmarkid;
    }

    /**
     * Set readed.
     *
     * @param bool $readed
     * @return Dealmark
     */
    public function setReaded($readed)
    {
        $this->readed = $readed;

        return $this;
    }

    /**
     * Get readed.
     *
     * @return bool
     */
    public function getReaded()
    {
        return $this->readed;
    }

    /**
     * Set follow.
     *
     * @param bool $follow
     * @return Dealmark
     */
    public function setFollow($follow)
    {
        $this->follow = $follow;

        return $this;
    }

    /**
     * Get follow.
     *
     * @return bool
     */
    public function getFollow()
    {
        return $this->follow;
    }

    /**
     * Set applied.
     *
     * @param bool $applied
     * @return Dealmark
     */
    public function setApplied($applied)
    {
        $this->applied = $applied;

        return $this;
    }

    /**
     * Get applied.
     *
     * @return bool
     */
    public function getApplied()
    {
        return $this->applied;
    }

    /**
     * Set manual.
     *
     * @param bool $manual
     * @return Dealmark
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
     * Set dealid.
     *
     * @return Dealmark
     */
    public function setDealid(?Deal $dealid = null)
    {
        $this->dealid = $dealid;

        return $this;
    }

    /**
     * Get dealid.
     *
     * @return \AwardWallet\MainBundle\Entity\Deal
     */
    public function getDealid()
    {
        return $this->dealid;
    }

    /**
     * Set userid.
     *
     * @return Dealmark
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
