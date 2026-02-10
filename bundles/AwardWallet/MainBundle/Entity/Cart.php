<?php

namespace AwardWallet\MainBundle\Entity;

use AwardWallet\MainBundle\Entity\CartItem\AwBusinessCredit;
use AwardWallet\MainBundle\Entity\CartItem\AwPlus;
use AwardWallet\MainBundle\Entity\CartItem\AwPlus1Year;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusGift;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusPrepaid;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusRecurring;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription;
use AwardWallet\MainBundle\Entity\CartItem\BalanceWatchCredit;
use AwardWallet\MainBundle\Entity\CartItem\Booking;
use AwardWallet\MainBundle\Entity\CartItem\Discount;
use AwardWallet\MainBundle\Entity\CartItem\OneCard as OneCardItem;
use AwardWallet\MainBundle\Globals\Cart\AT201SubscriptionInterface;
use AwardWallet\MainBundle\Globals\Cart\AwPlusSubscriptionInterface;
use AwardWallet\MainBundle\Globals\Cart\AwPlusUpgradableInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;

/**
 * Cart.
 *
 * @ORM\Table(name="Cart")
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\CartRepository")
 * @ORM\EntityListeners({ "AwardWallet\MainBundle\Entity\Listener\CartListener" })
 * @ORM\HasLifecycleCallbacks()
 */
class Cart implements TranslationContainerInterface
{
    public const PAYMENTTYPE_CREDITCARD = 1;
    public const PAYMENTTYPE_CHECKBYINTERNET = 2;
    public const PAYMENTTYPE_MAILINCHECK = 3;
    public const PAYMENTTYPE_TEST = 4;
    public const PAYMENTTYPE_PAYPAL = 5;
    public const PAYMENTTYPE_TEST_PAYPAL = 6;
    public const PAYMENTTYPE_TEST_CREDITCARD = 7;
    public const PAYMENTTYPE_APPSTORE = 8;
    public const PAYMENTTYPE_ANDROIDMARKET = 9;
    public const PAYMENTTYPE_BITCOIN = 10;
    public const PAYMENTTYPE_RECURLY = 11;
    public const PAYMENTTYPE_BUSINESS_BALANCE = 12;
    public const PAYMENTTYPE_ETHEREUM = 13;
    public const PAYMENTTYPE_STRIPE = 14;
    public const PAYMENTTYPE_QSTRANSCATION = 20;
    public const PAYMENTTYPE_STRIPE_INTENT = 21;

    public const APPSTORE_FEES_PERCENT = 15;
    public const ANDROIDMARKET_FEES_PERCENT = 15;

    public const SOURCE_USER = 0;
    public const SOURCE_RECURRING = 1;

    public const PAYMENT_TYPE_NAMES_PREFIX = 'cart.payment_type_name_';
    public const PAYMENT_TYPES = [
        self::PAYMENTTYPE_CREDITCARD => 'Credit Card',
        self::PAYMENTTYPE_STRIPE => 'Stripe (old)',
        self::PAYMENTTYPE_STRIPE_INTENT => 'Credit Card',
        self::PAYMENTTYPE_CHECKBYINTERNET => 'Check by internet',
        self::PAYMENTTYPE_MAILINCHECK => 'Mail in check',
        self::PAYMENTTYPE_TEST => 'Test',
        self::PAYMENTTYPE_PAYPAL => 'PayPal',
        self::PAYMENTTYPE_TEST_PAYPAL => 'Test PayPal',
        self::PAYMENTTYPE_TEST_CREDITCARD => 'Test Credit Card',
        self::PAYMENTTYPE_APPSTORE => 'App Store (iOS)',
        self::PAYMENTTYPE_ANDROIDMARKET => 'Google Play',
        self::PAYMENTTYPE_BITCOIN => 'Bitcoin',
        self::PAYMENTTYPE_ETHEREUM => 'Ethereum',
        self::PAYMENTTYPE_RECURLY => 'Credit Card',
        self::PAYMENTTYPE_BUSINESS_BALANCE => 'Business balance',
        self::PAYMENTTYPE_QSTRANSCATION => 'QsTransaction',
    ];

    /**
     * @var int
     * @ORM\Column(name="CartID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $cartid;

    /**
     * @var string
     * @ORM\Column(name="Code", type="string", length=20, nullable=true)
     */
    protected $code;

    /**
     * @var int
     * @ORM\Column(name="PaymentType", type="integer", nullable=true)
     */
    protected $paymenttype;

    /**
     * @var \DateTime
     * @ORM\Column(name="LastUsedDate", type="datetime", nullable=false)
     */
    protected $lastuseddate;

    /**
     * @var string
     * @ORM\Column(name="BillingTransactionID", type="string", length=40, nullable=true)
     */
    protected $billingtransactionid;

    /**
     * @var \DateTime
     * @ORM\Column(name="PayDate", type="datetime", nullable=true)
     */
    protected $paydate;

    /**
     * @var \DateTime
     * @ORM\Column(name="CalcDate", type="datetime", nullable=true)
     */
    protected $calcDate;

    /**
     * @var string
     * @ORM\Column(name="FirstName", type="string", length=40, nullable=true)
     */
    protected $firstname;

    /**
     * @var string
     * @ORM\Column(name="LastName", type="string", length=40, nullable=true)
     */
    protected $lastname;

    /**
     * @var string
     * @ORM\Column(name="Email", type="string", length=60, nullable=true)
     */
    protected $email;

    /**
     * @var string
     * @ORM\Column(name="CouponName", type="string", length=80, nullable=true)
     */
    protected $couponname;

    /**
     * @var string
     * @ORM\Column(name="CouponCode", type="string", length=80, nullable=true)
     */
    protected $couponcode;

    /**
     * @var \DateTime
     * @ORM\Column(name="UploadDate", type="datetime", nullable=true)
     */
    protected $uploaddate;

    /**
     * @var bool
     * @ORM\Column(name="Processed", type="boolean", nullable=false)
     */
    protected $processed = false;

    /**
     * @var string
     * @ORM\Column(name="Comments", type="string", length=250, nullable=true)
     */
    protected $comments;

    /**
     * @var string
     * @ORM\Column(name="ShipFirstName", type="string", length=40, nullable=true)
     */
    protected $shipfirstname;

    /**
     * @var string
     * @ORM\Column(name="ShipLastName", type="string", length=40, nullable=true)
     */
    protected $shiplastname;

    /**
     * @var string
     * @ORM\Column(name="ShipAddress1", type="string", length=250, nullable=true)
     */
    protected $shipaddress1;

    /**
     * @var string
     * @ORM\Column(name="ShipAddress2", type="string", length=250, nullable=true)
     */
    protected $shipaddress2;

    /**
     * @var string
     * @ORM\Column(name="ShipCity", type="string", length=80, nullable=true)
     */
    protected $shipcity;

    /**
     * @var string
     * @ORM\Column(name="ShipZip", type="string", length=40, nullable=true)
     */
    protected $shipzip;

    /**
     * @var string
     * @ORM\Column(name="ShippingZip", type="string", length=20, nullable=true)
     */
    protected $shippingzip;

    /**
     * @var string
     * @ORM\Column(name="Error", type="string", length=250, nullable=true)
     */
    protected $error;

    /**
     * @var string
     * @ORM\Column(name="ShippingDetails", type="text", nullable=true)
     */
    protected $shippingdetails;

    /**
     * @var string
     * @ORM\Column(name="BillFirstName", type="string", length=40, nullable=true)
     */
    protected $billfirstname;

    /**
     * @var string
     * @ORM\Column(name="BillLastName", type="string", length=40, nullable=true)
     */
    protected $billlastname;

    /**
     * @var string
     * @ORM\Column(name="BillAddress1", type="string", length=250, nullable=true)
     */
    protected $billaddress1;

    /**
     * @var string
     * @ORM\Column(name="BillAddress2", type="string", length=250, nullable=true)
     */
    protected $billaddress2;

    /**
     * @var string
     * @ORM\Column(name="BillCity", type="string", length=80, nullable=true)
     */
    protected $billcity;

    /**
     * @var string
     * @ORM\Column(name="BillZip", type="string", length=40, nullable=true)
     */
    protected $billzip;

    /**
     * @var int
     * @ORM\Column(name="CameFrom", type="integer", nullable=true)
     */
    protected $camefrom;

    /**
     * @var string
     * @ORM\Column(name="CreditCardType", type="string", length=80, nullable=true)
     */
    protected $creditcardtype;

    /**
     * @var string
     * @ORM\Column(name="CreditCardNumber", type="string", length=80, nullable=true)
     */
    protected $creditcardnumber;

    /**
     * @var bool
     * @ORM\Column(name="PayPalConfirmed", type="boolean", nullable=false)
     */
    protected $paypalconfirmed = false;

    /**
     * @var string
     * @ORM\Column(name="Crypted", type="string", length=4000, nullable=true)
     */
    protected $crypted;

    /**
     * @var int
     * @ORM\Column(name="IncomeTransactionID", type="integer", nullable=true)
     */
    protected $incometransactionid;

    /**
     * @var int
     * @ORM\Column(name="AppleTransactionID", type="bigint", nullable=true)
     */
    protected $appleTransactionID;

    /**
     * @var string
     * @ORM\Column(name="PurchaseToken", type="string", nullable=true)
     */
    protected $purchaseToken;

    /**
     * @var Country
     * @ORM\ManyToOne(targetEntity="Country")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="BillCountryID", referencedColumnName="CountryID")
     * })
     */
    protected $billcountry;

    /**
     * @var State
     * @ORM\ManyToOne(targetEntity="State")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="BillStateID", referencedColumnName="StateID")
     * })
     */
    protected $billstate;

    /**
     * @var Coupon
     * @ORM\ManyToOne(targetEntity="Coupon", inversedBy="carts", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="CouponID", referencedColumnName="CouponID")
     * })
     */
    protected $coupon;

    /**
     * @var Country
     * @ORM\ManyToOne(targetEntity="Country")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ShipCountryID", referencedColumnName="CountryID")
     * })
     */
    protected $shipcountry;

    /**
     * @var State
     * @ORM\ManyToOne(targetEntity="State")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ShipStateID", referencedColumnName="StateID")
     * })
     */
    protected $shipstate;

    /**
     * @var Usr
     * @ORM\ManyToOne(targetEntity="Usr", inversedBy="carts", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserID", referencedColumnName="UserID")
     * })
     */
    protected $user;

    /**
     * @var CartItem[]
     * @ORM\OneToMany(targetEntity="CartItem", mappedBy="cart", cascade={"persist", "remove"}, orphanRemoval=true)
     * @ORM\OrderBy({"cartitemid" = "ASC"})
     */
    protected $items;

    /**
     * @var int
     * @ORM\Column(name="Source", type="integer", nullable=false)
     */
    protected $source = self::SOURCE_USER;

    /**
     * @var string
     * @ORM\Column(name="CartAttrHash", type="string", nullable=true)
     */
    protected $cartAttrHash;

    public function __construct()
    {
        $this->items = new \Doctrine\Common\Collections\ArrayCollection();
        $this->setLastuseddate(new \DateTime());
    }

    public function __toString()
    {
        $items = $this->getItems();

        if ($this->hasItemsByType([Booking::TYPE])) {
            return strval($items->first()) . ", Order #" . $this->getCartid();
        }

        $havePrepaidItem = $this->hasPrepaidAwPlusSubscription();
        $r = [];

        foreach ($items as $item) {
            if ($havePrepaidItem && $item instanceof AwPlusSubscription) {
                continue;
            }

            if (!in_array($item::TYPE, [AwPlusRecurring::TYPE, Discount::TYPE])) {
                $name = (string) $item;

                if ($item::TYPE == OneCardItem::TYPE) {
                    $name = $name . ": " . $item->getQuantity();
                }
                $r[] = $name;
            }
        }

        if ($this->hasItemsByType([AwPlusRecurring::TYPE])) {
            $r[] = strval($this->getItemsByType([AwPlusRecurring::TYPE])->first());
        }
        $list = !sizeof($r) ? "" : implode(", ", $r) . ", ";

        return $list . "Order #" . $this->getCartid();
    }

    /**
     * Get cartid.
     *
     * @return int
     */
    public function getCartid()
    {
        return $this->cartid;
    }

    /**
     * Set code.
     *
     * @param string $code
     * @return Cart
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code.
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set paymenttype.
     *
     * @param ?int $paymenttype
     * @return Cart
     */
    public function setPaymenttype($paymenttype)
    {
        $this->paymenttype = $paymenttype;

        return $this;
    }

    /**
     * Get paymenttype.
     *
     * @return int
     */
    public function getPaymenttype()
    {
        return $this->paymenttype;
    }

    /**
     * Set lastuseddate.
     *
     * @param \DateTime $lastuseddate
     * @return Cart
     */
    public function setLastuseddate($lastuseddate)
    {
        $this->lastuseddate = $lastuseddate;

        return $this;
    }

    /**
     * Get lastuseddate.
     *
     * @return \DateTime
     */
    public function getLastuseddate()
    {
        return $this->lastuseddate;
    }

    /**
     * Set billingtransactionid.
     *
     * @param string $billingtransactionid
     * @return Cart
     */
    public function setBillingtransactionid($billingtransactionid)
    {
        $this->billingtransactionid = $billingtransactionid;

        return $this;
    }

    /**
     * Get billingtransactionid.
     *
     * @return string
     */
    public function getBillingtransactionid()
    {
        return $this->billingtransactionid;
    }

    /**
     * Set paydate.
     *
     * @param \DateTime $paydate
     * @return Cart
     */
    public function setPaydate($paydate = null)
    {
        $this->paydate = $paydate;

        if (!is_null($paydate)) {
            $this->archive();
        }

        return $this;
    }

    /**
     * Get paydate.
     *
     * @return \DateTime
     */
    public function getPaydate()
    {
        return $this->paydate;
    }

    /**
     * @param \DateTime $val
     * @return Cart
     */
    public function setCalcDate($val = null)
    {
        $this->calcDate = $val;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCalcDate()
    {
        return $this->calcDate;
    }

    /**
     * Set firstname.
     *
     * @param string $firstname
     * @return Cart
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;

        return $this;
    }

    /**
     * Get firstname.
     *
     * @return string
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * Set lastname.
     *
     * @param string $lastname
     * @return Cart
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;

        return $this;
    }

    /**
     * Get lastname.
     *
     * @return string
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * Set email.
     *
     * @param string $email
     * @return Cart
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set couponname.
     *
     * @param string $couponname
     * @return Cart
     */
    public function setCouponname($couponname)
    {
        $this->couponname = $couponname;

        return $this;
    }

    /**
     * Get couponname.
     *
     * @return string
     */
    public function getCouponname()
    {
        return $this->couponname;
    }

    /**
     * Set couponcode.
     *
     * @param string $couponcode
     * @return Cart
     */
    public function setCouponcode($couponcode)
    {
        $this->couponcode = $couponcode;

        return $this;
    }

    /**
     * Get couponcode.
     *
     * @return string
     */
    public function getCouponcode()
    {
        return $this->couponcode;
    }

    /**
     * Set uploaddate.
     *
     * @param \DateTime $uploaddate
     * @return Cart
     */
    public function setUploaddate($uploaddate)
    {
        $this->uploaddate = $uploaddate;

        return $this;
    }

    /**
     * Get uploaddate.
     *
     * @return \DateTime
     */
    public function getUploaddate()
    {
        return $this->uploaddate;
    }

    /**
     * Set processed.
     *
     * @param bool $processed
     * @return Cart
     */
    public function setProcessed($processed)
    {
        $this->processed = $processed;

        return $this;
    }

    /**
     * Get processed.
     *
     * @return bool
     */
    public function getProcessed()
    {
        return $this->processed;
    }

    /**
     * Set comments.
     *
     * @param string $comments
     * @return Cart
     */
    public function setComments($comments)
    {
        $this->comments = $comments;

        return $this;
    }

    /**
     * Get comments.
     *
     * @return string
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * Set shipfirstname.
     *
     * @param string $shipfirstname
     * @return Cart
     */
    public function setShipfirstname($shipfirstname)
    {
        $this->shipfirstname = $shipfirstname;

        return $this;
    }

    /**
     * Get shipfirstname.
     *
     * @return string
     */
    public function getShipfirstname()
    {
        return $this->shipfirstname;
    }

    /**
     * Set shiplastname.
     *
     * @param string $shiplastname
     * @return Cart
     */
    public function setShiplastname($shiplastname)
    {
        $this->shiplastname = $shiplastname;

        return $this;
    }

    /**
     * Get shiplastname.
     *
     * @return string
     */
    public function getShiplastname()
    {
        return $this->shiplastname;
    }

    /**
     * Set shipaddress1.
     *
     * @param string $shipaddress1
     * @return Cart
     */
    public function setShipaddress1($shipaddress1)
    {
        $this->shipaddress1 = $shipaddress1;

        return $this;
    }

    /**
     * Get shipaddress1.
     *
     * @return string
     */
    public function getShipaddress1()
    {
        return $this->shipaddress1;
    }

    /**
     * Set shipaddress2.
     *
     * @param string $shipaddress2
     * @return Cart
     */
    public function setShipaddress2($shipaddress2)
    {
        $this->shipaddress2 = $shipaddress2;

        return $this;
    }

    /**
     * Get shipaddress2.
     *
     * @return string
     */
    public function getShipaddress2()
    {
        return $this->shipaddress2;
    }

    /**
     * Set shipcity.
     *
     * @param string $shipcity
     * @return Cart
     */
    public function setShipcity($shipcity)
    {
        $this->shipcity = $shipcity;

        return $this;
    }

    /**
     * Get shipcity.
     *
     * @return string
     */
    public function getShipcity()
    {
        return $this->shipcity;
    }

    /**
     * Set shipzip.
     *
     * @param string $shipzip
     * @return Cart
     */
    public function setShipzip($shipzip)
    {
        $this->shipzip = $shipzip;

        return $this;
    }

    /**
     * Get shipzip.
     *
     * @return string
     */
    public function getShipzip()
    {
        return $this->shipzip;
    }

    /**
     * Set shippingzip.
     *
     * @param string $shippingzip
     * @return Cart
     */
    public function setShippingzip($shippingzip)
    {
        $this->shippingzip = $shippingzip;

        return $this;
    }

    /**
     * Get shippingzip.
     *
     * @return string
     */
    public function getShippingzip()
    {
        return $this->shippingzip;
    }

    /**
     * Set error.
     *
     * @param string $error
     * @return Cart
     */
    public function setError($error)
    {
        $this->error = $error;

        return $this;
    }

    /**
     * Get error.
     *
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Set shippingdetails.
     *
     * @param string $shippingdetails
     * @return Cart
     */
    public function setShippingdetails($shippingdetails)
    {
        $this->shippingdetails = $shippingdetails;

        return $this;
    }

    /**
     * Get shippingdetails.
     *
     * @return string
     */
    public function getShippingdetails()
    {
        return $this->shippingdetails;
    }

    /**
     * Set billfirstname.
     *
     * @param string $billfirstname
     * @return Cart
     */
    public function setBillfirstname($billfirstname)
    {
        $this->billfirstname = $billfirstname;

        return $this;
    }

    /**
     * Get billfirstname.
     *
     * @return string
     */
    public function getBillfirstname()
    {
        return $this->billfirstname;
    }

    /**
     * Set billlastname.
     *
     * @param string $billlastname
     * @return Cart
     */
    public function setBilllastname($billlastname)
    {
        $this->billlastname = $billlastname;

        return $this;
    }

    /**
     * Get billlastname.
     *
     * @return string
     */
    public function getBilllastname()
    {
        return $this->billlastname;
    }

    /**
     * Set billaddress1.
     *
     * @param string $billaddress1
     * @return Cart
     */
    public function setBilladdress1($billaddress1)
    {
        $this->billaddress1 = $billaddress1;

        return $this;
    }

    /**
     * Get billaddress1.
     *
     * @return string
     */
    public function getBilladdress1()
    {
        return $this->billaddress1;
    }

    /**
     * Set billaddress2.
     *
     * @param string $billaddress2
     * @return Cart
     */
    public function setBilladdress2($billaddress2)
    {
        $this->billaddress2 = $billaddress2;

        return $this;
    }

    /**
     * Get billaddress2.
     *
     * @return string
     */
    public function getBilladdress2()
    {
        return $this->billaddress2;
    }

    /**
     * Set billcity.
     *
     * @param string $billcity
     * @return Cart
     */
    public function setBillcity($billcity)
    {
        $this->billcity = $billcity;

        return $this;
    }

    /**
     * Get billcity.
     *
     * @return string
     */
    public function getBillcity()
    {
        return $this->billcity;
    }

    /**
     * Set billzip.
     *
     * @param string $billzip
     * @return Cart
     */
    public function setBillzip($billzip)
    {
        $this->billzip = $billzip;

        return $this;
    }

    /**
     * Get billzip.
     *
     * @return string
     */
    public function getBillzip()
    {
        return $this->billzip;
    }

    /**
     * Set camefrom.
     *
     * @param int $camefrom
     * @return Cart
     */
    public function setCamefrom($camefrom)
    {
        $this->camefrom = $camefrom;

        return $this;
    }

    /**
     * Get camefrom.
     *
     * @return int
     */
    public function getCamefrom()
    {
        return $this->camefrom;
    }

    /**
     * Set creditcardtype.
     *
     * @param string $creditcardtype
     * @return Cart
     */
    public function setCreditcardtype($creditcardtype)
    {
        $this->creditcardtype = $creditcardtype;

        return $this;
    }

    /**
     * Get creditcardtype.
     *
     * @return string
     */
    public function getCreditcardtype()
    {
        return $this->creditcardtype;
    }

    /**
     * Set creditcardnumber.
     *
     * @param string $creditcardnumber
     * @return Cart
     */
    public function setCreditcardnumber($creditcardnumber)
    {
        $this->creditcardnumber = $creditcardnumber;

        return $this;
    }

    /**
     * Get creditcardnumber.
     *
     * @return string
     */
    public function getCreditcardnumber()
    {
        return $this->creditcardnumber;
    }

    /**
     * Set paypalconfirmed.
     *
     * @param bool $paypalconfirmed
     * @return Cart
     */
    public function setPaypalconfirmed($paypalconfirmed)
    {
        $this->paypalconfirmed = $paypalconfirmed;

        return $this;
    }

    /**
     * Get paypalconfirmed.
     *
     * @return bool
     */
    public function getPaypalconfirmed()
    {
        return $this->paypalconfirmed;
    }

    /**
     * Set crypted.
     *
     * @param string $crypted
     * @return Cart
     */
    public function setCrypted($crypted)
    {
        $this->crypted = $crypted;

        return $this;
    }

    /**
     * Get crypted.
     *
     * @return string
     */
    public function getCrypted()
    {
        return $this->crypted;
    }

    /**
     * Set incometransactionid.
     *
     * @param int $incometransactionid
     * @return Cart
     */
    public function setIncometransactionid($incometransactionid)
    {
        $this->incometransactionid = $incometransactionid;

        return $this;
    }

    /**
     * Get incometransactionid.
     *
     * @return int
     */
    public function getIncometransactionid()
    {
        return $this->incometransactionid;
    }

    /**
     * @param int $appleTransactionID
     */
    public function setAppleTransactionID($appleTransactionID): Cart
    {
        $this->appleTransactionID = $appleTransactionID;

        return $this;
    }

    /**
     * @return int
     */
    public function getAppleTransactionID()
    {
        return $this->appleTransactionID;
    }

    /**
     * @return string
     */
    public function getPurchaseToken()
    {
        return $this->purchaseToken;
    }

    /**
     * @param string $purchaseToken
     * @return Cart
     */
    public function setPurchaseToken($purchaseToken)
    {
        $this->purchaseToken = $purchaseToken;

        return $this;
    }

    /**
     * Set billcountry.
     *
     * @return Cart
     */
    public function setBillcountry(?Country $billcountry = null)
    {
        $this->billcountry = $billcountry;

        return $this;
    }

    /**
     * Get billcountry.
     *
     * @return Country
     */
    public function getBillcountry()
    {
        return $this->billcountry;
    }

    /**
     * Set billstate.
     *
     * @return Cart
     */
    public function setBillstate(?State $billstate = null)
    {
        $this->billstate = $billstate;

        return $this;
    }

    /**
     * Get billstate.
     *
     * @return State
     */
    public function getBillstate()
    {
        return $this->billstate;
    }

    /**
     * Set coupon.
     *
     * @return Cart
     */
    public function setCoupon(?Coupon $coupon = null)
    {
        $this->coupon = $coupon;

        if ($coupon) {
            $coupon->addCart($this);
        }

        return $this;
    }

    /**
     * Get coupon.
     *
     * @return Coupon
     */
    public function getCoupon()
    {
        return $this->coupon;
    }

    /**
     * Set shipcountry.
     *
     * @return Cart
     */
    public function setShipcountry(?Country $shipcountry = null)
    {
        $this->shipcountry = $shipcountry;

        return $this;
    }

    /**
     * Get shipcountry.
     *
     * @return Country
     */
    public function getShipcountry()
    {
        return $this->shipcountry;
    }

    /**
     * Set shipstate.
     *
     * @return Cart
     */
    public function setShipstate(?State $shipstate = null)
    {
        $this->shipstate = $shipstate;

        return $this;
    }

    /**
     * Get shipstate.
     *
     * @return State
     */
    public function getShipstate()
    {
        return $this->shipstate;
    }

    /**
     * Set user.
     *
     * @return Cart
     */
    public function setUser(?Usr $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user.
     *
     * @return Usr
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Add cart item.
     *
     * @return Cart
     */
    public function addItem(CartItem $cartitem)
    {
        $cartitem->setCart($this);
        $this->items[] = $cartitem;

        if (empty($cartitem->getId()) && $this->getUser()) {
            $cartitem->setId($this->getUser()->getUserid());
        }
        $this->setError(null);

        return $this;
    }

    /**
     * Remove cart item.
     */
    public function removeItem(CartItem $cartitem)
    {
        $cartitem->setCart(null);
        $this->items->removeElement($cartitem);
        $this->setError(null);
    }

    /**
     * removing items by types.
     */
    public function removeItemsByType(array $types)
    {
        foreach ($this->getItemsByType($types) as $item) {
            $this->removeItem($item);
        }

        return $this;
    }

    /**
     * @return CartItem[]|ArrayCollection<array-key, CartItem>
     */
    public function getItems()
    {
        return $this->items;
    }

    public function isPaid()
    {
        return !empty($this->getPaydate());
    }

    public function getTotalPrice(bool $withCoupon = true)
    {
        $result = 0;
        $haveDiscountItem = false;
        $havePrepaidItem = $this->hasPrepaidAwPlusSubscription();

        foreach ($this->getItems() as $item) {
            if ($havePrepaidItem && $item instanceof AwPlusSubscription) {
                continue;
            }

            $result += $item->getTotalPrice();

            if ($item instanceof Discount) {
                $haveDiscountItem = true;
            }
        }

        // old carts. in old times, when applying coupon, we have not added Discount item, only set coupon to Cart
        if ($withCoupon && $this->coupon !== null && !$haveDiscountItem) {
            $result -= $result * ($this->coupon->getDiscount() / 100);
        }

        return round($result, 2);
    }

    public function getScheduledTotal()
    {
        $result = 0;

        foreach ($this->getItems() as $item) {
            if (!empty($item->getScheduledDate())) {
                $result += $item->getTotalPrice();
            }
        }

        return $result;
    }

    public function getScheduledDate(): ?\DateTime
    {
        foreach ($this->getItems() as $item) {
            if (!empty($scheduledDate = $item->getScheduledDate())) {
                return $scheduledDate;
            }
        }

        return null;
    }

    public function getDiscountAmount(?Coupon $coupon = null)
    {
        $coupon = ($coupon) ? $coupon : $this->getCoupon();

        if (!$coupon) {
            return 0;
        }

        return round($this->getTotalPrice(false) * ($coupon->getDiscount() / 100), 2);
    }

    /**
     * @return CartItem|null
     */
    public function getDiscount()
    {
        $discount = $this->getItemsByType([Discount::TYPE]);

        return $discount->count() ? $discount->first() : null;
    }

    public function getQuantityItems()
    {
        $items = $this->getItems();
        $result = 0;

        foreach ($items as $item) {
            $result += $item->getCnt();
        }

        return $result;
    }

    /**
     * get items of cart.
     *
     * @param int[] $types types of items - See CartItem::TYPE
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getItemsByType(array $types)
    {
        return $this->getItems()->filter(function ($item) use ($types) {
            /** @var CartItem $item */
            return in_array($item::TYPE, $types);
        });
    }

    /**
     * Has elements with at least one of the types.
     *
     * @param array $types types of items
     * @return bool
     */
    public function hasItemsByType(array $types)
    {
        return $this->getItems()->exists(function ($k, $item) use ($types) {
            /** @var CartItem $item */
            return in_array($item::TYPE, $types);
        });
    }

    public function hasDiscountById(int $discountId): bool
    {
        /** @var Discount $item */
        foreach ($this->getItemsByType([Discount::TYPE]) as $item) {
            if ($item->getId() === $discountId) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Cart
     */
    public function clear()
    {
        foreach ($this->getItems() as $item) {
            $this->removeItem($item);
        }
        $this->setCoupon(null);
        $this->setCouponname(null);
        $this->setCouponcode(null);
        $this->setError(null);

        return $this;
    }

    /**
     * cart was paid automatically through paypal recurring payments.
     *
     * @return bool
     */
    public function isAwPlusRecurringPayment()
    {
        return $this->getItems()->exists(function ($k, $item) {
            /** @var CartItem $item */
            return $item instanceof AwPlus && $item->getUserdata() == AwPlus::FLAG_RECURRING;
        });
    }

    /**
     * @return bool
     */
    public function isAwPlusSubscription()
    {
        return $this->getItems()->exists(function ($k, $item) {
            /** @var CartItem $item */
            return $item->isAwPlusSubscription();
        });
    }

    public function isSubscription()
    {
        return $this->getItems()->exists(function ($k, $item) {
            /** @var CartItem $item */
            return $item->isSubscription();
        });
    }

    public function isAwPlus()
    {
        return $this->getItems()->exists(function ($k, $item) {
            /** @var CartItem $item */
            return $item instanceof AwPlusUpgradableInterface;
        });
    }

    public function getOneCardsQuantity()
    {
        /** @var \Doctrine\Common\Collections\Collection $onecards */
        $onecards = $this->getItemsByType([OneCardItem::TYPE]);

        if ($onecards) {
            return $onecards->count() ? $onecards->first()->getQuantity() : 0;
        }

        return 0;
    }

    public function hasItemsWithQuantity(): bool
    {
        return $this->getItems()->exists(function ($k, CartItem $item) {
            return !empty($item->getQuantity());
        });
    }

    public function getCartRecurringAmount()
    {
        /** @var \Doctrine\Common\Collections\Collection $recurring */
        $recurring = $this->getItemsByType([AwPlusRecurring::TYPE]);

        if ($recurring) {
            return $recurring->first()->getRecurringAmount();
        }

        return 0;
    }

    public function getImmediateAmount(): float
    {
        return array_sum(
            array_map(
                function (CartItem $item) { return $item->getTotalPrice(); },
                $this->getItems()->filter(
                    function (CartItem $item) { return !$item->isScheduled(); }
                )->toArray()
            )
        );
    }

    /**
     * @return int|null - id of AbInvoice entity
     */
    public function getBookingInvoiceId()
    {
        $items = $this->getItems()->filter(function ($item) {
            /** @var CartItem $item */
            return $item instanceof Booking
                && $item->getUserdata()
                && $item->getOperation();
        });

        if (sizeof($items) > 0) {
            return $items->first()->getUserdata();
        }

        return null;
    }

    /**
     * @return int|null - id of AbRequest entity
     */
    public function getBookingRequestId(): ?int
    {
        $items = $this->getItems()->filter(function ($item) {
            /** @var CartItem $item */
            return $item instanceof Booking
                && $item->getId();
        });

        if (sizeof($items) > 0) {
            return $items->first()->getId();
        }

        return null;
    }

    public function recurringPaymentAllowed()
    {
        return in_array($this->getPaymenttype(), [
            self::PAYMENTTYPE_TEST_CREDITCARD,
            self::PAYMENTTYPE_CREDITCARD,
            self::PAYMENTTYPE_PAYPAL,
            self::PAYMENTTYPE_TEST_PAYPAL,
        ]);
    }

    public function allowSendMailPaymentComplete()
    {
        return in_array($this->getPaymenttype(), [
            Cart::PAYMENTTYPE_CREDITCARD,
            Cart::PAYMENTTYPE_RECURLY,
            Cart::PAYMENTTYPE_TEST_CREDITCARD,
            Cart::PAYMENTTYPE_PAYPAL,
            Cart::PAYMENTTYPE_TEST_PAYPAL,
            Cart::PAYMENTTYPE_BITCOIN,
            Cart::PAYMENTTYPE_ANDROIDMARKET,
            Cart::PAYMENTTYPE_APPSTORE,
            Cart::PAYMENTTYPE_ETHEREUM,
            Cart::PAYMENTTYPE_STRIPE,
            Cart::PAYMENTTYPE_STRIPE_INTENT,
            null,
        ])
            && !($this->getItems()->count() === 1 && $this->hasItemsByType(CartItem::TRIAL_TYPES))
            && $this->getImmediateAmount() > 0;
    }

    public function isPayPalPaymentType()
    {
        return in_array($this->getPaymenttype(), [Cart::PAYMENTTYPE_PAYPAL, Cart::PAYMENTTYPE_TEST_PAYPAL]);
    }

    public function isCreditCardPaymentType()
    {
        return in_array($this->getPaymenttype(), [Cart::PAYMENTTYPE_CREDITCARD, Cart::PAYMENTTYPE_TEST_CREDITCARD, Cart::PAYMENTTYPE_RECURLY, Cart::PAYMENTTYPE_STRIPE, Cart::PAYMENTTYPE_STRIPE_INTENT]);
    }

    public function isBitcoinPaymentType()
    {
        return in_array($this->getPaymenttype(), [Cart::PAYMENTTYPE_BITCOIN]);
    }

    public function isEthereumPaymentType()
    {
        return in_array($this->getPaymenttype(), [Cart::PAYMENTTYPE_ETHEREUM]);
    }

    public function isSandboxMode()
    {
        return in_array($this->getPaymenttype(), [Cart::PAYMENTTYPE_TEST_PAYPAL, Cart::PAYMENTTYPE_TEST_CREDITCARD]);
    }

    public function hasMobileAwPlusSubscription()
    {
        return in_array($this->getPaymenttype(), [
            Cart::PAYMENTTYPE_ANDROIDMARKET,
            Cart::PAYMENTTYPE_APPSTORE,
        ]) && $this->isAwPlusSubscription();
    }

    public function hasPrepaidAwPlusSubscription()
    {
        return $this->hasItemsByType([AwPlusPrepaid::TYPE]);
    }

    public function hasAwBusinessCredit()
    {
        return $this->hasItemsByType([AwBusinessCredit::TYPE]);
    }

    public function saveBillingAddress(Billingaddress $address)
    {
        $this->setBillfirstname($address->getFirstname());
        $this->setBilllastname($address->getLastname());
        $this->setBilladdress1($address->getAddress1());
        $this->setBilladdress2($address->getAddress2());
        $this->setBillcity($address->getCity());
        $this->setBillstate($address->getStateid());
        $this->setBillcountry($address->getCountryid());
        $this->setBillzip($address->getZip());
    }

    public function getItemsForGA($invoice = null): array
    {
        $result = [];
        $discount = 0;
        $siteadDesc = $invoice instanceof AbInvoice && $invoice->getMessage()->getRequest()->getSiteAd()
            ? $invoice->getMessage()->getRequest()->getSiteAd()->getDescription() : '';

        foreach ($this->getItems() as $item) {
            if (Discount::TYPE === $item::TYPE) {
                $discount += $item->getPrice();
            } else {
                $result[] = [
                    'name' => $item->getName(),
                    'quantity' => $item->getCnt(),
                    'entityType' => $item::TYPE,
                    'entity' => substr(strrchr(get_class($item), '\\'), 1),
                    'sitead' => $siteadDesc,
                    'price' => $item->getPrice(),
                    'total' => $item->getTotalPrice(),
                ];
            }
        }

        if ($discount) {
            foreach ($result as &$item) {
                if ($item['total'] >= $discount) {
                    $item['total'] -= abs($discount);
                    $item['price'] = $item['total'] / $item['quantity'];

                    break;
                }
            }
        }

        return $result;
    }

    /**
     * @return AwPlusUpgradableInterface|CartItem|null
     */
    public function getPlusItem()
    {
        return $this->findItemByClass(AwPlusUpgradableInterface::class);
    }

    public function getSubscriptionItem(): ?CartItem
    {
        $result = $this->findItemByClass(AwPlusSubscriptionInterface::class);

        if ($result) {
            return $result;
        }

        return $this->findItemByClass(AT201SubscriptionInterface::class);
    }

    /**
     * @return AT201SubscriptionInterface|CartItem|null
     */
    public function getAT201Item()
    {
        return $this->findItemByClass(AT201SubscriptionInterface::class);
    }

    public function findItemByClass(string $class): ?CartItem
    {
        foreach ($this->items as $item) {
            if ($item instanceof $class) {
                return $item;
            }
        }

        return null;
    }

    public function recalcNeeded()
    {
        return
            !empty($this->getPlusItem())
            && (empty($this->calcDate) || (time() - $this->calcDate->getTimestamp()) > 3600);
    }

    public function setSource($val)
    {
        $this->source = $val;

        return $this;
    }

    /**
     * @return string
     */
    public function getCartAttrHash()
    {
        return $this->cartAttrHash;
    }

    /**
     * @param string $cartAttrHash
     * @return Cart
     */
    public function setCartAttrHash($cartAttrHash)
    {
        $this->cartAttrHash = $cartAttrHash;

        return $this;
    }

    public function isNewSubscription(): bool
    {
        return $this->isSubscription() && $this->source == self::SOURCE_USER;
    }

    public function isGiveBalanceWatchCredit(): bool
    {
        if (true === $this->hasItemsByType([BalanceWatchCredit::TYPE])
            || (
                ($this->isAwPlusSubscription() || true === $this->hasItemsByType([AwPlus1Year::TYPE]))
                && true === $this->hasItemsByType([BalanceWatchCredit::TYPE, AwPlusGift::TYPE])
                && null === $this->getAT201Item()
            ) || (
                !is_null($this->getCoupon())
                && (!$this->isAwPlusSubscription() && false === $this->hasItemsByType([AwPlus1Year::TYPE]))
            ) || $this->hasItemsByType([AwPlusPrepaid::TYPE])
        ) {
            return false;
        }

        return true;
    }

    public function getTextPaymentType(): string
    {
        switch ($this->getPaymenttype()) {
            case self::PAYMENTTYPE_CREDITCARD:
                return 'credit-card';

            case self::PAYMENTTYPE_PAYPAL:
                return 'paypal';

            case self::PAYMENTTYPE_BITCOIN:
                return 'bitcoin';

            case self::PAYMENTTYPE_ETHEREUM:
                return 'ethereum';

            default:
                return 'unknown';
        }
    }

    public function isRecurring(): bool
    {
        return !$this->isAwPlusRecurringPayment()
            && $this->isAwPlusSubscription();
    }

    public function isFirstTimeSubscriptionPending(): bool
    {
        $coupon = $this->getCoupon();

        if (is_null($coupon) || !$coupon->getFirsttimeonly()) {
            return false;
        }

        $subscription = $this->getSubscriptionItem();

        return $subscription
            && $subscription->isScheduled()
            && $subscription->getScheduledDate() > date_create();
    }

    /**
     * Returns an array of messages.
     *
     * @return array<Message>
     */
    public static function getTranslationMessages()
    {
        $prefix = self::PAYMENT_TYPE_NAMES_PREFIX;

        return [
            (new Message($prefix . self::PAYMENTTYPE_CREDITCARD))->setDesc('Credit Card'),
            (new Message($prefix . self::PAYMENTTYPE_CHECKBYINTERNET))->setDesc('Check by internet'),
            (new Message($prefix . self::PAYMENTTYPE_MAILINCHECK))->setDesc('Mail in check'),
            (new Message($prefix . self::PAYMENTTYPE_TEST))->setDesc('Test'),
            (new Message($prefix . self::PAYMENTTYPE_PAYPAL))->setDesc('PayPal'),
            (new Message($prefix . self::PAYMENTTYPE_TEST_PAYPAL))->setDesc('Test PayPal'),
            (new Message($prefix . self::PAYMENTTYPE_TEST_CREDITCARD))->setDesc('Test Credit Card'),
            (new Message($prefix . self::PAYMENTTYPE_APPSTORE))->setDesc('App Store (iOS)'),
            (new Message($prefix . self::PAYMENTTYPE_ANDROIDMARKET))->setDesc('Google Play'),
            (new Message($prefix . self::PAYMENTTYPE_BITCOIN))->setDesc('Bitcoin'),
            (new Message($prefix . self::PAYMENTTYPE_RECURLY))->setDesc('Credit Card'),
            (new Message($prefix . self::PAYMENTTYPE_BUSINESS_BALANCE))->setDesc('Business balance'),
            (new Message($prefix . self::PAYMENTTYPE_ETHEREUM))->setDesc('Ethereum'),
            (new Message($prefix . self::PAYMENTTYPE_STRIPE))->setDesc('Stripe (old)'),
            (new Message($prefix . self::PAYMENTTYPE_QSTRANSCATION))->setDesc('QsTransaction'),
            (new Message($prefix . self::PAYMENTTYPE_STRIPE_INTENT))->setDesc('Credit Card'),
        ];
    }

    private function archive()
    {
        $user = $this->getUser();

        if ($user) {
            $this
                ->setFirstname($user->getFirstname())
                ->setLastname($user->getLastname())
                ->setEmail($user->getEmail());
        }

        $coupon = $this->getCoupon();

        if ($coupon) {
            $this
                ->setCouponcode($coupon->getCode())
                ->setCouponname($coupon->getName());
        }
    }
}
