<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="QsTransaction")
 */
class QsTransaction
{
    public const ACCOUNT_DIRECT = 1;
    public const ACCOUNT_AWARDTRAVEL101 = 2;
    public const ACCOUNT_CARDRATINGS = 3;

    public const ACCOUNTS = [
        self::ACCOUNT_DIRECT => 'Direct',
        self::ACCOUNT_AWARDTRAVEL101 => 'Award Travel 101',
        self::ACCOUNT_CARDRATINGS => 'CardRatings',
    ];

    public const VERSION_QMP_XLS = 2;
    public const VERSION_QMP_API_AI = 3;

    public const ACTUAL_VERSION = 3;

    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(name="QsTransactionID", type="integer", nullable=false)
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \DateTime
     * @ORM\Column(name="ClickDate", type="datetime", nullable=false)
     */
    private $clickDate;

    /**
     * @var \DateTime
     * @ORM\Column(name="SearchDate", type="datetime", nullable=false)
     */
    private $searchDate;

    /**
     * @var \DateTime
     * @ORM\Column(name="ProcessDate", type="datetime", nullable=false)
     */
    private $processDate;

    /**
     * @var int
     * @ORM\Column(name="Account", type="integer", nullable=true)
     */
    private $account;

    /**
     * @var QsCreditCard
     * @ORM\ManyToOne(targetEntity="QsCreditCard")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="QsCreditCardID", referencedColumnName="QsCreditCardID", nullable=true)
     * })
     */
    private $qsCreditCard;

    /**
     * @var string
     * @ORM\Column(name="Card", type="string", length=255, nullable=true)
     */
    private $card;

    /**
     * @var string
     * @ORM\Column(name="`Source`", type="string", length=64, nullable=true)
     */
    private $source;

    /**
     * @var string
     * @ORM\Column(name="`Exit`", type="string", length=64, nullable=true)
     */
    private $exit;

    /**
     * @var int
     * @ORM\Column(name="BlogPostID", type="integer", nullable=true)
     */
    private $blogPostId;

    /**
     * @var string
     * @ORM\Column(name="MID", type="string", length=64, nullable=true)
     */
    private $mid;

    /**
     * @var string
     * @ORM\Column(name="CID", type="string", length=64, nullable=true)
     */
    private $cid;

    /**
     * @var string
     * @ORM\Column(name="RefCode", type="string", length=16, nullable=true)
     */
    private $refCode;

    /**
     * @var Usr
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserID", referencedColumnName="UserID", nullable=true)
     * })
     */
    private $user;

    /**
     * @var int
     * @ORM\Column(name="Clicks", type="integer")
     */
    private $clicks;

    /**
     * @var int
     * @ORM\Column(name="Earnings", type="decimal")
     */
    private $earnings;

    /**
     * @var bool
     * @ORM\Column(name="Approvals", type="integer")
     */
    private $approvals = 0;

    /**
     * @var int
     * @ORM\Column(name="Click_ID", type="integer", nullable=false)
     */
    private $clickId;

    /**
     * @var string
     * @ORM\Column(name="RawAccount", type="string", length=255, nullable=true)
     */
    private $rawAccount;

    /**
     * @var string
     * @ORM\Column(name="RawVar1", type="string", length=255, nullable=true)
     */
    private $rawVar1;

    /**
     * @var string
     * @ORM\Column(name="Hash", type="string", length=40, nullable=false)
     */
    private $hash;

    /**
     * @var \DateTime
     * @ORM\Column(name="CreationDate", type="datetime", nullable=false)
     */
    private $creationDate;

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return $this
     */
    public function setClickDate(\DateTime $date): self
    {
        $this->clickDate = $date;

        return $this;
    }

    public function getClickDate(): \DateTime
    {
        return $this->clickDate;
    }

    /**
     * @return $this
     */
    public function setSearchDate(\DateTime $date): self
    {
        $this->searchDate = $date;

        return $this;
    }

    public function getSearchDate(): \DateTime
    {
        return $this->searchDate;
    }

    /**
     * @return $this
     */
    public function setProcessDate(\DateTime $date): self
    {
        $this->processDate = $date;

        return $this;
    }

    public function getProcessDate(): \DateTime
    {
        return $this->processDate;
    }

    /**
     * @return $this
     */
    public function setAccount(int $account): self
    {
        $this->account = $account;

        return $this;
    }

    public function getAccount(): int
    {
        return $this->account;
    }

    /**
     * @return $this
     */
    public function setQsCreditCard(QsCreditCard $card)
    {
        $this->qsCreditCard = $card;

        return $this;
    }

    public function getQsCreditCard(): ?QsCreditCard
    {
        return $this->qsCreditCard;
    }

    /**
     * @return $this
     */
    public function setCard(string $cardName): self
    {
        $this->card = $cardName;

        return $this;
    }

    public function getCard(): string
    {
        return $this->card;
    }

    /**
     * @return $this
     */
    public function setSource(string $source): self
    {
        $this->source = $source;

        return $this;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * @return $this
     */
    public function setExit(string $exit): self
    {
        $this->exit = $exit;

        return $this;
    }

    public function getExit(): string
    {
        return $this->exit;
    }

    /**
     * @return $this
     */
    public function setBlogPostId(int $postId): self
    {
        $this->blogPostId = $postId;

        return $this;
    }

    public function getBlogPostId(): int
    {
        return $this->blogPostId;
    }

    /**
     * @return $this
     */
    public function setMid(string $mid): self
    {
        $this->mid = $mid;

        return $this;
    }

    public function getMid(): string
    {
        return $this->mid;
    }

    /**
     * @return $this
     */
    public function setCid(string $cid): self
    {
        $this->cid = $cid;

        return $this;
    }

    public function getCid(): string
    {
        return $this->cid;
    }

    /**
     * @return $this
     */
    public function setRefCode(string $refCode): self
    {
        $this->refCode = $refCode;

        return $this;
    }

    public function getRefCode(): ?string
    {
        return $this->refCode;
    }

    /**
     * @return $this
     */
    public function setUser(Usr $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getUser(): ?Usr
    {
        return $this->user;
    }

    /**
     * @return $this
     */
    public function setClicks(int $clicks): self
    {
        $this->clicks = $clicks;

        return $this;
    }

    public function getClicks(): int
    {
        return $this->clicks;
    }

    /**
     * @return $this
     */
    public function setClickId(int $clickId): self
    {
        $this->clickId = $clickId;

        return $this;
    }

    public function getClickId(): int
    {
        return $this->clickId;
    }

    /**
     * @return $this
     */
    public function setEarnings(float $earnings): self
    {
        $this->earnings = $earnings;

        return $this;
    }

    public function getEarnings(): int
    {
        return $this->earnings;
    }

    /**
     * @return $this
     */
    public function setApprovals(bool $approvals): self
    {
        $this->approvals = $approvals;

        return $this;
    }

    public function getApprovals(): bool
    {
        return $this->approvals;
    }

    /**
     * @return $this
     */
    public function setRawAccount(string $rawAccount): self
    {
        $this->rawAccount = $rawAccount;

        return $this;
    }

    public function getRawAccount(): string
    {
        return $this->rawAccount;
    }

    /**
     * @return $this
     */
    public function setRawVar1(string $rawVar1): self
    {
        $this->rawVar1 = $rawVar1;

        return $this;
    }

    public function getRawVar1(): string
    {
        return $this->rawVar1;
    }

    /**
     * @return $this
     */
    public function setHash(string $hash): self
    {
        $this->hash = $hash;

        return $this;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function setCreationDate(\DateTime $creationDate): self
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    public function getCreationDate(): \DateTime
    {
        return $this->creationDate;
    }
}
