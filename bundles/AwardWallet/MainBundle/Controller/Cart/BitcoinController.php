<?php

namespace AwardWallet\MainBundle\Controller\Cart;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Globals\Cart\Manager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/cart/bitcoin")
 */
class BitcoinController extends AbstractController
{
    /**
     * @Route("/prepare", name="aw_cart_bitcoin_prepare", options={"expose"=true})
     * @Security("is_granted('ROLE_USER')")
     */
    public function prepareAction(Manager $cartManager)
    {
        $cart = $cartManager->getCart();
        $cart->setPaymenttype(Cart::PAYMENTTYPE_BITCOIN);
        $cartManager->save($cart);

        return new Response("OK");
    }

    /**
     * @Route("/status/{id}", name="aw_cart_bitcoin_status", requirements={"id" = "\d+"}, options={"expose"=true})
     * @Security("is_granted('ROLE_USER') and is_granted('VIEW', cart)")
     * @ParamConverter("cart", class="AwardWalletMainBundle:Cart")
     */
    public function statusAction(Cart $cart)
    {
        return new Response(!empty($cart->getPaydate()) ? "complete" : "wait");
    }
}
