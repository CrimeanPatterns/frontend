<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserTip.
 *
 * @ORM\Table(name="UserTip")
 * @ORM\Entity
 */
class UserTip
{
    /**
     * @var int
     * @ORM\Column(name="UserTipID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $usertipId;

    /**
     * @var Usr
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserID", referencedColumnName="UserID")
     * })
     */
    protected $userId;

    /**
     * @var Tip
     * @ORM\ManyToOne(targetEntity="Tip")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="TipID", referencedColumnName="TipID")
     * })
     */
    protected $tipId;

    /**
     * @var \DateTime
     * @ORM\Column(name="ShowDate", type="datetime", nullable=true)
     */
    protected $showDate;

    /**
     * @var \DateTime
     * @ORM\Column(name="CloseDate", type="datetime", nullable=true)
     */
    protected $closeDate;

    /**
     * @var \DateTime
     * @ORM\Column(name="ClickDate", type="datetime", nullable=true)
     */
    protected $clickDate;

    /**
     * @var int
     * @ORM\Column(name="ShowCount", type="integer", nullable=false)
     */
    protected $showCount;

    public function getUserId(): Usr
    {
        return $this->userId;
    }

    public function setUserId(?Usr $user = null): self
    {
        $this->userId = $user;

        return $this;
    }

    public function getTipId(): Tip
    {
        return $this->tipId;
    }

    /**
     * @param Tip
     */
    public function setTipId(?Tip $tip = null): self
    {
        $this->tipId = $tip;

        return $this;
    }

    /**
     * @param \DateTime $showDate
     */
    public function setShowDate($showDate): self
    {
        $this->showDate = $showDate;

        return $this;
    }

    public function getShowDate(): ?\DateTime
    {
        return $this->showDate;
    }

    /**
     * @param \DateTime $closeDate
     */
    public function setCloseDate($closeDate): self
    {
        $this->closeDate = $closeDate;

        return $this;
    }

    public function getCloseDate(): ?\DateTime
    {
        return $this->closeDate;
    }

    /**
     * @param \DateTime $clickDate
     */
    public function setClickDate($clickDate): self
    {
        $this->clickDate = $clickDate;

        return $this;
    }

    public function getClickDate(): ?\DateTime
    {
        return $this->clickDate;
    }

    public function setShowCount(int $showCount): self
    {
        $this->showCount = $showCount;

        return $this;
    }

    public function getShowCount(): int
    {
        return $this->showCount;
    }
}
