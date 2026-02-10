<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * BlogComment.
 *
 * @ORM\Table(name="BlogComment")
 * @ORM\Entity
 */
class BlogComment
{
    /**
     * @var int
     * @ORM\Column(name="BlogCommentID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="PostTitle", type="string", length=255, nullable=false)
     */
    private $postTitle;

    /**
     * @var string
     * @ORM\Column(name="PostLink", type="string", length=255, nullable=false)
     */
    private $postLink;

    /**
     * @var \DateTime
     * @ORM\Column(name="PostUpdate", type="datetime", nullable=false)
     */
    private $postUpdate;

    /**
     * @var int
     * @ORM\Column(name="CommentCount", type="integer", nullable=false)
     */
    private $commentCount;

    /**
     * @var string
     * @ORM\Column(name="CommentAuthor", type="string", length=128, nullable=false)
     */
    private $commentAuthor;

    /**
     * @var \DateTime
     * @ORM\Column(name="CommentDate", type="datetime", nullable=false)
     */
    private $commentDate;

    /**
     * @var string
     * @ORM\Column(name="CommentEmail", type="string", length=128, nullable=false)
     */
    private $commentEmail;

    /**
     * @var string
     * @ORM\Column(name="CommentLink", type="string", length=255, nullable=false)
     */
    private $commentLink;

    /**
     * @var string
     * @ORM\Column(name="CommentContent", type="string", nullable=false)
     */
    private $commentContent;

    /**
     * @var string
     * @ORM\Column(name="Subscribers", type="string", nullable=false)
     */
    private $subscribers;

    public function getId(): int
    {
        return $this->id;
    }

    public function setPostTitle(string $title): self
    {
        $this->postTitle = $title;

        return $this;
    }

    public function getPostTitle(): string
    {
        return $this->postTitle;
    }

    public function setPostLink(string $link): self
    {
        $this->postLink = $link;

        return $this;
    }

    public function getPostLink(): string
    {
        return $this->postLink;
    }

    public function setPostUpdate(\DateTime $dateTime): self
    {
        $this->postUpdate = $dateTime;

        return $this;
    }

    public function getPostUpdate(): \DateTime
    {
        return $this->postUpdate;
    }

    public function setCommentCount(int $count): self
    {
        $this->commentCount = $count;

        return $this;
    }

    public function getCommentCount(): int
    {
        return $this->commentCount;
    }

    public function setCommentAuthor(string $author): self
    {
        $this->commentAuthor = $author;

        return $this;
    }

    public function getCommentAuthor(): string
    {
        return $this->commentAuthor;
    }

    public function setCommentDate(\DateTime $dateTime): self
    {
        $this->commentDate = $dateTime;

        return $this;
    }

    public function getCommentDate(): \DateTime
    {
        return $this->commentDate;
    }

    public function setCommentEmail(string $email): self
    {
        $this->commentEmail = $email;

        return $this;
    }

    public function getCommentEmail(): string
    {
        return $this->commentEmail;
    }

    public function setCommentLink(string $link): self
    {
        $this->commentLink = $link;

        return $this;
    }

    public function getCommentLink(): string
    {
        return $this->commentLink;
    }

    public function setCommentContent(string $content): self
    {
        $this->commentContent = $content;

        return $this;
    }

    public function getCommentContent(): string
    {
        return $this->commentContent;
    }

    public function setSubscribers(array $data): self
    {
        $this->subscribers = json_encode($data);

        return $this;
    }

    public function getSubscribers(): array
    {
        return json_decode($this->subscribers);
    }
}
