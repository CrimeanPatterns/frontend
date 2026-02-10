<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Accountshare.
 *
 * @ORM\Table(name="AccountShare")
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\AccountshareRepository")
 */
class Accountshare
{
    /**
     * @var int
     * @ORM\Column(name="AccountShareID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $accountshareid;

    /**
     * @var \Useragent
     * @ORM\ManyToOne(targetEntity="Useragent")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserAgentID", referencedColumnName="UserAgentID")
     * })
     */
    protected $useragentid;

    /**
     * @var \Account
     * @ORM\ManyToOne(targetEntity="Account", inversedBy="shares")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="AccountID", referencedColumnName="AccountID")
     * })
     */
    protected $accountid;

    /**
     * Get accountshareid.
     *
     * @return int
     */
    public function getAccountshareid()
    {
        return $this->accountshareid;
    }

    /**
     * Set useragentid.
     *
     * @return Accountshare
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
     * Set accountid.
     *
     * @return Accountshare
     */
    public function setAccountid(?Account $accountid = null)
    {
        $this->accountid = $accountid;

        return $this;
    }

    /**
     * Get accountid.
     *
     * @return \AwardWallet\MainBundle\Entity\Account
     */
    public function getAccountid()
    {
        return $this->accountid;
    }
}
