<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="CouponItem")
 * @ORM\Entity()
 */
class CouponItem
{
    /**
     * @var int
     * @ORM\Column(name="CouponItemID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;
    /**
     * @var Coupon
     * @ORM\ManyToOne(targetEntity="Coupon", inversedBy="items", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="CouponID", referencedColumnName="CouponID")
     * })
     */
    private $coupon;
    /**
     * @var int
     * @ORM\Column(name="CartItemType", type="integer", nullable=false)
     */
    private $cartItemType;
    /**
     * @var int
     * @ORM\Column(name="Cnt", type="integer", nullable=false)
     */
    private $count = 1;

    public function __construct(Coupon $coupon, int $cartItemType)
    {
        $this->coupon = $coupon;
        $this->cartItemType = $cartItemType;
    }

    public function getCartItemType(): int
    {
        return $this->cartItemType;
    }

    public function getCount(): int
    {
        return $this->count;
    }
}
