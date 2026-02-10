<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TimelineShare.
 *
 * @ORM\Table(name="TimelineShare")
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\TimelineShareRepository")
 */
class TimelineShare
{
    /**
     * @var int
     * @ORM\Column(name="TimelineShareID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $timelineShareId;

    /**
     * @var Usr
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="TimelineOwnerID", referencedColumnName="UserID")
     * })
     */
    protected $timelineOwner;

    /**
     * @var Useragent
     * @ORM\ManyToOne(targetEntity="Useragent")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="FamilyMemberID", referencedColumnName="UserAgentID")
     * })
     */
    protected $familyMember;

    /**
     * @var Useragent
     * @ORM\ManyToOne(targetEntity="Useragent")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserAgentID", referencedColumnName="UserAgentID")
     * })
     */
    protected $userAgent;

    /**
     * @var Usr
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumn(name="recipientUserID", referencedColumnName="UserID", nullable=false)
     */
    protected $recipientUser;

    /**
     * @return \AwardWallet\MainBundle\Entity\Usr
     */
    public function getTimelineOwner()
    {
        return $this->timelineOwner;
    }

    /**
     * @param \AwardWallet\MainBundle\Entity\Usr $timelineOwner
     */
    public function setTimelineOwner($timelineOwner)
    {
        $this->timelineOwner = $timelineOwner;
    }

    /**
     * @return \AwardWallet\MainBundle\Entity\Usr
     */
    public function getRecipientUser()
    {
        return $this->recipientUser;
    }

    /**
     * @param \AwardWallet\MainBundle\Entity\Usr $recipientUser
     */
    public function setRecipientUser($recipientUser)
    {
        $this->recipientUser = $recipientUser;
    }

    /**
     * @return Useragent
     */
    public function getUserAgent()
    {
        return $this->userAgent;
    }

    /**
     * @param Useragent $userAgent
     */
    public function setUserAgent($userAgent)
    {
        $this->userAgent = $userAgent;
    }

    /**
     * @return Useragent
     */
    public function getFamilyMember()
    {
        return $this->familyMember;
    }

    /**
     * @return Useragent
     */
    public function getTargetAgent()
    {
        return $this->familyMember ?? $this->userAgent;
    }

    public function setFamilyMember(?Useragent $familyMember = null)
    {
        $this->familyMember = $familyMember;
    }

    /**
     * @return int
     */
    public function getTimelineShareId()
    {
        return $this->timelineShareId;
    }

    /**
     * @return bool
     */
    public function canView(Usr $user)
    {
        if ($this->canEdit($user)) {
            return true;
        }

        return $user === $this->recipientUser && $this->getUserAgent()->getTripAccessLevel() >= Useragent::TRIP_ACCESS_READ_ONLY && $this->getUserAgent()->isApproved();
    }

    /**
     * @return bool
     */
    public function canEdit(Usr $user)
    {
        return $user === $this->recipientUser && $this->getUserAgent()->getTripAccessLevel() >= Useragent::TRIP_ACCESS_FULL_CONTROL && $this->getUserAgent()->isApproved();
    }
}
