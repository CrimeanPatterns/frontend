<?php

namespace AwardWallet\MainBundle\Controller\Cart;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\Cart\CartUserSource;
use AwardWallet\MainBundle\Globals\Cart\Manager;
use AwardWallet\MainBundle\Service\Billing\MobilePaymentWarningChecker;
use AwardWallet\MainBundle\Service\Billing\PaypalRestApi;
use AwardWallet\MainBundle\Service\Billing\PaypalRestApiFactory;
use AwardWallet\MainBundle\Service\Billing\RecurringManager;
use PayPal\Exception\PayPalConnectionException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/cart")
 */
class CreditCardController extends AbstractController
{
    /**
     * @Route("/card/checkout", name="aw_cart_creditcard_checkout", methods={"POST"}, options={"expose"=true})
     * @Security("is_granted('USER_PAYER')")
     */
    public function checkoutAction(
        Request $request,
        PaypalRestApiFactory $paypalRestApiFactory,
        Manager $cartManager,
        CartUserSource $cartUserSource,
        PaypalRestApi $paypalRestApi,
        RecurringManager $recurringManager,
        MobilePaymentWarningChecker $mobilePaymentWarningChecker
    ) {
        $user = $cartUserSource->getCartOwner();

        $cart = $cartManager->getCart();
        $session = $request->getSession();

        if ($cart->recalcNeeded()
            || !in_array($cart->getPaymenttype(), [Cart::PAYMENTTYPE_CREDITCARD, Cart::PAYMENTTYPE_TEST_CREDITCARD])
            || $cart->getTotalPrice() == 0
            || !$session->has('CreditCardInfo')) {
            return new JsonResponse(
                [
                    'success' => 0,
                ]
            );
        }

        $cardData = $session->get('CreditCardInfo');

        // Recurring
        $setupRecurring = $cart->isRecurring();

        $isDirectItem = function (CartItem $item) { return empty($item->getScheduledDate()); };

        $directAmount = $cart->getImmediateAmount();

        $this->logger->warning("preparing credit card transaction", ["CartID" => $cart->getCartid(), "UserID" => $cart->getUser()->getUserid(), "SetupRecurring" => $setupRecurring, "DirectAmount" => $directAmount]);

        try {
            if ($cart->getBookingInvoiceId()) {
                $this->logger->info("paying booking request", ["CartID" => $cart->getCartid(), "UserID" => $cart->getUser()->getUserid(), "SetupRecurring" => $setupRecurring, "DirectAmount" => $directAmount]);
                $booker = $cartManager->getBooker($cart);
                $paypalApi = $paypalRestApiFactory->getByBooker($booker);
            } else {
                $paypalApi = $paypalRestApi;
            }

            if ($setupRecurring) {
                $cardId = $paypalApi->saveCreditCard($cardData, $cart->getBillfirstname(), $cart->getBilllastname(), $user->getUserid(), $cart->getCartid());
                $this->logger->info("card saved: {$cardId}", ["CartID" => $cart->getCartid(), "UserID" => $cart->getUser()->getId()]);

                if ($directAmount == 0) {
                    $paypalApi->checkSavedCard($cardId, $cart->getUser());
                }
            }

            if ($directAmount > 0) {
                if (empty($cardId)) {
                    $transactionId = $paypalApi->payWithCard($cart, $cardData, $directAmount, $isDirectItem);
                } else {
                    $transactionId = $paypalApi->payWithSavedCard($cart, $cardId, $directAmount, $isDirectItem);
                }
                $this->logger->info("paid with card, tx: {$transactionId}", ["CartID" => $cart->getCartid(), "UserID" => $cart->getUser()->getId()]);
            }

            if (!empty($cardId)) {
                try {
                    $recurringManager->cancelRecurringPayment($cart->getUser());
                } catch (\Exception $e) {
                    $this->logger->critical("failed to cancel recurring payment profile, CANCEL THROUGH PAYPAL: " . $e->getMessage(), ["UserID" => $cart->getUser()->getUserid(), "CartID" => $cart->getCartid(), "ProfileID" => $user->getPaypalrecurringprofileid(), "exception" => $e]);
                } finally {
                    $this->logger->info("created recurring profile {$cardId}, CartID: " . $cart->getCartid());
                    $this->logger->info("completed recurring transaction", ["ProfileID" => $cardId, "CartID" => $cart->getCartid(), "UserID" => $cart->getUser()->getUserid()]);
                    $user->setPaypalrecurringprofileid($cardId);
                    $mobilePaymentWarningChecker->addWarnings($user);
                    $user->setSubscription(Usr::SUBSCRIPTION_SAVED_CARD);
                    $this->getDoctrine()->getManager()->flush();
                }
            }

            if (!empty($transactionId)) {
                $cart->setBillingtransactionid($transactionId);
            }
            $cartManager->markAsPayed($cart);
            $this->getDoctrine()->getManager()->flush();
        } catch (PayPalConnectionException $e) {
            $error = $this->processPayPalException($e, $cart);
            $this->logger->warning("paypal error", ["UserID" => $cart->getUser()->getUserid(), "CartID" => $cart->getCartid(), "ProfileID" => $user->getPaypalrecurringprofileid(), "Error" => $error, "Exception" => $e->getMessage(), "Data" => $e->getData()]);
            $session->remove('CreditCardInfo');

            return new JsonResponse([
                'success' => 0,
                'error' => $error,
            ]);
        }

        // Clear session
        $session->remove('billing.address');
        $session->remove('CreditCardInfo');

        return new JsonResponse([
            'success' => 1,
            'cartId' => $cart->getCartid(),
        ]);
    }
}
