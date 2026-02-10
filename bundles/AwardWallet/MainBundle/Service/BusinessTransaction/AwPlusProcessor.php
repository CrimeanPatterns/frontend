<?php

namespace AwardWallet\MainBundle\Service\BusinessTransaction;

use AwardWallet\MainBundle\Entity\BusinessTransaction\UpgradedToAwPlus;
use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Globals\Cart\Manager as CartManager;

class AwPlusProcessor
{
    private CartManager $cartManager;

    private BusinessTransactionManager $businessTransactionManager;

    public function __construct(CartManager $cartManager, BusinessTransactionManager $businessTransactionManager)
    {
        $this->cartManager = $cartManager;
        $this->businessTransactionManager = $businessTransactionManager;
    }

    public function upgradeToAwPlus(Useragent $useragent, $force = false): bool
    {
        if (!$force && (!$useragent->getClientid() || $useragent->getClientid()->getAccountlevel() != ACCOUNT_LEVEL_FREE)) {
            throw new \InvalidArgumentException(sprintf("User %d must have a Regular account level", $useragent->getClientid()->getUserid()));
        }

        if ($result = $this->businessTransactionManager->addTransaction($useragent->getAgentid(), new UpgradedToAwPlus($useragent))) {
            $item = new AwPlusSubscription();

            $this->cartManager->setUser($useragent->getClientid());
            $cart = $this->cartManager->createNewCart();
            $cart->setPaymenttype(Cart::PAYMENTTYPE_BUSINESS_BALANCE);
            $cart->addItem($item);
            $this->cartManager->markAsPayed($cart);
        }

        return $result;
    }
}
