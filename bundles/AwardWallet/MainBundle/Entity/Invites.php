<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Invites.
 *
 * @ORM\Table(name="Invites")
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\InvitesRepository")
 */
class Invites
{
    /**
     * @var int
     * @ORM\Column(name="InvitesID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $invitesid;

    /**
     * @var string
     * @ORM\Column(name="Email", type="string", length=100, nullable=true)
     */
    protected $email;

    /**
     * @var \DateTime
     * @ORM\Column(name="InviteDate", type="datetime", nullable=true)
     */
    protected $invitedate;

    /**
     * @var string
     * @ORM\Column(name="Code", type="string", length=20, nullable=true)
     */
    protected $code;

    /**
     * @var bool
     * @ORM\Column(name="Approved", type="boolean", nullable=false)
     */
    protected $approved = false;

    /**
     * @var \Usr
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="InviterID", referencedColumnName="UserID")
     * })
     */
    protected $inviterid;

    /**
     * @var Usr
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="InviteeID", referencedColumnName="UserID")
     * })
     */
    protected $inviteeid;

    /**
     * @var Useragent
     * @ORM\ManyToOne(targetEntity="Useragent")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserAgentID", referencedColumnName="UserAgentID")
     * })
     */
    protected $familyMember;

    /**
     * Get invitesid.
     *
     * @return int
     */
    public function getInvitesid()
    {
        return $this->invitesid;
    }

    /**
     * Set email.
     *
     * @param string $email
     * @return Invites
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
     * Set invitedate.
     *
     * @param \DateTime $invitedate
     * @return Invites
     */
    public function setInvitedate($invitedate)
    {
        $this->invitedate = $invitedate;

        return $this;
    }

    /**
     * Get invitedate.
     *
     * @return \DateTime
     */
    public function getInvitedate()
    {
        return $this->invitedate;
    }

    /**
     * Set code.
     *
     * @param string $code
     * @return Invites
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
     * Set approved.
     *
     * @param bool $approved
     * @return Invites
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
     * Set inviterid.
     *
     * @return Invites
     */
    public function setInviterid(?Usr $inviterid = null)
    {
        $this->inviterid = $inviterid;

        return $this;
    }

    /**
     * Get inviterid.
     *
     * @return \AwardWallet\MainBundle\Entity\Usr
     */
    public function getInviterid()
    {
        return $this->inviterid;
    }

    /**
     * Set inviteeid.
     *
     * @return Invites
     */
    public function setInviteeid(?Usr $inviteeid = null)
    {
        $this->inviteeid = $inviteeid;

        return $this;
    }

    /**
     * Get inviteeid.
     *
     * @return \AwardWallet\MainBundle\Entity\Usr
     */
    public function getInviteeid()
    {
        return $this->inviteeid;
    }

    /**
     * @return Useragent
     */
    public function getFamilyMember()
    {
        return $this->familyMember;
    }

    /**
     * @param Useragent $familyMember
     */
    public function setFamilyMember($familyMember)
    {
        $this->familyMember = $familyMember;
    }
}
