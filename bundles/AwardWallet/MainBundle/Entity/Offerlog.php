<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Offerlog.
 *
 * @ORM\Table(name="OfferLog")
 * @ORM\Entity
 */
class Offerlog
{
    /**
     * @var int
     * @ORM\Column(name="OfferLogID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $offerlogid;

    /**
     * @var int
     * @ORM\Column(name="OfferID", type="integer", nullable=true)
     */
    protected $offerid;

    /**
     * @var int
     * @ORM\Column(name="UserID", type="integer", nullable=true)
     */
    protected $userid;

    /**
     * @var int
     * @ORM\Column(name="Action", type="integer", nullable=true)
     */
    protected $action;

    /**
     * @var \DateTime
     * @ORM\Column(name="ActionDate", type="datetime", nullable=true)
     */
    protected $actiondate;

    /**
     * Get offerlogid.
     *
     * @return int
     */
    public function getOfferlogid()
    {
        return $this->offerlogid;
    }

    /**
     * Set offerid.
     *
     * @param int $offerid
     * @return Offerlog
     */
    public function setOfferid($offerid)
    {
        $this->offerid = $offerid;

        return $this;
    }

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
     * Set userid.
     *
     * @param int $userid
     * @return Offerlog
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
     * Set action.
     *
     * @param int $action
     * @return Offerlog
     */
    public function setAction($action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Get action.
     *
     * @return int
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Set actiondate.
     *
     * @param \DateTime $actiondate
     * @return Offerlog
     */
    public function setActiondate($actiondate)
    {
        $this->actiondate = $actiondate;

        return $this;
    }

    /**
     * Get actiondate.
     *
     * @return \DateTime
     */
    public function getActiondate()
    {
        return $this->actiondate;
    }
}
