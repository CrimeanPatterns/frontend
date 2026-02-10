<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Passwordvaultuser.
 *
 * @ORM\Table(name="PasswordVaultUser")
 * @ORM\Entity
 */
class Passwordvaultuser
{
    /**
     * @var int
     * @ORM\Column(name="PasswordVaultUserID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $passwordvaultuserid;

    /**
     * @var \Passwordvault
     * @ORM\ManyToOne(targetEntity="Passwordvault")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="PasswordVaultID", referencedColumnName="PasswordVaultID")
     * })
     */
    protected $passwordvaultid;

    /**
     * @var \Usr
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserID", referencedColumnName="UserID")
     * })
     */
    protected $userid;

    /**
     * Get passwordvaultuserid.
     *
     * @return int
     */
    public function getPasswordvaultuserid()
    {
        return $this->passwordvaultuserid;
    }

    /**
     * Set passwordvaultid.
     *
     * @return Passwordvaultuser
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

    /**
     * Set userid.
     *
     * @return Passwordvaultuser
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
