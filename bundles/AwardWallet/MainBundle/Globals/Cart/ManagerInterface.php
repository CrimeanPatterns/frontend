<?php

namespace AwardWallet\MainBundle\Globals\Cart;

use AwardWallet\MainBundle\Entity\Billingaddress;
use AwardWallet\MainBundle\Entity\Cart;

interface ManagerInterface
{
    /**
     * get unpaid or new cart.
     *
     * @param bool $createNewCart true - new instance, false - get unpaid or new cart
     * @return Cart
     */
    public function getCart($createNewCart = false);

    /**
     * save cart
     * if cart is null - get cart from Usr.
     */
    public function save(?Cart $cart = null);

    /**
     * mark as paid cart
     * if cart is null - get cart from Usr
     * Billing address for save in cart.
     */
    public function markAsPayed(?Cart $cart = null, ?Billingaddress $address = null);
}
