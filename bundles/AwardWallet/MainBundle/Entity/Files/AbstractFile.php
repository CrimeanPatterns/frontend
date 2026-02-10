<?php

namespace AwardWallet\MainBundle\Entity\Files;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\MappedSuperclass
 */
abstract class AbstractFile
{
    /**
     * @ORM\Column(name="FileName", type="string", length=140, nullable=false)
     */
    protected string $fileName;

    /**
     * @ORM\Column(name="FileSize", type="integer", nullable=false)
     */
    protected int $fileSize = 0;

    /**
     * @ORM\Column(name="Format", type="string", length=20, nullable=false)
     */
    protected string $format;

    /**
     * @ORM\Column(name="Description", type="string", length=250, nullable=true)
     */
    protected ?string $description = null;

    /**
     * @ORM\Column(name="StorageKey", type="string", length=128, nullable=false)
     * @JMS\Exclude();
     */
    protected string $storageKey;

    /**
     * @ORM\Column(name="UploadDate", type="datetime", nullable=false)
     */
    protected \DateTime $uploadDate;

    /**
     * @ORM\Column(name="UUID", type="uuid", unique=true, nullable=false)
     * @JMS\Exclude();
     */
    protected UuidInterface $uuid;

    public function __construct()
    {
        $this->uuid = Uuid::uuid4();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function setFileName(string $fileName): self
    {
        $this->fileName = $fileName;

        return $this;
    }

    public function getFileSize(): ?int
    {
        return $this->fileSize;
    }

    public function setFileSize(int $fileSize): self
    {
        $this->fileSize = $fileSize;

        return $this;
    }

    public function getFormat(): ?string
    {
        return $this->format;
    }

    public function setFormat(string $format): self
    {
        $this->format = $format;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getStorageKey(): ?string
    {
        return $this->storageKey;
    }

    public function setStorageKey(string $storageKey): self
    {
        $this->storageKey = $storageKey;

        return $this;
    }

    public function getUploadDate(): \DateTime
    {
        return $this->uploadDate;
    }

    public function setUploadDate(\DateTime $uploadDate): self
    {
        $this->uploadDate = $uploadDate;

        return $this;
    }

    public function getUuid(): UuidInterface
    {
        return $this->uuid;
    }

    public function setUuid(Uuid $uuid)
    {
        $this->uuid = $uuid;

        return $this;
    }
}
