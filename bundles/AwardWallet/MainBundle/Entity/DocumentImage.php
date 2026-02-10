<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * DocumentImage.
 *
 * @ORM\Table(name="DocumentImage")
 * @ORM\Entity
 */
class DocumentImage implements ImageInterface
{
    /**
     * @var int
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(name="DocumentImageID", type="integer", nullable=false)
     */
    protected $documentImageId;

    /**
     * @var Usr
     * @var Account
     * @ORM\ManyToOne(targetEntity="Usr", cascade={"detach"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserID", referencedColumnName="UserID", nullable=true)
     * })
     */
    protected $userId;

    /**
     * @var Providercoupon
     * @ORM\ManyToOne(targetEntity="Providercoupon", cascade={"detach"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ProviderCouponID", referencedColumnName="ProviderCouponID", nullable=true)
     * })
     */
    protected $providercouponid;

    /**
     * @var int
     * @ORM\Column(name="Width", type="integer", nullable=false)
     */
    protected $width;

    /**
     * @var int
     * @ORM\Column(name="Height", type="integer", nullable=false)
     */
    protected $height;
    /**
     * @var string
     * @ORM\Column(name="FileName", type="string", nullable=false)
     */
    protected $fileName;

    /**
     * @var int
     * @ORM\Column(name="FileSize", type="integer", nullable=false)
     */
    protected $fileSize;

    /**
     * @var string
     * @ORM\Column(name="Format", type="string", nullable=false)
     */
    protected $format;

    /**
     * @var string
     * @ORM\Column(name="StorageKey", type="string", nullable=false)
     */
    protected $storageKey;

    /**
     * @var \DateTime
     * @ORM\Column(name="UploadDate", type="datetime", nullable=false)
     */
    protected $uploadDate;

    /**
     * @var string
     * @ORM\Column(name="ClientUUID", type="string", nullable=true)
     */
    protected $clientUUID;

    /**
     * @var Uuid
     * @ORM\Column(type="uuid", unique=true, nullable=false)
     */
    protected $UUID;

    public function __construct()
    {
        $this->UUID = Uuid::uuid4();
    }

    /**
     * @return int
     */
    public function getDocumentImageId()
    {
        return $this->documentImageId;
    }

    /**
     * @return Usr
     */
    public function getUser()
    {
        return $this->userId;
    }

    /**
     * @return DocumentImage
     */
    public function setUser(Usr $userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * @return $this
     */
    public function setProviderCoupon(Providercoupon $providerCoupon): self
    {
        $this->providercouponid = $providerCoupon;

        return $this;
    }

    /**
     * @return Providercoupon
     */
    public function getProviderCoupon()
    {
        return $this->providercouponid;
    }

    /**
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param int $width
     * @return DocumentImage
     */
    public function setWidth($width)
    {
        $this->width = $width;

        return $this;
    }

    /**
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param int $height
     * @return DocumentImage
     */
    public function setHeight($height)
    {
        $this->height = $height;

        return $this;
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * @param string $fileName
     * @return DocumentImage
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;

        return $this;
    }

    /**
     * @return int
     */
    public function getFileSize()
    {
        return $this->fileSize;
    }

    /**
     * @param int $fileSize
     * @return DocumentImage
     */
    public function setFileSize($fileSize)
    {
        $this->fileSize = $fileSize;

        return $this;
    }

    /**
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * @param string $format
     * @return DocumentImage
     */
    public function setFormat($format)
    {
        $this->format = $format;

        return $this;
    }

    /**
     * @return string
     */
    public function getStorageKey()
    {
        return $this->storageKey;
    }

    /**
     * @param string $storageKey
     * @return DocumentImage
     */
    public function setStorageKey($storageKey)
    {
        $this->storageKey = $storageKey;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUploadDate()
    {
        return $this->uploadDate;
    }

    /**
     * @return DocumentImage
     */
    public function setUploadDate(\DateTime $uploadDate)
    {
        $this->uploadDate = $uploadDate;

        return $this;
    }

    /**
     * @return string
     */
    public function getClientUUID()
    {
        return $this->clientUUID;
    }

    /**
     * @param string $uuid
     */
    public function setClientUUID($uuid): self
    {
        $this->clientUUID = $uuid;

        return $this;
    }

    public function getUUID(): Uuid
    {
        return $this->UUID;
    }

    public function setUUID(UuidInterface $UUID): self
    {
        $this->UUID = $UUID;

        return $this;
    }
}
