<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="CreditCardOffer")
 */
class CreditCardOffer
{
    public const OFFER_QUALITY_BEST = 1;
    public const OFFER_QUALITY_INCREASED = 2;
    public const OFFER_QUALITY_STANDARD = 3;

    public const OFFER_QUALITY_LIST = [
        self::OFFER_QUALITY_BEST => 'Best Ever',
        self::OFFER_QUALITY_INCREASED => 'Increased',
        self::OFFER_QUALITY_STANDARD => 'Standard',
    ];

    /**
     * @ORM\Id
     * @ORM\Column(name="CreditCardOfferID", type="integer", nullable=false)
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private ?int $id;

    /**
     * @ORM\Column(name="StartDate", type="datetime", nullable=false)
     */
    private \DateTime $startDate;

    /**
     * @ORM\Column(name="EndDate", type="datetime", nullable=true)
     */
    private ?\DateTime $endDate;

    /**
     * @ORM\ManyToOne(targetEntity="CreditCard")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="CreditCardID", referencedColumnName="CreditCardID")
     * })
     */
    private CreditCard $creditCard;

    /**
     * @ORM\Column(name="OfferNote", type="text", nullable=false)
     */
    private string $offerNote;

    /**
     * @ORM\Column(name="SubjectiveValue", type="integer", nullable=true)
     */
    private ?int $subjectiveValue;

    /**
     * @ORM\Column(name="PrimaryPostID", type="integer", nullable=true)
     */
    private ?int $primaryPostId;

    /**
     * @ORM\Column(name="SupportingPostID", type="string", length=255, nullable=true)
     */
    private ?string $supportingPostId;

    /**
     * @ORM\Column(name="OfferQuality", type="integer", nullable=true)
     */
    private ?int $offerQuality;

    /**
     * @ORM\Column(name="IsMonetized", type="boolean", nullable=false)
     */
    private bool $isMonetized;

    public function __construct()
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStartDate(): \DateTime
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTime $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTime
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTime $endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getCreditCard(): CreditCard
    {
        return $this->creditCard;
    }

    public function setCreditCard(CreditCard $creditCard): self
    {
        $this->creditCard = $creditCard;

        return $this;
    }

    public function getOfferNote(): string
    {
        return $this->offerNote;
    }

    public function setOfferNote(string $offerNote): self
    {
        $this->offerNote = $offerNote;

        return $this;
    }

    public function getSubjectiveValue(): ?int
    {
        return $this->subjectiveValue;
    }

    public function setSubjectiveValue(?int $subjectiveValue): self
    {
        $this->subjectiveValue = $subjectiveValue;

        return $this;
    }

    public function getPrimaryPostId(): ?int
    {
        return $this->primaryPostId;
    }

    public function setPrimaryPostId(?int $primaryPostId): self
    {
        $this->primaryPostId = $primaryPostId;

        return $this;
    }

    public function getSupportingPostId(): ?string
    {
        return $this->supportingPostId;
    }

    public function setSupportingPostId(?string $supportingPostId): self
    {
        $this->supportingPostId = $supportingPostId;

        return $this;
    }

    public function getOfferQuality(): ?int
    {
        return $this->offerQuality;
    }

    public function setOfferQuality(?int $offerQuality): self
    {
        $this->offerQuality = $offerQuality;

        return $this;
    }

    public function isMonetized(): bool
    {
        return $this->isMonetized;
    }

    public function setIsMonetized(bool $isMonetized): self
    {
        $this->isMonetized = $isMonetized;

        return $this;
    }
}
