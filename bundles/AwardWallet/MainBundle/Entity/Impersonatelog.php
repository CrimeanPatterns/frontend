<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Impersonatelog.
 *
 * @ORM\Table(name="ImpersonateLog")
 * @ORM\Entity
 */
class Impersonatelog
{
    /**
     * @var int
     * @ORM\Column(name="ImpersonateLogID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $impersonatelogid;

    /**
     * @var \DateTime
     * @ORM\Column(name="CreationDate", type="datetime", nullable=false)
     */
    protected $creationdate;

    /**
     * @var string
     * @ORM\Column(name="IPAddress", type="string", length=15, nullable=true)
     */
    protected $ipaddress;

    /**
     * @var string
     * @ORM\Column(name="UserAgent", type="string", length=250, nullable=true)
     */
    protected $useragent;

    /**
     * @var \Usr
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserID", referencedColumnName="UserID")
     * })
     */
    protected $userid;

    /**
     * @var \Usr
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="TargetUserID", referencedColumnName="UserID")
     * })
     */
    protected $targetuserid;

    /**
     * Get impersonatelogid.
     *
     * @return int
     */
    public function getImpersonatelogid()
    {
        return $this->impersonatelogid;
    }

    /**
     * Set creationdate.
     *
     * @param \DateTime $creationdate
     * @return Impersonatelog
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
     * Set ipaddress.
     *
     * @param string $ipaddress
     * @return Impersonatelog
     */
    public function setIpaddress($ipaddress)
    {
        $this->ipaddress = $ipaddress;

        return $this;
    }

    /**
     * Get ipaddress.
     *
     * @return string
     */
    public function getIpaddress()
    {
        return $this->ipaddress;
    }

    /**
     * Set useragent.
     *
     * @param string $useragent
     * @return Impersonatelog
     */
    public function setUseragent($useragent)
    {
        $this->useragent = $useragent;

        return $this;
    }

    /**
     * Get useragent.
     *
     * @return string
     */
    public function getUseragent()
    {
        return $this->useragent;
    }

    /**
     * Set userid.
     *
     * @return Impersonatelog
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
     * Set targetuserid.
     *
     * @return Impersonatelog
     */
    public function setTargetuserid(?Usr $targetuserid = null)
    {
        $this->targetuserid = $targetuserid;

        return $this;
    }

    /**
     * Get targetuserid.
     *
     * @return \AwardWallet\MainBundle\Entity\Usr
     */
    public function getTargetuserid()
    {
        return $this->targetuserid;
    }
}
