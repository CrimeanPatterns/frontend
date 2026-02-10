<?php

namespace AwardWallet\MainBundle\Controller\Cart;

use AwardWallet\Common\PasswordCrypt\PasswordDecryptor;
use AwardWallet\MainBundle\Entity\AbInvoice;
use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\Cart\Manager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/cart/stripe")
 */
class StripeController extends AbstractController
{
    /**
     * @Route("/checkout", name="aw_cart_recurly_checkout", methods={"POST"})
     * @Security("is_granted('ROLE_USER')")
     */
    public function checkoutAction(PasswordDecryptor $passwordDecryptor, Manager $cartManager, SessionInterface $session)
    {
        $cart = $cartManager->getCart();
        $this->logger->info("starting stripe payment for cart", ["CartID" => $cart->getCartid(), "UserID" => $cart->getUser()->getUserid()]);
        /** @var Usr $user */
        $booker = $cartManager->getBooker($cart);
        $invoice = $cartManager->getBookingInvoice($cart);

        if ($cart->getPaymenttype() != Cart::PAYMENTTYPE_STRIPE
            || $cart->isAwPlusRecurringPayment()
            || $cart->getTotalPrice() == 0
            || !$session->has('CreditCardInfo')
            || empty($booker)
            || empty($invoice)) {
            return new JsonResponse(
                [
                    'success' => 0,
                ]
            );
        }

        $cardData = $session->get('CreditCardInfo');

        try {
            $transactionId = $this->pay($cart, $cardData, $passwordDecryptor->decrypt($booker->getBookerInfo()->getPayPalPassword()), $invoice);
        } catch (\Stripe\Error\Card $e) {
            $session->remove('CreditCardInfo');
            $this->logger->error("stripe exception: " . var_export($e->getMessage(), true), ["CartID" => $cart->getCartid(), "UserID" => $cart->getUser()->getUserid()]);
            $this->logger->error("stripe exception trace: " . var_export($e->getTraceAsString(), true), ["CartID" => $cart->getCartid(), "UserID" => $cart->getUser()->getUserid()]);

            return new JsonResponse([
                'success' => 0,
                'error' => $e->getMessage(),
            ]);
        }

        $cart->setBillingtransactionid($transactionId);
        // Complete
        $cartManager->markAsPayed($cart, null);
        $this->getDoctrine()->getManager()->flush();

        // Clear session
        $session->remove('billing.address');
        $session->remove('CreditCardInfo');

        return new JsonResponse([
            'success' => 1,
            'cartId' => $cart->getCartid(),
        ]);
    }

    // public for old code, remove it then
    public function pay(Cart $cart, array $cardData, $apiKey, AbInvoice $invoice)
    {
        $this->logger->info("processing stripe payment", ["CartID" => $cart->getCartid(), "UserID" => $cart->getUser()->getUserid()]);

        \Stripe\Stripe::setApiKey($apiKey);
        \Stripe\Stripe::setLogger($this->logger);

        $abRequest = $invoice->getMessage()->getRequest();
        $charge = \Stripe\Charge::create([
            'amount' => round($cart->getTotalPrice() * 100),
            'currency' => 'usd',
            'description' => "Payment for booking request #{$abRequest->getAbRequestID()}, order #{$cart->getCartid()}",
            'source' => [
                'exp_month' => $cardData["expirationMonth"],
                'exp_year' => $cardData["expirationYear"],
                'number' => $cardData["cardNumber"],
                'object' => 'card',
                'cvc' => $cardData["securityCode"],
                'statement_descriptor' => 'booking request ' . $abRequest->getAbRequestID(),
            ],
        ]);

        if ($charge instanceof \Stripe\Charge) {
            $this->logger->info("charge transaction completed", ["CartID" => $cart->getCartid(), "UserID" => $cart->getUser()->getUserid(), "ID" => $charge->id, "Status" => $charge->status]);

            return $charge->id;
        }

        throw new \Exception("Unknown error while processing stripe payment");
    }
}
