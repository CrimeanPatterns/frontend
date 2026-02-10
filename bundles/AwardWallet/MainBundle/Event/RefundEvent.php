<?php

namespace AwardWallet\MainBundle\Event;

use AwardWallet\MainBundle\Entity\Cart;
use Symfony\Component\EventDispatcher\Event;

class RefundEvent extends Event
{
    public const NAME = 'cart_refund';

    /**
     * @var Cart
     */
    private $cart;

    public function __construct(Cart $cart)
    {
        $this->cart = $cart;
    }

    public function getCart(): Cart
    {
        return $this->cart;
    }
}
