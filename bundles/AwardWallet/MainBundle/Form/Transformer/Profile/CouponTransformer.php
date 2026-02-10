<?php

namespace AwardWallet\MainBundle\Form\Transformer\Profile;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Form\Model\Profile\CouponModel;
use AwardWallet\MainBundle\Form\Transformer\AbstractModelTransformer;

class CouponTransformer extends AbstractModelTransformer
{
    /**
     * @var Cart
     */
    public function transform($cart)
    {
        return (new CouponModel())
            ->setCoupon($cart->getCoupon() ? $cart->getCoupon()->getCode() : null)
            ->setEntity($cart);
    }
}
