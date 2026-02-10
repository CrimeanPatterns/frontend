<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AwardWallet\MainBundle\Entity\AbRequestMark.
 *
 * @ORM\Entity
 * @ORM\Table(
 *     name="AbRequestMark",
 *     indexes={
 *         @ORM\Index(name="IDX_FE3974EF58746832", columns={"UserID"}),
 *         @ORM\Index(name="IDX_FE3974EF18FCD26A", columns={"RequestID"})
 *     },
 *     uniqueConstraints={@ORM\UniqueConstraint(name="RequestUser", columns={"RequestID","UserID"})}
 * )
 * @ORM\HasLifecycleCallbacks()
 */
class AbRequestMark
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $AbRequestMarkID;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=false)
     */
    protected $ReadDate;

    /**
     * @var Usr
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumn(name="UserID", referencedColumnName="UserID", nullable=false)
     */
    protected $UserID;

    /**
     * @var AbRequest
     * @ORM\ManyToOne(targetEntity="AbRequest", inversedBy="RequestsMark")
     * @ORM\JoinColumn(name="RequestID", referencedColumnName="AbRequestID", nullable=false)
     */
    protected $RequestID;

    /**
     * Get AbAbRequestMarkID.
     *
     * @return int
     */
    public function getAbRequestMarkID()
    {
        return $this->AbRequestMarkID;
    }

    /**
     * Set ReadDate.
     *
     * @param \DateTime $readDate
     * @return AbRequestMark
     */
    public function setReadDate($readDate)
    {
        $this->ReadDate = $readDate;

        return $this;
    }

    /**
     * Get ReadDate.
     *
     * @return \DateTime
     */
    public function getReadDate()
    {
        return $this->ReadDate;
    }

    /**
     * Set user.
     *
     * @return AbRequestMark
     */
    public function setUser(Usr $user)
    {
        $this->UserID = $user;

        return $this;
    }

    /**
     * Get user.
     *
     * @return \AwardWallet\MainBundle\Entity\Usr
     */
    public function getUser()
    {
        return $this->UserID;
    }

    /**
     * Set request.
     *
     * @return AbRequestMark
     */
    public function setRequest(AbRequest $request)
    {
        $this->RequestID = $request;

        return $this;
    }

    /**
     * Get request.
     *
     * @return \AwardWallet\MainBundle\Entity\AbRequest
     */
    public function getRequest()
    {
        return $this->RequestID;
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        if (empty($this->ReadDate)) {
            $this->ReadDate = new \DateTime();
        }
    }
}
