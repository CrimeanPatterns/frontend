<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="QsCreditCard")
 */
class QsCreditCard
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(name="QsCreditCardID", type="integer", nullable=false)
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var int
     * @ORM\Column(name="QsCardInternalKey", type="integer", nullable=true)
     */
    private $qsCardInternalKey;

    /**
     * @var string
     * @ORM\Column(name="CardName", type="string", length=255, nullable=false)
     */
    private $cardName;

    /**
     * @var string
     * @ORM\Column(name="BonusMilesFull", type="text", nullable=true)
     */
    private $bonusMilesFull;

    /**
     * @var string
     * @ORM\Column(name="Slug", type="string", length=64, nullable=true)
     */
    private $slug;

    /**
     * @var CreditCard|null
     * @ORM\ManyToOne(targetEntity="CreditCard")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="AwCreditCardID", referencedColumnName="CreditCardID")
     * })
     */
    private $awCreditCard;

    /**
     * @var bool
     * @ORM\Column(name="IsManual", type="boolean", nullable=false)
     */
    private $isManual;

    /**
     * @var \DateTime
     * @ORM\Column(name="UpdateDate", type="datetime", nullable=false)
     */
    private $updateDate;

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return $this
     */
    public function setQsCardInternalKey(int $cardId): self
    {
        $this->qsCardInternalKey = $cardId;

        return $this;
    }

    public function getQsCardInternalKey(): int
    {
        return $this->qsCardInternalKey;
    }

    /**
     * @return $this
     */
    public function setCardName(string $cardName): self
    {
        $this->cardName = $cardName;

        return $this;
    }

    public function getCardName(): string
    {
        return $this->cardName;
    }

    /**
     * @return $this
     */
    public function setBonusMilesFull(string $bonusMilesFull): self
    {
        $this->bonusMilesFull = $bonusMilesFull;

        return $this;
    }

    public function getBonusMilesFull(): ?string
    {
        return $this->bonusMilesFull;
    }

    /**
     * @return $this
     */
    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    /**
     * @return $this
     */
    public function setAwCreditCard(?CreditCard $creditCard): self
    {
        $this->awCreditCard = $creditCard;

        return $this;
    }

    public function getAwCreditCard(): ?CreditCard
    {
        return $this->awCreditCard;
    }

    /**
     * @return $this
     */
    public function setIsManual(bool $isManual): self
    {
        $this->isManual = $isManual;

        return $this;
    }

    public function isManual(): bool
    {
        return $this->isManual;
    }

    /**
     * @return $this
     */
    public function setUpdateDate(\DateTime $updateDate): self
    {
        $this->updateDate = $updateDate;

        return $this;
    }

    public function getUpdateDate(): \DateTime
    {
        return $this->updateDate;
    }
}
