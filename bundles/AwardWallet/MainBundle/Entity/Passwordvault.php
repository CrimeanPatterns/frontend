<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Passwordvault.
 *
 * @ORM\Table(name="PasswordVault")
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\PasswordVaultRepository")
 */
class Passwordvault
{
    /**
     * @var int
     * @ORM\Column(name="PasswordVaultID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $passwordvaultid;

    /**
     * @var string
     * @ORM\Column(name="Login", type="string", length=80, nullable=true)
     */
    protected $login;

    /**
     * @var string
     * @ORM\Column(name="Login2", type="string", length=120, nullable=true)
     */
    protected $login2;

    /**
     * @var string
     * @ORM\Column(name="Login3", type="string", length=40, nullable=true)
     */
    protected $login3;

    /**
     * @var string
     * @ORM\Column(name="Pass", type="string", length=250, nullable=true)
     */
    protected $pass;

    /**
     * @var int
     * @ORM\Column(name="IssueID", type="integer", nullable=true)
     */
    protected $issueid;

    /**
     * @var \DateTime
     * @ORM\Column(name="CreationDate", type="datetime", nullable=false)
     */
    protected $creationdate;

    /**
     * @var \DateTime
     * @ORM\Column(name="ExpirationDate", type="datetime", nullable=true)
     */
    protected $expirationdate;

    /**
     * @var bool
     * @ORM\Column(name="Approved", type="boolean", nullable=false)
     */
    protected $approved = false;

    /**
     * @var \Account
     * @ORM\ManyToOne(targetEntity="Account")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="AccountID", referencedColumnName="AccountID")
     * })
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
     * @var \Provider
     * @ORM\ManyToOne(targetEntity="Provider")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ProviderID", referencedColumnName="ProviderID")
     * })
     */
    protected $providerid;

    /**
     * Get passwordvaultid.
     *
     * @return int
     */
    public function getPasswordvaultid()
    {
        return $this->passwordvaultid;
    }

    /**
     * Set login.
     *
     * @param string $login
     * @return Passwordvault
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
     * Set login2.
     *
     * @param string $login2
     * @return Passwordvault
     */
    public function setLogin2($login2)
    {
        $this->login2 = $login2;

        return $this;
    }

    /**
     * Get login2.
     *
     * @return string
     */
    public function getLogin2()
    {
        return $this->login2;
    }

    /**
     * Set login3.
     *
     * @param string $login3
     * @return Passwordvault
     */
    public function setLogin3($login3)
    {
        $this->login3 = $login3;

        return $this;
    }

    /**
     * Get login3.
     *
     * @return string
     */
    public function getLogin3()
    {
        return $this->login3;
    }

    /**
     * Set pass.
     *
     * @param string $pass
     * @return Passwordvault
     */
    public function setPass($pass)
    {
        $this->pass = $pass;

        return $this;
    }

    /**
     * Get pass.
     *
     * @return string
     */
    public function getPass()
    {
        return $this->pass;
    }

    /**
     * Set issueid.
     *
     * @param int $issueid
     * @return Passwordvault
     */
    public function setIssueid($issueid)
    {
        $this->issueid = $issueid;

        return $this;
    }

    /**
     * Get issueid.
     *
     * @return int
     */
    public function getIssueid()
    {
        return $this->issueid;
    }

    /**
     * Set creationdate.
     *
     * @param \DateTime $creationdate
     * @return Passwordvault
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
     * Set expirationdate.
     *
     * @param \DateTime $expirationdate
     * @return Passwordvault
     */
    public function setExpirationdate($expirationdate)
    {
        $this->expirationdate = $expirationdate;

        return $this;
    }

    /**
     * Get expirationdate.
     *
     * @return \DateTime
     */
    public function getExpirationdate()
    {
        return $this->expirationdate;
    }

    /**
     * Set approved.
     *
     * @param bool $approved
     * @return Passwordvault
     */
    public function setApproved($approved)
    {
        $this->approved = $approved;

        return $this;
    }

    /**
     * Get approved.
     *
     * @return bool
     */
    public function getApproved()
    {
        return $this->approved;
    }

    /**
     * Set accountid.
     *
     * @return Passwordvault
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

    /**
     * Set userid.
     *
     * @return Passwordvault
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
     * Set providerid.
     *
     * @return Passwordvault
     */
    public function setProviderid(?Provider $providerid = null)
    {
        $this->providerid = $providerid;

        return $this;
    }

    /**
     * Get providerid.
     *
     * @return \AwardWallet\MainBundle\Entity\Provider
     */
    public function getProviderid()
    {
        return $this->providerid;
    }
}
