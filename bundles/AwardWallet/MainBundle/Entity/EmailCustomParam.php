<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="EmailCustomParam")
 * @ORM\Entity
 */
class EmailCustomParam
{
    public const TYPE_BLOG_WEEKLY_DIGEST = 1;

    public const TYPES = [
        self::TYPE_BLOG_WEEKLY_DIGEST => 'Blog Weekly Digest',
    ];

    /**
     * @ORM\Id
     * @ORM\Column(name="EmailCustomParamID", type="integer", nullable=false)
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private int $id;

    /**
     * @ORM\Column(name="Type", type="integer", nullable=false)
     */
    private int $type;

    /**
     * @ORM\Column(name="EventDate", type="date", nullable=true)
     */
    private ?\DateTime $eventDate;

    /**
     * @ORM\Column(name="Subject", type="text", length=255, nullable=true)
     */
    private ?string $subject;

    /**
     * @ORM\Column(name="Preview", type="text", length=255, nullable=true)
     */
    private ?string $preview;

    /**
     * @ORM\Column(name="Message", type="text", nullable=true)
     */
    private ?string $message;

    /**
     * @ORM\Column(name="UpdateDate", type="datetime", nullable=false)
     */
    private \DateTime $updateDate;

    public function getId(): int
    {
        return $this->id;
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

    public function getEventDate(): ?\DateTime
    {
        return $this->eventDate;
    }

    public function setEventDate(?\DateTime $eventDate): self
    {
        $this->eventDate = $eventDate;

        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function getPreview(): ?string
    {
        return $this->preview;
    }

    public function setPreview(?string $preview): self
    {
        $this->preview = $preview;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getUpdateDate(): \DateTime
    {
        return $this->updateDate;
    }

    public function setUpdateDate(\DateTime $updateDate): self
    {
        $this->updateDate = $updateDate;

        return $this;
    }
}
