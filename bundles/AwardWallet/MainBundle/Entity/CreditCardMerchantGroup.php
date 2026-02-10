<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CreditCardMerchantGroup.
 *
 * @ORM\Entity
 * @ORM\Table(name="CreditCardMerchantGroup")
 */
class CreditCardMerchantGroup
{
    /**
     * @var string
     * @ORM\Column(name="Description", type="text", nullable=true)
     */
    protected $description;

    /**
     * @var int
     * @ORM\Column(name="SortIndex", type="integer", nullable=false)
     */
    protected $sortIndex;
    /**
     * @var int
     * @ORM\Column(name="CreditCardMerchantGroupID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var CreditCard
     * @ORM\ManyToOne(targetEntity="CreditCard")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="CreditCardID", referencedColumnName="CreditCardID")
     * })
     */
    private $creditCard;

    /**
     * @var MerchantGroup
     * @ORM\ManyToOne(targetEntity="MerchantGroup")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="MerchantGroupID", referencedColumnName="MerchantGroupID")
     * })
     */
    private $merchantGroup;

    /**
     * @var float
     * @ORM\Column(name="Multiplier", type="float")
     */
    private $multiplier;

    /**
     * @var \DateTime
     * @ORM\Column(name="StartDate", type="date", nullable=true)
     */
    private $startDate;

    /**
     * @var \DateTime
     * @ORM\Column(name="EndDate", type="date", nullable=true)
     */
    private $endDate;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setCreditCard(CreditCard $creditCard): self
    {
        $this->creditCard = $creditCard;

        return $this;
    }

    public function getCreditCard(): ?CreditCard
    {
        return $this->creditCard;
    }

    public function setMerchantGroup(MerchantGroup $merchantGroup): self
    {
        $this->merchantGroup = $merchantGroup;

        return $this;
    }

    public function getMerchantGroup(): ?MerchantGroup
    {
        return $this->merchantGroup;
    }

    public function setMultiplier(float $multiplier): self
    {
        $this->multiplier = $multiplier;

        return $this;
    }

    public function getMultiplier(): ?float
    {
        return $this->multiplier;
    }

    public function setStartDate(?\DateTime $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getStartDate(): ?\DateTime
    {
        return $this->startDate;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getSortIndex(): ?int
    {
        return $this->sortIndex;
    }

    public function setSortIndex(int $sortIndex): self
    {
        $this->sortIndex = $sortIndex;

        return $this;
    }
}
