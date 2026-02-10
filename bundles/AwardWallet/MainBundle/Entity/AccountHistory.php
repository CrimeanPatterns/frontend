<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AccountHistory.
 *
 * @ORM\Table(name="AccountHistory")
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Repository\AccounthistoryRepository")
 */
class AccountHistory
{
    /**
     * @var Account
     * @ORM\ManyToOne(targetEntity="Account", inversedBy="history")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="AccountID", referencedColumnName="AccountID")
     * })
     */
    private $account;

    /**
     * @var Subaccount
     * @ORM\ManyToOne(targetEntity="Subaccount")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="SubAccountID", referencedColumnName="SubAccountID")
     * })
     */
    private $subaccount;

    /**
     * @var Merchant
     * @ORM\ManyToOne(targetEntity="Merchant")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="MerchantID", referencedColumnName="MerchantID")
     * })
     */
    private $merchant;

    /**
     * @var ShoppingCategory
     * @ORM\ManyToOne(targetEntity="ShoppingCategory")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ShoppingCategoryID", referencedColumnName="ShoppingCategoryID")
     * })
     */
    private $shoppingcategory;

    /**
     * @var Currency
     * @ORM\ManyToOne(targetEntity="Currency")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="CurrencyID", referencedColumnName="CurrencyID")
     * })
     */
    private $currency;

    /**
     * @var \DateTime
     * @ORM\Column(name="PostingDate", type="datetime", nullable=false)
     */
    private $postingdate;

    /**
     * @var string
     * @ORM\Column(name="Description", type="string", length=4000)
     */
    private $description;

    /**
     * @var string
     * @ORM\Column(name="Category", type="string", length=4000)
     */
    private $category;

    /**
     * @var float
     * @ORM\Column(name="Miles", type="float")
     */
    private $miles;

    /**
     * @ORM\Column(name="Info", type="array")
     */
    private $info;

    /**
     * @ORM\Column(name="Note", type="string", length=1000)
     */
    private $note;

    /**
     * @var int
     * @ORM\Column(name="Position", type="integer")
     */
    private $position;

    /**
     * @var string
     * @ORM\Column(name="UUID", type="guid", nullable=true)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $uuid;

    /**
     * @var bool
     * @ORM\Column(name="Custom", type="smallint", nullable=true)
     */
    private $custom;

    /**
     * @var float
     * @ORM\Column(name="Multiplier", type="decimal", nullable=true)
     */
    private $multiplier;

    /**
     * @var float
     * @ORM\Column(name="Amount", type="decimal", nullable=true)
     */
    private $amount;

    /**
     * @var float
     * @ORM\Column(name="AmountBalance", type="decimal", nullable=true)
     */
    private $amountbalance;

    /**
     * @var float
     * @ORM\Column(name="MilesBalance", type="decimal", nullable=true)
     */
    private $milesbalance;

    /**
     * Set account.
     *
     * @param Account $account
     * @return AccountHistory
     */
    public function setAccount($account)
    {
        $this->account = $account;

        return $this;
    }

    /**
     * Get account.
     *
     * @return Account
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * Set postingdate.
     *
     * @param \DateTime $postingdate
     * @return AccountHistory
     */
    public function setPostingdate($postingdate)
    {
        $this->postingdate = $postingdate;

        return $this;
    }

    /**
     * Get postingdate.
     *
     * @return \DateTime
     */
    public function getPostingdate()
    {
        return $this->postingdate;
    }

    /**
     * Set description.
     *
     * @param string $description
     * @return AccountHistory
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set miles.
     *
     * @param float $miles
     * @return AccountHistory
     */
    public function setMiles($miles)
    {
        $this->miles = $miles;

        return $this;
    }

    /**
     * Get miles.
     *
     * @return float
     */
    public function getMiles()
    {
        return $this->miles;
    }

    /**
     * Set info.
     *
     * @param string $info
     * @return AccountHistory
     */
    public function setInfo($info)
    {
        $this->info = $info;

        return $this;
    }

    /**
     * Get info.
     *
     * @return string
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * Set position.
     *
     * @param int $position
     * @return AccountHistory
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Get position.
     *
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @return string
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * @return bool
     */
    public function isCustom()
    {
        return $this->custom;
    }

    /**
     * @param bool $custom
     */
    public function setCustom($custom)
    {
        $this->custom = $custom;
    }

    /**
     * @return string
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * @param string $note
     */
    public function setNote($note)
    {
        $this->note = $note;
    }

    /**
     * @return Merchant
     */
    public function getMerchant()
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

    /**
     * @return Subaccount
     */
    public function getSubaccount()
    {
        return $this->subaccount;
    }

    /**
     * @param Subaccount $subaccount
     */
    public function setSubaccount($subaccount)
    {
        $this->subaccount = $subaccount;
    }

    /**
     * @return ShoppingCategory
     */
    public function getShoppingcategory()
    {
        return $this->shoppingcategory;
    }

    /**
     * @return $this
     */
    public function setShoppingcategory(ShoppingCategory $shoppingcategory)
    {
        $this->shoppingcategory = $shoppingcategory;

        return $this;
    }

    /**
     * @return float
     */
    public function getMultiplier()
    {
        return $this->multiplier;
    }

    /**
     * @return $this
     */
    public function setMultiplier(float $multiplier)
    {
        $this->multiplier = $multiplier;

        return $this;
    }

    /**
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @return $this
     */
    public function setAmount(float $amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * @return Currency
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @return $this
     */
    public function setCurrency(Currency $currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * @return float
     */
    public function getAmountbalance()
    {
        return $this->amountbalance;
    }

    /**
     * @return $this
     */
    public function setAmountbalance(float $amountbalance)
    {
        $this->amountbalance = $amountbalance;

        return $this;
    }

    /**
     * @return float
     */
    public function getMilesbalance()
    {
        return $this->milesbalance;
    }

    /**
     * @return $this
     */
    public function setMilesbalance(float $milesbalance)
    {
        $this->milesbalance = $milesbalance;

        return $this;
    }

    public function getParsedInfo()
    {
        $result = $this->info;

        if (isset($result['Transaction Description'])) {
            unset($result['Transaction Description']);
        }

        return $result;
    }

    public function getTransactionDescription()
    {
        $data = $this->info['Transaction Description'] ?? null;

        if (empty($data)) {
            return null;
        }

        $data = json_decode(htmlspecialchars_decode($data), true);

        if (!is_array($data) || count($data) < 1) {
            return null;
        }

        return $data;
    }

    public function getTransactionDescriptionAdmin()
    {
        $data = $this->getTransactionDescription();

        if (empty($data)) {
            return null;
        }

        $result = [];

        foreach ($data as $trItem) {
            if (isset($trItem['earnedTransactionDescription'])) {
                $result[] = $trItem['earnedTransactionDescription'];
            }
        }

        $result[] = "<br />" . json_encode($data);

        return implode("<br/>", $result);
    }

    /**
     * @return string
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @return $this
     */
    public function setCategory(string $category)
    {
        $this->category = $category;

        return $this;
    }
}
