<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CreditCardShoppingCategoryGroup.
 *
 * @ORM\Entity
 * @ORM\Table(name="CreditCardShoppingCategoryGroup")
 */
class CreditCardShoppingCategoryGroup
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
     * @ORM\Column(name="CreditCardShoppingCategoryGroupID", type="integer", nullable=false)
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
     * @var ShoppingCategoryGroup
     * @ORM\ManyToOne(targetEntity="ShoppingCategoryGroup")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ShoppingCategoryGroupID", referencedColumnName="ShoppingCategoryGroupID")
     * })
     */
    private $shoppingCategoryGroup;

    /**
     * @var float
     * @ORM\Column(name="Multiplier", type="decimal", precision=4, scale=2)
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

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set creditCard.
     *
     * @param CreditCard $creditCard
     * @return CreditCardShoppingCategoryGroup
     */
    public function setCreditCard($creditCard)
    {
        $this->creditCard = $creditCard;

        return $this;
    }

    /**
     * Get creditCard.
     *
     * @return CreditCard
     */
    public function getCreditCard()
    {
        return $this->creditCard;
    }

    /**
     * Set shoppingCategoryGroup.
     *
     * @param ShoppingCategoryGroup $shoppingCategoryGroup
     * @return CreditCardShoppingCategoryGroup
     */
    public function setShoppingCategoryGroup($shoppingCategoryGroup)
    {
        $this->shoppingCategoryGroup = $shoppingCategoryGroup;

        return $this;
    }

    /**
     * Get shoppingCategoryGroup.
     *
     * @return ShoppingCategoryGroup
     */
    public function getShoppingCategoryGroup()
    {
        return $this->shoppingCategoryGroup;
    }

    /**
     * Set multiplier.
     *
     * @param float $multiplier
     * @return CreditCardShoppingCategoryGroup
     */
    public function setMultiplier($multiplier)
    {
        $this->multiplier = $multiplier;

        return $this;
    }

    /**
     * Get multiplier.
     *
     * @return float
     */
    public function getMultiplier()
    {
        return $this->multiplier;
    }

    /**
     * Set startDate.
     *
     * @param \DateTime $startDate
     * @return CreditCardShoppingCategoryGroup
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Get startDate.
     *
     * @return \DateTime
     */
    public function getStartDate()
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

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    public function getSortIndex(): ?int
    {
        return $this->sortIndex;
    }

    /**
     * @return $this
     */
    public function setSortIndex(int $sortIndex)
    {
        $this->sortIndex = $sortIndex;

        return $this;
    }
}
