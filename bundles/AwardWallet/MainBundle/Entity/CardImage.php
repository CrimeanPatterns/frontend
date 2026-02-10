<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

/**
 * CardImage.
 *
 * @ORM\Table(name="CardImage")
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\CardImageRepository")
 */
class CardImage implements ImageInterface
{
    public const KIND_FRONT = 1;
    public const KIND_BACK = 2;

    /**
     * @var int
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(name="CardImageID", type="integer", nullable=false)
     */
    protected $cardImageId;

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
     * @var Account
     * @ORM\ManyToOne(targetEntity="Account", cascade={"detach"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="AccountID", referencedColumnName="AccountID", nullable=true)
     * })
     */
    protected $accountid;

    /**
     * @var Subaccount
     * @ORM\ManyToOne(targetEntity="Subaccount", cascade={"detach"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="SubAccountID", referencedColumnName="SubAccountID", nullable=true)
     * })
     */
    protected $subaccountid;

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
     * @ORM\Column(name="Kind", type="integer", nullable=false)
     */
    protected $kind;

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

    /**
     * @var Provider
     * @ORM\ManyToOne(targetEntity="Provider")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="DetectedProviderID", referencedColumnName="ProviderID")
     * })
     */
    protected $detectedProviderId;

    /**
     * @var array
     * @ORM\Column(name="ComputerVisionResult", type="json_array", nullable=true)
     */
    protected $computerVisionResult = [];

    /**
     * @var Provider
     * @ORM\ManyToOne(targetEntity="Provider")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ProviderID", referencedColumnName="ProviderID")
     * })
     */
    protected $providerId;

    /**
     * @var bool
     * @ORM\Column(name="CCDetected", type="boolean", nullable=false)
     */
    protected $ccDetected = false;

    /**
     * @var string
     * @ORM\Column(name="CCDetectorVersion", type="string", nullable=true)
     */
    protected $ccDetectorVersion;

    public function __construct()
    {
        $this->UUID = Uuid::uuid4();
    }

    /**
     * @return int
     */
    public function getCardImageId()
    {
        return $this->cardImageId;
    }

    /**
     * @return Usr
     */
    public function getUser()
    {
        return $this->userId;
    }

    /**
     * @return CardImage
     */
    public function setUser(Usr $userId)
    {
        $this->clearContainers([
            &$this->accountid,
            &$this->subaccountid,
            &$this->providercouponid,
        ]);
        $this->userId = $userId;

        return $this;
    }

    /**
     * @return Account
     */
    public function getAccount()
    {
        return $this->accountid;
    }

    /**
     * @return Subaccount
     */
    public function getSubAccount()
    {
        return $this->subaccountid;
    }

    /**
     * @return CardImage
     */
    public function setAccount(Account $account)
    {
        $this->doSetContainer([&$this->providercouponid, &$this->subaccountid], $this->accountid, $account);
        $this->setProviderId($account->getProviderid());

        return $this;
    }

    public function setSubAccount(Subaccount $subaccount): self
    {
        $this->doSetContainer([&$this->accountid, &$this->providercouponid], $this->subaccountid, $subaccount);
        $this->providerId = null;

        return $this;
    }

    /**
     * @return $this
     */
    public function setProviderCoupon(Providercoupon $providerCoupon): self
    {
        $this->doSetContainer([&$this->accountid, &$this->subaccountid], $this->providercouponid, $providerCoupon);
        $this->providerId = null;

        return $this;
    }

    /**
     * @return $this
     */
    public function setContainer(CardImageContainerInterface $container)
    {
        if ($container instanceof Account) {
            $this->setAccount($container);
        } elseif ($container instanceof Providercoupon) {
            $this->setProviderCoupon($container);
        } elseif ($container instanceof Subaccount) {
            $this->setSubAccount($container);
        } else {
            throw new \InvalidArgumentException('Unknown container type');
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function hasContainer()
    {
        return (bool) ($this->subaccountid ?: ($this->accountid ?: $this->providercouponid));
    }

    /**
     * @return LoyaltyProgramInterface
     */
    public function getContainer()
    {
        $container = $this->subaccountid ?: ($this->accountid ?: $this->providercouponid);

        if (!$container) {
            throw new \OutOfBoundsException('Container is uninitialized');
        }

        return $container;
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
    public function getKind()
    {
        return $this->kind;
    }

    /**
     * @param int $kind
     * @return CardImage
     */
    public function setKind($kind)
    {
        $this->kind = $kind;

        return $this;
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
     * @return CardImage
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
     * @return CardImage
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
     * @return CardImage
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
     * @return CardImage
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
     * @return CardImage
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
     * @return CardImage
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

    public function setUUID(Uuid $UUID): self
    {
        $this->UUID = $UUID;

        return $this;
    }

    /**
     * @return Provider
     */
    public function getDetectedProviderId()
    {
        return $this->detectedProviderId;
    }

    public function setDetectedProviderId(?Provider $detectedProviderId = null): self
    {
        $this->detectedProviderId = $detectedProviderId;

        return $this;
    }

    /**
     * @return Provider|null
     */
    public function getProviderId()
    {
        return $this->providerId;
    }

    public function setProviderId(?Provider $providerId = null): self
    {
        $this->providerId = $providerId;

        return $this;
    }

    /**
     * @return CardImage
     */
    public function setUploadDate(\DateTime $uploadDate)
    {
        $this->uploadDate = $uploadDate;

        return $this;
    }

    public function isCcDetected(): bool
    {
        return $this->ccDetected;
    }

    /**
     * @return CardImage
     */
    public function setCcDetected(bool $ccDetected)
    {
        $this->ccDetected = $ccDetected;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCcDetectorVersion()
    {
        return $this->ccDetectorVersion;
    }

    public function setCcDetectorVersion(string $ccDetectorVersion): self
    {
        $this->ccDetectorVersion = $ccDetectorVersion;

        return $this;
    }

    public function getComputerVisionResult(): array
    {
        return $this->computerVisionResult;
    }

    public function setComputerVisionResult(array $computerVisionResult): self
    {
        $this->computerVisionResult = $computerVisionResult;

        return $this;
    }

    public function updateComputerVisionResult(array $mixin): self
    {
        $this->computerVisionResult = array_merge((array) $this->computerVisionResult, $mixin);

        return $this;
    }

    public function hasGoogleVisionResposne(): bool
    {
        return isset($this->computerVisionResult['googleVision']);
    }

    public function getGoogleVisionResponse(): array
    {
        return $this->computerVisionResult['googleVision'];
    }

    public function setGoogleVisionResponse(array $resposne): self
    {
        $this->computerVisionResult['googleVision'] = $resposne;

        return $this;
    }

    public function updateAwProviderDetect(array $mixin): self
    {
        $this->computerVisionResult['aw_provider_detect'] = array_merge(
            $this->computerVisionResult['aw_provider_detect'] ?? [],
            $mixin
        );

        return $this;
    }

    public function setParsingResult(array $parsingResult, array $supportedProperties, array $formProperties): self
    {
        $this->computerVisionResult['aw_parsing']['result'] = $parsingResult;
        $this->computerVisionResult['aw_parsing']['supported_properties'] = $supportedProperties;
        $this->computerVisionResult['aw_parsing']['form_properties'] = $formProperties;

        return $this;
    }

    public function setCreditCardDetectionResult(array $rects): self
    {
        $this->computerVisionResult['aw_credit_card_detection']['rects'] = $rects;

        return $this;
    }

    /**
     * @param CardImageContainerInterface[] $clearQueue
     * @param CardImageContainerInterface $containerRef
     * @return $this
     */
    protected function doSetContainer(array $clearQueue, &$containerRef, CardImageContainerInterface $newContainer): self
    {
        $this->clearContainers($clearQueue);
        $containerRef = $newContainer;
        $newContainer->addCardImage($this);
        $this->userId = null;

        return $this;
    }

    /**
     * @param CardImageContainerInterface[] $clearQueue
     */
    protected function clearContainers(array $clearQueue)
    {
        foreach ($clearQueue as &$oldContainer) {
            if ($oldContainer) {
                $oldContainer->removeCardImage($this);
                $oldContainer = null;
            }
        }

        unset($oldContainer);
    }
}
