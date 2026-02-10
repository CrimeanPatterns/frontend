<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * BalanceWatch.
 *
 * @ORM\Table(name="BalanceWatch")
 * @ORM\Entity
 */
class BalanceWatch
{
    public const POINTS_SOURCE_TRANSFER = 1;
    public const POINTS_SOURCE_PURCHASE = 2;
    public const POINTS_SOURCE_OTHER = 3;

    /**
     * use for validate values.
     */
    public const POINTS_SOURCE_VALUES = [
        self::POINTS_SOURCE_TRANSFER,
        self::POINTS_SOURCE_PURCHASE,
        // self::POINTS_SOURCE_OTHER, // ignore for TransferTimes.php checkPointSource()
    ];

    public const POINTS_SOURCES = [
        self::POINTS_SOURCE_TRANSFER => 'Transfer',
        self::POINTS_SOURCE_PURCHASE => 'Purchase',
        self::POINTS_SOURCE_OTHER => 'Other',
    ];

    public const REASON_BALANCE_CHANGED = 2;
    public const REASON_TIMEOUT = 3;
    public const REASON_UPDATE_ERROR = 4;
    public const REASON_FORCED_STOP = 5;

    public const REASONS = [
        self::REASON_BALANCE_CHANGED => 'balance changed',
        self::REASON_TIMEOUT => 'update timeout',
        self::REASON_UPDATE_ERROR => 'update error',
        self::REASON_FORCED_STOP => 'forced stop',
    ];

    public const STATUS_NEW = 'N';
    public const STATUS_REVIEW = 'R';
    public const STATUS_ERROR = 'E';
    public const STATUS_GOOD = 'G';

    public const STATUSES = [
        self::STATUS_NEW => 'New',
        self::STATUS_REVIEW => 'Review',
        self::STATUS_ERROR => 'Error',
        self::STATUS_GOOD => 'Good',
    ];

    public const APPROXIMATE_PERCENT_POINT = 15;

    public const ACCOUNT_LOGIN2_EXCLUDE_VALUES = [/* 'US', 'USA', 'United States', */ 'English', 'Login2'];

    /**
     * @var int
     * @ORM\Column(name="BalanceWatchID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var Account
     * @ORM\ManyToOne(targetEntity="Account")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="AccountID", referencedColumnName="AccountID")
     * })
     */
    private $account;

    /**
     * @var Provider
     * @ORM\ManyToOne(targetEntity="Provider")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ProviderID", referencedColumnName="ProviderID")
     * })
     */
    private $provider;

    /**
     * @var Usr
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="PayerUserID", referencedColumnName="UserID")
     * })
     */
    private $payerUser;

    /**
     * @var int
     * @ORM\Column(name="PointsSource", type="integer", columnDefinition="ENUM(1, 2)", nullable=true)
     */
    private $pointsSource;

    /**
     * @var Provider
     * @ORM\ManyToOne(targetEntity="Provider")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="TransferFromProviderID", referencedColumnName="ProviderID")
     * })
     */
    private $transferFromProvider;

    /**
     * @var float
     * @ORM\Column(name="ExpectedPoints", type="decimal", nullable=true)
     */
    private $expectedPoints;

    /**
     * @var \DateTime
     * @ORM\Column(name="TransferRequestDate", type="datetime", nullable=true)
     */
    private $transferRequestDate;

    /**
     * @var int
     * @ORM\Column(name="StopReason", type="integer", nullable=true)
     */
    private $stopReason;

    /**
     * @var \DateTime
     * @ORM\Column(name="StopDate", type="datetime", nullable=true)
     */
    private $stopDate;

    /**
     * @var bool
     * @ORM\Column(name="IsBusiness", type="boolean", nullable=false)
     */
    private $isBusiness;

    /**
     * @var \DateTime
     * @ORM\Column(name="CreationDate", type="datetime", nullable=true)
     */
    private $creationDate;

    /**
     * @ORM\Column(name="SourceProgramRegion", type="string", nullable=true, length=80)
     */
    private ?string $sourceProgramRegion;

    /**
     * @ORM\Column(name="TargetProgramRegion", type="string", nullable=true, length=80)
     */
    private ?string $targetProgramRegion;

    public function __construct()
    {
        $this->creationDate = new \DateTime();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function setAccount(?Account $account = null): self
    {
        $this->account = $account;

        return $this;
    }

    public function getAccount(): ?Account
    {
        return $this->account;
    }

    public function setProvider(?Provider $provider = null): self
    {
        $this->provider = $provider;

        return $this;
    }

    public function getProvider(): ?Provider
    {
        return $this->provider;
    }

    public function setPayerUser(?Usr $payerUser = null): self
    {
        $this->payerUser = $payerUser;

        return $this;
    }

    public function getPayerUser(): ?Usr
    {
        return $this->payerUser;
    }

    public function setPointsSource(int $pointsSource): self
    {
        $this->pointsSource = $pointsSource;

        return $this;
    }

    public function getPointsSource(): ?int
    {
        return $this->pointsSource;
    }

    public function setTransferFromProvider(?Provider $transferProvider): self
    {
        $this->transferFromProvider = $transferProvider;

        return $this;
    }

    public function getTransferFromProvider(): ?Provider
    {
        return $this->transferFromProvider;
    }

    public function setExpectedPoints(?int $expectedPoints): self
    {
        $this->expectedPoints = $expectedPoints;

        return $this;
    }

    /**
     * @param bool $isApproximate
     */
    public function getExpectedPoints($isApproximate = false): ?int
    {
        if ($isApproximate && null !== $this->expectedPoints) {
            return $this->expectedPoints - (self::APPROXIMATE_PERCENT_POINT * $this->expectedPoints / 100);
        }

        return $this->expectedPoints;
    }

    /**
     * @param \DateTime $transferRequestDate |null
     */
    public function setTransferRequestDate(?\DateTime $transferRequestDate): self
    {
        $this->transferRequestDate = $transferRequestDate;

        return $this;
    }

    public function getTransferRequestDate(): \DateTime
    {
        return $this->transferRequestDate;
    }

    public function setStopReason(int $stopReason): self
    {
        $this->stopReason = $stopReason;

        return $this;
    }

    public function getStopReason(): ?int
    {
        return $this->stopReason;
    }

    public function setStopDate(\DateTime $stopDate): self
    {
        $this->stopDate = $stopDate;

        return $this;
    }

    public function getStopDate(): \DateTime
    {
        return $this->stopDate;
    }

    public function setCreationDate(\DateTime $creationDate): self
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    public function isBusiness(): bool
    {
        return $this->isBusiness;
    }

    public function setIsBusiness(bool $isBusiness): self
    {
        $this->isBusiness = $isBusiness;

        return $this;
    }

    public function getCreationDate(): \DateTime
    {
        return $this->creationDate;
    }

    public function getSourceProgramRegion(): ?string
    {
        return $this->sourceProgramRegion;
    }

    public function setSourceProgramRegion(?string $sourceProgramRegion): self
    {
        $this->sourceProgramRegion = $sourceProgramRegion;

        return $this;
    }

    public function getTargetProgramRegion(): ?string
    {
        return $this->targetProgramRegion;
    }

    public function setTargetProgramRegion(?string $targetProgramRegion): self
    {
        $this->targetProgramRegion = $targetProgramRegion;

        return $this;
    }
}
