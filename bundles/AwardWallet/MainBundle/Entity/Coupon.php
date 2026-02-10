<?php

namespace AwardWallet\MainBundle\Entity;

use AwardWallet\MainBundle\Entity\CartItem\AwPlus;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Coupon.
 *
 * @ORM\Table(name="Coupon")
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\CouponRepository")
 */
class Coupon
{
    public const COUPON_HELP_COVID19_3MONTHS = "awplus-covid19-help";
    public const COUPON_10YEARS = "aw10-k92bzpq"; // aw 10 year anniversary
    public const SERVICE_AWPLUS_ONE_CARD = 13; // AwardWallet Plus for 6 months and OneCard
    /**
     * @deprecated - use CouponItem to create coupon with multiple items
     */
    public const SERVICE_AWPLUS_1_YEAR_AND_ONE_CARD = 11; // 1 year of AwardWallet Plus and AwardWallet OneCard

    /**
     * @var int
     * @ORM\Column(name="CouponID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $couponid;

    /**
     * @var string
     * @ORM\Column(name="Name", type="string", length=80, nullable=false)
     */
    protected $name;

    /**
     * @var string
     * @Assert\NotBlank()
     * @Assert\Length(max = 80)
     * @ORM\Column(name="Code", type="string", length=80, nullable=false)
     */
    protected $code;

    /**
     * @var int
     * @ORM\Column(name="Discount", type="integer", nullable=false)
     */
    protected $discount;

    /**
     * @var \DateTime
     * @ORM\Column(name="StartDate", type="date", nullable=true)
     */
    protected $startdate;

    /**
     * @var \DateTime
     * @ORM\Column(name="EndDate", type="date", nullable=true)
     */
    protected $enddate;

    /**
     * @var int
     * @ORM\Column(name="MaxUses", type="integer", nullable=true)
     */
    protected $maxuses;

    /**
     * @var bool
     * @ORM\Column(name="FirstTimeOnly", type="boolean", nullable=false)
     */
    protected $firsttimeonly = true;

    /**
     * @var \DateTime
     * @ORM\Column(name="CreationDate", type="datetime", nullable=false)
     */
    protected $creationdate;

    /**
     * @var Usr
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserID", referencedColumnName="UserID")
     * })
     */
    protected $user;

    /**
     * @var Cart[]
     * @ORM\OneToMany(targetEntity="Cart", mappedBy="coupon")
     */
    protected $carts;
    /**
     * @var CouponItem[]
     * @ORM\OneToMany(targetEntity="CouponItem", mappedBy="coupon", cascade={"persist"})
     */
    private $items;

    public function __construct()
    {
        $this->creationdate = new \DateTime();
        $this->carts = new ArrayCollection();
        $this->items = new ArrayCollection();
    }

    /**
     * Get couponid.
     *
     * @return int
     */
    public function getCouponid()
    {
        return $this->couponid;
    }

    /**
     * Set name.
     *
     * @param string $name
     * @return Coupon
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
     * Set code.
     *
     * @param string $code
     * @return Coupon
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
     * Set discount.
     *
     * @param int $discount
     * @return Coupon
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
     * Set startdate.
     *
     * @param \DateTime $startdate
     * @return Coupon
     */
    public function setStartdate($startdate)
    {
        $this->startdate = $startdate;

        return $this;
    }

    /**
     * Get startdate.
     *
     * @return \DateTime
     */
    public function getStartdate()
    {
        return $this->startdate;
    }

    /**
     * Set enddate.
     *
     * @param \DateTime $enddate
     * @return Coupon
     */
    public function setEnddate($enddate)
    {
        $this->enddate = $enddate;

        return $this;
    }

    /**
     * Get enddate.
     *
     * @return \DateTime
     */
    public function getEnddate()
    {
        return $this->enddate;
    }

    /**
     * Set maxuses.
     *
     * @param int $maxuses
     * @return Coupon
     */
    public function setMaxuses($maxuses)
    {
        $this->maxuses = $maxuses;

        return $this;
    }

    /**
     * Get maxuses.
     *
     * @return int
     */
    public function getMaxuses()
    {
        return $this->maxuses;
    }

    /**
     * Set firsttimeonly.
     *
     * @param bool $firsttimeonly
     * @return Coupon
     */
    public function setFirsttimeonly($firsttimeonly)
    {
        $this->firsttimeonly = $firsttimeonly;

        return $this;
    }

    /**
     * Get firsttimeonly.
     *
     * @return bool
     */
    public function getFirsttimeonly()
    {
        return $this->firsttimeonly;
    }

    /**
     * @return CouponItem[]
     */
    public function getItems(): array
    {
        $result = $this->items->toArray();

        if (empty($result)) {
            $result[] = new CouponItem($this, AwPlus::TYPE);
        }

        return $result;
    }

    /**
     * Set creationdate.
     *
     * @param \DateTime $creationdate
     * @return Coupon
     */
    public function setCreationdate($creationdate)
    {
        $this->creationdate = $creationdate;

        return $this;
    }

    /**
     * Get creationdate.
     *
     * @return \DateTime
     */
    public function getCreationdate()
    {
        return $this->creationdate;
    }

    /**
     * Set user.
     *
     * @return Coupon
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
     * Add cart.
     *
     * @return Coupon
     */
    public function addCart(Cart $cart)
    {
        $this->carts[] = $cart;

        return $this;
    }

    /**
     * Remove cart.
     */
    public function removeCart(Cart $cart)
    {
        $this->carts->removeElement($cart);
    }

    public function getCarts()
    {
        return $this->carts;
    }

    /**
     * dont use this! fail on long list of carts.
     *
     * @deprecated
     */
    public function getNumberOfUses()
    {
        /** @var ArrayCollection $carts */
        $carts = $this->getCarts();
        $filtered = $carts->filter(function ($cart) {
            /** @var Cart $cart */
            return $cart->isPaid();
        });

        return count($filtered);
    }

    public function isExpired()
    {
        $start = $this->getStartdate();
        $end = $this->getEnddate();
        $now = new \DateTime();

        return ($start && $start >= $now) || ($end && $end <= $now);
    }

    public function isInviteBonus(Usr $user)
    {
        return $this->getName() == 'Invite bonus' && preg_match('/^Invite-' . $user->getUserid() . '-/ims', $this->getCode());
    }

    public function addItem(int $cartItemType)
    {
        $this->items->add(new CouponItem($this, $cartItemType));
    }

    public function hasCartItemTypes(array $cartItemTypes): bool
    {
        foreach ($this->items as $item) {
            if (in_array($item->getCartItemType(), $cartItemTypes)) {
                return true;
            }
        }

        return false;
    }
}
