<?php

namespace AwardWallet\MainBundle\Controller\Cart;

use AwardWallet\Common\PasswordCrypt\PasswordDecryptor;
use AwardWallet\MainBundle\Entity\AbInvoice;
use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\Cart\Manager;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/cart")
 */
class RecurlyController extends AbstractController
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack, LoggerInterface $paymentLogger, LoggerInterface $logger)
    {
        parent::__construct($paymentLogger, $logger);
        $this->requestStack = $requestStack;
    }

    /**
     * @Route("/recurly/checkout", name="aw_cart_recurly_checkout", methods={"POST"})
     * @Security("is_granted('ROLE_USER')")
     */
    public function checkoutAction(PasswordDecryptor $passwordDecryptor, Manager $cartManager, SessionInterface $session)
    {
        $cart = $cartManager->getCart();
        $this->logger->info("starting recurly payment for cart", ["CartID" => $cart->getCartid(), "UserID" => $cart->getUser()->getUserid()]);
        /** @var Usr $user */
        $booker = $cartManager->getBooker($cart);
        $invoice = $cartManager->getBookingInvoice($cart);

        if ($cart->getPaymenttype() != Cart::PAYMENTTYPE_RECURLY
            || $cart->isAwPlusRecurringPayment()
            || $cart->getTotalPrice() == 0
            || !$session->has('CreditCardInfo')
            || !$session->has('billing.address')
            || empty($booker)
            || empty($invoice)) {
            return new JsonResponse(
                [
                    'success' => 0,
                ]
            );
        }

        $cardData = $session->get('CreditCardInfo');
        $billingAddress = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Billingaddress::class)->find($session->get('billing.address'));
        $address = [
            'FirstName' => $cart->getBillfirstname(),
            'LastName' => $cart->getBilllastname(),
            'Address1' => $billingAddress->getAddress1(),
            'Address2' => $billingAddress->getAddress1(),
            'City' => $billingAddress->getCity(),
            'StateCode' => $billingAddress->getStateid()->getCodeOrName(),
            'CountryCode' => $billingAddress->getCountryid()->getCode(),
            'Zip' => $billingAddress->getZip(),
        ];

        try {
            $transactionId = $this->pay($cart, $cardData, $address, $passwordDecryptor->decrypt($booker->getBookerInfo()->getPayPalPassword()), $invoice);
        } catch (\Recurly_ValidationError $e) {
            $session->remove('CreditCardInfo');
            $this->logger->error("recurly exception: " . var_export($e->getMessage(), true), ["CartID" => $cart->getCartid(), "UserID" => $cart->getUser()->getUserid()]);
            $this->logger->error("recurly exception trace: " . var_export($e->getTraceAsString(), true), ["CartID" => $cart->getCartid(), "UserID" => $cart->getUser()->getUserid()]);

            return new JsonResponse([
                'success' => 0,
                'error' => $e->getMessage(),
            ]);
        }

        $cart->setBillingtransactionid($transactionId);
        // Complete
        $cartManager->markAsPayed($cart, $billingAddress);
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
    public function pay(Cart $cart, array $cardData, array $address, $apiKey, AbInvoice $invoice)
    {
        $this->logger->info("processing recurly payment", ["CartID" => $cart->getCartid(), "UserID" => $cart->getUser()->getUserid()]);

        \Recurly_Client::$apiKey = $apiKey;

        $abRequest = $invoice->getMessage()->getRequest();
        $transaction = new \Recurly_Transaction();
        $transaction->amount_in_cents = round($cart->getTotalPrice() * 100);
        $transaction->currency = 'USD';
        $transaction->description = "Payment for booking request #{$abRequest->getAbRequestID()}, order #{$cart->getCartid()}";

        $account = new \Recurly_Account();
        $account->account_code = $abRequest->getMainContactEmail();
        $account->email = $abRequest->getMainContactEmail();
        $account->first_name = $cart->getUser()->getFirstname();
        $account->last_name = $cart->getUser()->getLastname();

        $billing_info = new \Recurly_BillingInfo();
        $billing_info->first_name = $address['FirstName'];
        $billing_info->last_name = $address['LastName'];
        $billing_info->number = $cardData["cardNumber"];
        $billing_info->verification_value = $cardData["securityCode"];
        $billing_info->month = $cardData["expirationMonth"];
        $billing_info->year = $cardData["expirationYear"];
        $billing_info->address1 = $address['Address1'];
        $billing_info->address2 = $address['Address2'];
        $billing_info->city = $address['City'];
        $billing_info->country = $address['CountryCode'];
        $billing_info->state = $address['StateCode'];
        $billing_info->zip = $address['Zip'];
        $request = $this->requestStack->getMasterRequest();
        $billing_info->ip_address = $request->getClientIp();

        $account->billing_info = $billing_info;
        $transaction->account = $account;
        $this->logger->info("recurly transaction", ["CartID" => $cart->getCartid(), "UserID" => $cart->getUser()->getUserid()]);

        $transaction->create();
        $values = $transaction->getValues();
        $errors = $transaction->getErrors();
        $ref = $values['reference'] ?? null;

        if (empty($ref)) {
            $this->mainLogger->critical("empty recurly ref");
        }
        $this->logger->info("processed recurly payment for cart " . $cart->getCartid() . ", reference: " . $ref . ", errors: " . var_export($errors, true), ["CartID" => $cart->getCartid(), "UserID" => $cart->getUser()->getUserid()]);

        if (!empty($errors)) {
            throw new \Recurly_ValidationError(implode(", ", $errors), 0, []);
        }
        $this->logger->info("processed recurly payment for cart " . $cart->getCartid() . ", reference: " . $ref . ", errors: " . var_export($errors, true), ["CartID" => $cart->getCartid(), "UserID" => $cart->getUser()->getUserid()]);

        return $ref;
    }
}
