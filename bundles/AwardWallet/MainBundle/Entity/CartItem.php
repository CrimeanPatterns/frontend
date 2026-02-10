<?php

namespace AwardWallet\MainBundle\Entity;

use AwardWallet\MainBundle\Entity\CartItem\AwPlusTrial;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusTrial6Months;
use AwardWallet\MainBundle\Entity\Listener\TranslateArgs;
use AwardWallet\MainBundle\Globals\Cart\AT201SubscriptionInterface;
use AwardWallet\MainBundle\Globals\Cart\AwPlusSubscriptionInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * CartItem.
 *
 * @ORM\Table(name="CartItem")
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\CartItemRepository")
 * @ORM\EntityListeners({ "AwardWallet\MainBundle\Entity\Listener\CartItemListener" })
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="TypeID", type="integer")
 * @ORM\DiscriminatorMap({
 *  "1" = "AwardWallet\MainBundle\Entity\CartItem\AwPlus",
 *  "4" = "AwardWallet\MainBundle\Entity\CartItem\AwPlus1Year",
 *  "3" = "AwardWallet\MainBundle\Entity\CartItem\AwPlus20Year",
 *  "101" = "AwardWallet\MainBundle\Entity\CartItem\AwPlus1Month",
 *  "102" = "AwardWallet\MainBundle\Entity\CartItem\AwPlus2Months",
 *  "103" = "AwardWallet\MainBundle\Entity\CartItem\AwPlus3Months",
 *  "104" = "AwardWallet\MainBundle\Entity\CartItem\AwPlus6Months",
 *  "105" = "AwardWallet\MainBundle\Entity\CartItem\AwPlusTrial6Months",
 *  "10" = "AwardWallet\MainBundle\Entity\CartItem\AwPlusTrial",
 *  "11" = "AwardWallet\MainBundle\Entity\CartItem\AwPlusGift",
 *  "14" = "AwardWallet\MainBundle\Entity\CartItem\AwPlusRecurring",
 *  "16" = "AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription",
 *  "17" = "AwardWallet\MainBundle\Entity\CartItem\AwPlusWeekSubscription",
 *  "18" = "AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription6Months",
 *  "2" = "AwardWallet\MainBundle\Entity\CartItem\Donation",
 *  "5" = "AwardWallet\MainBundle\Entity\CartItem\AwBusiness",
 *  "6" = "AwardWallet\MainBundle\Entity\CartItem\AwBusinessPlus",
 *  "15" = "AwardWallet\MainBundle\Entity\CartItem\AwBusinessCredit",
 *  "7" = "AwardWallet\MainBundle\Entity\CartItem\OneCard",
 *  "8" = "AwardWallet\MainBundle\Entity\CartItem\OneCardShipping",
 *  "9" = "AwardWallet\MainBundle\Entity\CartItem\Booking",
 *  "12" = "AwardWallet\MainBundle\Entity\CartItem\Discount",
 *  "50" = "AwardWallet\MainBundle\Entity\CartItem\BalanceWatchCredit",
 *  "201" = "AwardWallet\MainBundle\Entity\CartItem\AT201Subscription1Month",
 *  "202" = "AwardWallet\MainBundle\Entity\CartItem\AT201Subscription6Months",
 *  "203" = "AwardWallet\MainBundle\Entity\CartItem\AT201Subscription1Year",
 *  "31" = "AwardWallet\MainBundle\Entity\CartItem\AwPlusPrepaid",
 *  "32" = "AwardWallet\MainBundle\Entity\CartItem\AwPlusVIP1YearUpgrade",
 *  "33" = "AwardWallet\MainBundle\Entity\CartItem\Supporters3MonthsUpgrade"
 * })
 */
abstract class CartItem
{
    public const PRICE = 0;

    public const TRIAL_TYPES = [AwPlusTrial::TYPE, AwPlusTrial6Months::TYPE];

    /**
     * @var int
     * @ORM\Column(name="CartItemID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $cartitemid;

    /**
     * @var int
     * @ORM\Column(name="CategoryID", type="integer", nullable=true)
     */
    protected $categoryid;

    /**
     * @var int
     * @ORM\Column(name="ID", type="integer", nullable=true)
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(name="Name", type="string", length=250, nullable=false)
     */
    protected $name;

    /**
     * @var int
     * @ORM\Column(name="Cnt", type="integer", nullable=false)
     */
    protected $cnt = 1;

    /**
     * @var float
     * @ORM\Column(name="Price", type="float", nullable=false)
     */
    protected $price = 0;

    /**
     * @var int
     * @ORM\Column(name="Discount", type="integer", nullable=false)
     */
    protected $discount = 0;

    /**
     * @var int
     * @ORM\Column(name="Operation", type="integer", nullable=true)
     */
    protected $operation;

    /**
     * @var int
     * @ORM\Column(name="UserData", type="integer", nullable=true)
     */
    protected $userdata;

    /**
     * @var string
     * @ORM\Column(name="Description", type="text", nullable=false)
     */
    protected $description = '';

    /**
     * @var int
     * @ORM\Column(name="ColorID", type="integer", nullable=true)
     */
    protected $colorid;

    /**
     * @var Cart
     * @ORM\ManyToOne(targetEntity="Cart", inversedBy="items")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="CartID", referencedColumnName="CartID")
     * })
     */
    protected $cart;

    /**
     * @var \DateTime
     * @ORM\Column(name="ScheduledDate", type="date", nullable=true)
     */
    protected $scheduledDate;

    public function __toString()
    {
        return preg_replace("/<br[^>]*>\s*\([^\)]+\)/ims", "", $this->getName());
    }

    /**
     * Get cartitemid.
     *
     * @return int
     */
    public function getCartitemid()
    {
        return $this->cartitemid;
    }

    /**
     * Set categoryid.
     *
     * @param int $categoryid
     * @return CartItem
     */
    public function setCategoryid($categoryid)
    {
        $this->categoryid = $categoryid;

        return $this;
    }

    /**
     * Get categoryid.
     *
     * @return int
     */
    public function getCategoryid()
    {
        return $this->categoryid;
    }

    /**
     * Set id.
     *
     * @param int $id
     * @return CartItem
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

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
     * Set name.
     *
     * @param string $name
     * @return CartItem
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set cnt.
     *
     * @param int $cnt
     * @return CartItem
     */
    public function setCnt($cnt)
    {
        $this->cnt = $cnt;

        return $this;
    }

    /**
     * Get cnt.
     *
     * @return int
     */
    public function getCnt()
    {
        return $this->cnt;
    }

    /**
     * Set price.
     *
     * @param float $price
     * @return CartItem
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get price.
     *
     * @return float
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set discount.
     *
     * @param int $discount
     * @return CartItem
     */
    public function setDiscount($discount)
    {
        $this->discount = $discount;

        return $this;
    }

    /**
     * Get discount.
     *
     * @return int
     */
    public function getDiscount()
    {
        return $this->discount;
    }

    /**
     * Set operation.
     *
     * @param int $operation
     * @return CartItem
     */
    public function setOperation($operation)
    {
        $this->operation = $operation;

        return $this;
    }

    /**
     * Get operation.
     *
     * @return int
     */
    public function getOperation()
    {
        return $this->operation;
    }

    /**
     * Set userdata.
     *
     * @param int $userdata
     * @return CartItem
     */
    public function setUserdata($userdata)
    {
        $this->userdata = $userdata;

        return $this;
    }

    /**
     * Get userdata.
     *
     * @return int
     */
    public function getUserdata()
    {
        return $this->userdata;
    }

    /**
     * Set description.
     *
     * @param string $description
     * @return CartItem
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
     * Set colorid.
     *
     * @param int $colorid
     * @return CartItem
     */
    public function setColorid($colorid)
    {
        $this->colorid = $colorid;

        return $this;
    }

    /**
     * Get colorid.
     *
     * @return int
     */
    public function getColorid()
    {
        return $this->colorid;
    }

    /**
     * Set cart.
     *
     * @return CartItem
     */
    public function setCart(?Cart $cart = null)
    {
        $this->cart = $cart;

        return $this;
    }

    /**
     * Get cart.
     *
     * @return Cart
     */
    public function getCart()
    {
        return $this->cart;
    }

    /**
     * @return CartItem
     */
    public function setScheduledDate(?\DateTime $dateTime = null)
    {
        $this->scheduledDate = $dateTime;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getScheduledDate()
    {
        return $this->scheduledDate;
    }

    public function getTotalPrice()
    {
        return round($this->getPrice() * $this->getCnt() * ((100 - $this->discount) / 100), 2);
    }

    public function getTotalPriceWithDiscount()
    {
        return round($this->getTotalPrice() - $this->getDiscountAmount(), 2);
    }

    public function getDiscountAmount(?Coupon $coupon = null)
    {
        $coupon = ($coupon) ? $coupon : $this->getCart()->getCoupon();

        if (!$coupon) {
            return 0;
        }

        return round($this->getTotalPrice() * ($coupon->getDiscount() / 100), 2);
    }

    public function getQuantity()
    {
        return $this->getDescription();
    }

    public function isAwPlusSubscription()
    {
        return $this instanceof AwPlusSubscriptionInterface;
    }

    public function isSubscription()
    {
        return $this instanceof AwPlusSubscriptionInterface || $this instanceof AT201SubscriptionInterface;
    }

    public function translate(TranslateArgs $args)
    {
    }

    /**
     * Used to display the cart in the template.
     *
     * @return bool
     */
    public function isCountable()
    {
        return false;
    }

    public function isVisibleInCart(): bool
    {
        return true;
    }

    public function isScheduled(): bool
    {
        return $this->getScheduledDate() !== null;
    }
}
