<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="PageVisit")
 * @ORM\Entity
 */
class PageVisit
{
    public const TYPE_NOT_MOBILE = 0;
    public const TYPE_MOBILE = 1;

    /**
     * @var string
     * @ORM\Id
     * @ORM\Column(name="PageName", type="string", length=64, nullable=false)
     */
    private $pageName;

    /**
     * @var Usr
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserID", referencedColumnName="UserID")
     * })
     */
    private $user;

    /**
     * @var int
     * @ORM\Column(name="Visits", type="integer", nullable=false)
     */
    private $visits;

    /**
     * @var \DateTime
     * @ORM\Id
     * @ORM\Column(name="Day", type="date", nullable=false)
     */
    private $day;

    /**
     * @var bool
     * @ORM\Id
     * @ORM\Column(name="IsMobile", type="boolean", nullable=false)
     */
    private $isMobile;

    public function getPageName(): string
    {
        return $this->pageName;
    }

    public function setPageName(string $pageName): self
    {
        $this->pageName = $pageName;

        return $this;
    }

    public function getUser(): Usr
    {
        return $this->user;
    }

    public function setUser(Usr $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getVisits(): int
    {
        return $this->visits;
    }

    public function setVisits(int $visits): self
    {
        $this->visits = $visits;

        return $this;
    }

    public function getDay(): \DateTime
    {
        return $this->day;
    }

    public function setDay(\DateTime $day): self
    {
        $this->day = $day;

        return $this;
    }

    public function isMobile(): bool
    {
        return $this->isMobile;
    }

    public function setIsMobile(bool $isMobile): self
    {
        $this->isMobile = $isMobile;

        return $this;
    }
}
