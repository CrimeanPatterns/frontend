<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 *  name="BlogUserReport",
 *  indexes={@ORM\Index(name="UserIDreport_fk", columns={"UserID"})}
 * )
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class BlogUserReport
{
    /**
     * @var int
     * @ORM\Column(name="BlogUserReportID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var Usr
     * @ORM\ManyToOne(targetEntity="Usr", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserID", referencedColumnName="UserID")
     * })
     */
    private $user;

    /**
     * @var int
     * @ORM\Column(name="BlogPostID", type="integer", nullable=true)
     */
    private $blogPostId;

    /**
     * @var \DateTime
     * @ORM\Column(name="InTime", type="datetime", nullable=true)
     */
    private $inTime;

    /**
     * @var \DateTime
     * @ORM\Column(name="OutTime", type="datetime", nullable=true)
     */
    private $outTime;

    /**
     * @var int
     * @ORM\Column(name="TimeZoneOffset", type="integer", nullable=true)
     */
    private $timeZoneOffset;

    /**
     * @var bool
     * @ORM\Column(name="IsAuthorized ", type="boolean", nullable=false)
     */
    private $isAuthorized;

    public function getId(): int
    {
        return $this->id;
    }

    public function setUser(Usr $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getUser(): ?Usr
    {
        return $this->user;
    }

    public function setBlogPostId(int $postId): self
    {
        $this->blogPostId = $postId;

        return $this;
    }

    public function getBlogPostId(): int
    {
        return $this->blogPostId;
    }

    public function setInTime(?\DateTime $dateTime): self
    {
        $this->inTime = $dateTime;

        return $this;
    }

    public function getInTime(): ?\DateTime
    {
        return $this->inTime;
    }

    public function setOutTime(?\DateTime $dateTime): self
    {
        $this->outTime = $dateTime;

        return $this;
    }

    public function getOutTime(): ?\DateTime
    {
        return $this->outTime;
    }

    public function setTimeZoneOffset(?int $timeZoneOffset): self
    {
        $this->timeZoneOffset = $timeZoneOffset;

        return $this;
    }

    public function getTimeZoneOffset(): ?int
    {
        return $this->timeZoneOffset;
    }

    public function IsAuthorized(): bool
    {
        return $this->isAuthorized;
    }

    public function setIsAuthorized(bool $isAuthorized): self
    {
        $this->isAuthorized = $isAuthorized;

        return $this;
    }
}
