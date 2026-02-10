<?php

namespace AwardWallet\MainBundle\Service\InAppPurchase;

use AwardWallet\MainBundle\Entity\Cart;
use Psr\Log\LoggerInterface;

interface CartMatcherInterface
{
    public function match(Cart $cart, LoggerInterface $logger): bool;
}
