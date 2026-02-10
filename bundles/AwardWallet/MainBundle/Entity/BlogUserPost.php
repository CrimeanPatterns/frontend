<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="BlogUserPost")
 * @ORM\Entity
 */
class BlogUserPost
{
    public const TYPE_FAVORITE = 1;
    public const TYPE_MARK_READ = 2;

    public const TYPES = [
        self::TYPE_FAVORITE => 'Favorites Posts',
        self::TYPE_MARK_READ => 'Learned Posts',
    ];

    /**
     * @ORM\Column(name="CreationDateTime", type="datetime", nullable=false)
     */
    protected \DateTime $createDateTime;

    /**
     * @ORM\Column(name="BlogUserPostID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private ?int $id;

    /**
     * @ORM\Column(name="Type", type="integer", length=3, nullable=false)
     */
    private int $type;

    /**
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumn(name="UserID", referencedColumnName="UserID", nullable=false)
     */
    private ?Usr $user;

    /**
     * @ORM\Column(name="PostID", type="bigint", nullable=false)
     */
    private int $postId;

    public function __construct()
    {
        $this->createDateTime = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function setType(int $type): self
    {
        $this->type = $type;

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

    public function getPostId(): int
    {
        return $this->postId;
    }

    public function setPostId(int $postId): self
    {
        $this->postId = $postId;

        return $this;
    }

    public function getCreateDateTime(): \DateTime
    {
        return $this->createDateTime;
    }

    public function setCreateDateTime(\DateTime $createDateTime): self
    {
        $this->createDateTime = $createDateTime;

        return $this;
    }
}
