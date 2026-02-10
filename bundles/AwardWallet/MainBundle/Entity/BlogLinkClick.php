<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="BlogLinkClick")
 * @ORM\Entity
 */
class BlogLinkClick
{
    /**
     * @ORM\Column(name="BlogLinkClickID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private int $id;

    /**
     * @ORM\Column(name="PrettyLink", type="string", nullable=true)
     */
    private ?string $prettyLink;

    /**
     * @ORM\Column(name="TargetLink", type="string", nullable=true)
     */
    private ?string $targetLink;

    /**
     * @ORM\Column(name="Source", type="string", nullable=true)
     */
    private ?string $source;

    /**
     * @ORM\Column(name="Exit", type="string", nullable=true)
     */
    private ?string $exit;

    /**
     * @ORM\Column(name="MID", type="string", nullable=true)
     */
    private ?string $mid;

    /**
     * @ORM\Column(name="CID", type="string", nullable=true)
     */
    private ?string $cid;

    /**
     * @ORM\Column(name="RefCode", type="string", nullable=true)
     */
    private ?string $refCode;

    /**
     * @ORM\ManyToOne(targetEntity="Usr", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserID", referencedColumnName="UserID")
     * })
     */
    private ?Usr $user;

    /**
     * @ORM\Column(name="BlogPostID", type="integer", nullable=true)
     */
    private ?int $blogPostId;

    /**
     * @ORM\Column(name="UserAgent", type="string", nullable=true)
     */
    private ?string $userAgent;

    /**
     * @ORM\Column(name="ClickDate", type="datetime", nullable=false)
     */
    private \DateTime $clickDate;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getPrettyLink(): ?string
    {
        return $this->prettyLink;
    }

    public function setPrettyLink(?string $prettyLink): self
    {
        $this->prettyLink = $prettyLink;

        return $this;
    }

    public function getTargetLink(): ?string
    {
        return $this->targetLink;
    }

    public function setTargetLink(?string $targetLink): self
    {
        $this->targetLink = $targetLink;

        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): self
    {
        $this->source = $source;

        return $this;
    }

    public function getExit(): ?string
    {
        return $this->exit;
    }

    public function setExit(?string $exit): self
    {
        $this->exit = $exit;

        return $this;
    }

    public function getMid(): ?string
    {
        return $this->mid;
    }

    public function setMid(?string $mid): self
    {
        $this->mid = $mid;

        return $this;
    }

    public function getCid(): ?string
    {
        return $this->cid;
    }

    public function setCid(?string $cid): self
    {
        $this->cid = $cid;

        return $this;
    }

    public function getRefCode(): ?string
    {
        return $this->refCode;
    }

    public function setRefCode(?string $refCode): self
    {
        $this->refCode = $refCode;

        return $this;
    }

    public function getUser(): ?Usr
    {
        return $this->user;
    }

    public function setUser(?Usr $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getBlogPostId(): ?int
    {
        return $this->blogPostId;
    }

    public function setBlogPostId(?int $blogPostId): self
    {
        $this->blogPostId = $blogPostId;

        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): self
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    public function getClickDate(): \DateTime
    {
        return $this->clickDate;
    }

    public function setClickDate(\DateTime $clickDate): self
    {
        $this->clickDate = $clickDate;

        return $this;
    }
}
