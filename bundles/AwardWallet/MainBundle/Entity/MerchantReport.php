<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * MerchantReport.
 *
 * @ORM\Table(name="MerchantReport")
 * @ORM\Entity
 */
class MerchantReport
{
    /**
     * @var int
     * @ORM\Column(name="Version", type="text", nullable=false)
     */
    protected $version;

    /**
     * @var int
     * @ORM\Column(name="Transactions", type="text", nullable=false)
     */
    protected $transactions;

    /**
     * @var int
     * @ORM\Column(name="ExpectedMultiplierTransactions", type="text", nullable=false)
     */
    protected $expectedMultiplierTransactions;

    /**
     * @var Merchant
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Merchant")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="MerchantID", referencedColumnName="MerchantID")
     * })
     */
    private $merchant;

    /**
     * @var CreditCard
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="CreditCard")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="CreditCardID", referencedColumnName="CreditCardID")
     * })
     */
    private $creditCard;

    /**
     * @var ShoppingCategory
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="ShoppingCategory")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ShoppingCategoryID", referencedColumnName="ShoppingCategoryID")
     * })
     */
    private $shoppingCategory;

    public function getMerchant(): ?Merchant
    {
        return $this->merchant;
    }

    /**
     * @return $this
     */
    public function setMerchant(Merchant $merchant)
    {
        $this->merchant = $merchant;

        return $this;
    }

    public function getCreditCard(): ?CreditCard
    {
        return $this->creditCard;
    }

    /**
     * @return $this
     */
    public function setCreditCard(CreditCard $creditCard)
    {
        $this->creditCard = $creditCard;

        return $this;
    }

    public function getShoppingCategory(): ?ShoppingCategory
    {
        return $this->shoppingCategory;
    }

    /**
     * @return $this
     */
    public function setShoppingCategory(ShoppingCategory $shoppingCategory)
    {
        $this->shoppingCategory = $shoppingCategory;

        return $this;
    }

    public function getTransactions(): ?int
    {
        return $this->transactions;
    }

    /**
     * @return $this
     */
    public function setTransactions(int $transactions)
    {
        $this->transactions = $transactions;

        return $this;
    }

    public function getVersion(): ?int
    {
        return $this->version;
    }

    /**
     * @return $this
     */
    public function setVersion(int $version)
    {
        $this->version = $version;

        return $this;
    }

    public function getExpectedMultiplierTransactions(): ?int
    {
        return $this->expectedMultiplierTransactions;
    }

    /**
     * @return $this
     */
    public function setExpectedMultiplierTransactions(int $expectedMultiplierTransactions)
    {
        $this->expectedMultiplierTransactions = $expectedMultiplierTransactions;

        return $this;
    }
}
