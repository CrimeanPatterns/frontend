<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Passwordvaultlog.
 *
 * @ORM\Table(name="PasswordVaultLog")
 * @ORM\Entity
 */
class Passwordvaultlog
{
    /**
     * @var int
     * @ORM\Column(name="PasswordVaultLogID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $passwordvaultlogid;

    /**
     * @var \DateTime
     * @ORM\Column(name="EventDate", type="datetime", nullable=false)
     */
    protected $eventdate;

    /**
     * @var unteger
     * @ORM\Column(name="Event", type="integer", nullable=false)
     */
    protected $event = 1;

    /**
     * @var string
     * @ORM\Column(name="Login", type="string", length=40, nullable=true)
     */
    protected $login;

    /**
     * @var int
     * @ORM\Column(name="AccountID", type="integer", nullable=true)
     */
    protected $accountid;

    /**
     * @var \Usr
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserID", referencedColumnName="UserID")
     * })
     */
    protected $userid;

    /**
     * @var \Passwordvault
     * @ORM\ManyToOne(targetEntity="Passwordvault")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="PasswordVaultID", referencedColumnName="PasswordVaultID")
     * })
     */
    protected $passwordvaultid;

    /**
     * Get passwordvaultlogid.
     *
     * @return int
     */
    public function getPasswordvaultlogid()
    {
        return $this->passwordvaultlogid;
    }

    /**
     * Set eventdate.
     *
     * @param \DateTime $eventdate
     * @return Passwordvaultlog
     */
    public function setEventdate($eventdate)
    {
        $this->eventdate = $eventdate;

        return $this;
    }

    /**
     * Get eventdate.
     *
     * @return \DateTime
     */
    public function getEventdate()
    {
        return $this->eventdate;
    }

    /**
     * Set event.
     *
     * @param int $event
     * @return Passwordvaultlog
     */
    public function setEvent($event)
    {
        $this->event = $event;

        return $this;
    }

    /**
     * Get event.
     *
     * @return int
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * Set login.
     *
     * @param string $login
     * @return Passwordvaultlog
     */
    public function setLogin($login)
    {
        $this->login = $login;

        return $this;
    }

    /**
     * Get login.
     *
     * @return string
     */
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * Set accountid.
     *
     * @param int $accountid
     * @return Passwordvaultlog
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
     * Set userid.
     *
     * @return Passwordvaultlog
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
     * Set passwordvaultid.
     *
     * @return Passwordvaultlog
     */
    public function setPasswordvaultid(?Passwordvault $passwordvaultid = null)
    {
        $this->passwordvaultid = $passwordvaultid;

        return $this;
    }

    /**
     * Get passwordvaultid.
     *
     * @return \AwardWallet\MainBundle\Entity\Passwordvault
     */
    public function getPasswordvaultid()
    {
        return $this->passwordvaultid;
    }
}
