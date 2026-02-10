<?php

namespace AwardWallet\MainBundle\Entity\Listener;

use AwardWallet\MainBundle\Entity\CartItem;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

class CartItemListener
{
    protected $args;

    public function __construct(TranslateArgs $args)
    {
        $this->args = $args;
    }

    public function prePersist(CartItem $item, LifecycleEventArgs $args)
    {
        $item->translate($this->args);
    }

    //    public function preUpdate(Cartitem $item, PreUpdateEventArgs $args)
    //    {
    //        $item->translate($this->args);
    //    }
}
