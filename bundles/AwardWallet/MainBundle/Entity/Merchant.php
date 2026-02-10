<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="Merchant")
 */
class Merchant
{
    public const STAT_BY_CARD = 'byCard';
    public const STAT_BY_CARD_AND_MULTIPLIER = 'byCardAndMultiplier';

    /**
     * @var int
     * @ORM\Column(name="MerchantID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(name="Name", type="string", length=250, nullable=false)
     */
    protected $name;

    /**
     * @var int
     * @ORM\Column(name="Transactions", type="integer", nullable=false)
     */
    protected $transactions;

    /**
     * @var int
     * @ORM\Column(name="TransactionsLast3Months", type="integer", nullable=false)
     */
    protected $transactionsLast3Months;

    /**
     * @var string
     * @ORM\Column(name="Patterns", type="text", nullable=true)
     */
    protected $patterns;

    /**
     * @var string
     * @ORM\Column(name="ClickURL", type="text", nullable=true)
     */
    protected $clickURL;

    /**
     * @var string
     * @ORM\Column(name="DisplayName", type="text", nullable=true)
     */
    protected $displayName;

    /**
     * @var ShoppingCategoryGroup
     * @ORM\ManyToOne(targetEntity="ShoppingCategoryGroup")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ShoppingCategoryGroupID", referencedColumnName="ShoppingCategoryGroupID")
     * })
     */
    private $shoppingcategorygroup;

    /**
     * @ORM\Column(
     *   name="NotNullGroupID",
     *   type="integer",
     *   insertable=false,
     *   updatable=false,
     *   generated="ALWAYS"
     * )
     */
    private int $notnullgroupid;

    /**
     * @var ShoppingCategory
     * @ORM\ManyToOne(targetEntity="ShoppingCategory")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ShoppingCategoryID", referencedColumnName="ShoppingCategoryID")
     * })
     */
    private $shoppingcategory;

    /**
     * @var ?MerchantPattern
     * @ORM\ManyToOne(targetEntity="MerchantPattern")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="MerchantPatternID", referencedColumnName="MerchantPatternID")
     * })
     */
    private $merchantpattern;

    /**
     * @var ShoppingCategory
     * @ORM\ManyToOne(targetEntity="ShoppingCategory")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ForcedShoppingCategoryID", referencedColumnName="ShoppingCategoryID")
     * })
     */
    private $forcedShoppingCategory;

    /**
     * @ORM\Column(type="json", nullable=true)
     * filled in by AnalyzeMerchantStatCommand
     */
    private ?array $stat;

    public function __toString()
    {
        return $this->name ? $this->name : '';
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function setId(int $id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return string|null
     */
    public function getPatterns()
    {
        return $this->patterns;
    }

    public function getMerchantPattern(): ?MerchantPattern
    {
        return $this->merchantpattern;
    }

    public function setMerchantPattern(?MerchantPattern $merchantPattern): self
    {
        $this->merchantpattern = $merchantPattern;

        return $this;
    }

    public function setPatterns(string $patterns)
    {
        $this->patterns = $patterns;
    }

    public function getShoppingcategory(): ?ShoppingCategory
    {
        return $this->shoppingcategory;
    }

    public function getnotnullgroupid(): int
    {
        return $this->notnullgroupid;
    }

    /**
     * @return $this
     */
    public function setShoppingcategory(ShoppingCategory $shoppingcategory)
    {
        $this->shoppingcategory = $shoppingcategory;

        return $this;
    }

    public function getForcedShoppingCategory(): ?ShoppingCategory
    {
        return $this->forcedShoppingCategory;
    }

    public function setForcedShoppingCategory(?ShoppingCategory $forcedShoppingCategory): self
    {
        $this->forcedShoppingCategory = $forcedShoppingCategory;

        return $this;
    }

    public function getTransactions(): ?int
    {
        return $this->transactions;
    }

    public function setTransactions(int $transactions): void
    {
        $this->transactions = $transactions;
    }

    public function getTransactionsLast3Months(): ?int
    {
        return $this->transactionsLast3Months;
    }

    public function setTransactionsLast3Months(int $transactions): void
    {
        $this->transactionsLast3Months = $transactions;
    }

    public function getClickURL(): ?string
    {
        return $this->clickURL;
    }

    public function setClickURL($clickURL): self
    {
        $this->clickURL = $clickURL;

        return $this;
    }

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function setDisplayName(?string $displayName): self
    {
        $this->displayName = $displayName;

        return $this;
    }

    public function chooseShoppingCategory(): ?ShoppingCategory
    {
        if ($this->forcedShoppingCategory) {
            return $this->forcedShoppingCategory;
        }

        return $this->shoppingcategory;
    }

    public function getStat(): ?array
    {
        return $this->stat;
    }

    public static function getCardAndMultiplierStatKey(int $cardId, float $multiplier): string
    {
        return sprintf("%d_%0.1f", $cardId, $multiplier);
    }
}
