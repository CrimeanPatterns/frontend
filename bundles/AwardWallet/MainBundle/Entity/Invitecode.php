<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Invitecode.
 *
 * @ORM\Table(name="InviteCode")
 * @ORM\Entity
 */
class Invitecode
{
    /**
     * @var int
     * @ORM\Column(name="InviteCodeID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $invitecodeid;

    /**
     * @var string
     * @ORM\Column(name="Code", type="string", length=20, nullable=true)
     */
    protected $code;

    /**
     * @var \DateTime
     * @ORM\Column(name="CreationDate", type="datetime", nullable=false)
     */
    protected $creationdate;

    /**
     * @var string
     * @ORM\Column(name="Email", type="string", length=80, nullable=false)
     */
    protected $email;

    /**
     * @var string
     * @ORM\Column(name="Source", type="string", length=1, nullable=false)
     */
    protected $source = 'A';

    /**
     * @var \Usr
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserID", referencedColumnName="UserID")
     * })
     */
    protected $userid;

    /**
     * Get invitecodeid.
     *
     * @return int
     */
    public function getInvitecodeid()
    {
        return $this->invitecodeid;
    }

    /**
     * Set code.
     *
     * @param string $code
     * @return Invitecode
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
     * @return Invitecode
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
     * Set email.
     *
     * @param string $email
     * @return Invitecode
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set source.
     *
     * @param string $source
     * @return Invitecode
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Get source.
     *
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set userid.
     *
     * @return Invitecode
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
