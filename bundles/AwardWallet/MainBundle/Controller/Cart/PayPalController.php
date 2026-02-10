<?php

namespace AwardWallet\MainBundle\Controller\Cart;

use AwardWallet\Common\Monolog\Processor\TraceProcessor;
use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem;
use AwardWallet\MainBundle\Entity\CartItem\AwPlus;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusPrepaid;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusRecurring;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription;
use AwardWallet\MainBundle\Entity\CartItem\OneCard;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\Cart\Manager;
use AwardWallet\MainBundle\Globals\Cart\SubscriptionPeriod;
use AwardWallet\MainBundle\Service\Billing\ExpirationCalculator;
use AwardWallet\MainBundle\Service\Billing\MobilePaymentWarningChecker;
use AwardWallet\MainBundle\Service\Billing\PaypalSoapApi;
use AwardWallet\MainBundle\Service\Billing\RecurringManager;
use PayPal\CoreComponentTypes\BasicAmountType;
use PayPal\EBLBaseComponents\BillingAgreementDetailsType;
use PayPal\EBLBaseComponents\BillingPeriodDetailsType;
use PayPal\EBLBaseComponents\CreateRecurringPaymentsProfileRequestDetailsType;
use PayPal\EBLBaseComponents\CreditCardDetailsType;
use PayPal\EBLBaseComponents\DoExpressCheckoutPaymentRequestDetailsType;
use PayPal\EBLBaseComponents\PayerInfoType;
use PayPal\EBLBaseComponents\PaymentDetailsItemType;
use PayPal\EBLBaseComponents\PaymentDetailsType;
use PayPal\EBLBaseComponents\PersonNameType;
use PayPal\EBLBaseComponents\RecurringPaymentsProfileDetailsType;
use PayPal\EBLBaseComponents\ScheduleDetailsType;
use PayPal\EBLBaseComponents\SetExpressCheckoutRequestDetailsType;
use PayPal\PayPalAPI\CreateRecurringPaymentsProfileReq;
use PayPal\PayPalAPI\CreateRecurringPaymentsProfileRequestType;
use PayPal\PayPalAPI\DoExpressCheckoutPaymentReq;
use PayPal\PayPalAPI\DoExpressCheckoutPaymentRequestType;
use PayPal\PayPalAPI\GetExpressCheckoutDetailsReq;
use PayPal\PayPalAPI\GetExpressCheckoutDetailsRequestType;
use PayPal\PayPalAPI\SetExpressCheckoutReq;
use PayPal\PayPalAPI\SetExpressCheckoutRequestType;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * @Route("/cart/paypal")
 */
class PayPalController extends AbstractController
{
    private RecurringManager $recurringManager;
    private PaypalSoapApi $paypalSoapApi;
    private RouterInterface $router;
    private Manager $cartManager;
    private RequestStack $requestStack;
    private ExpirationCalculator $expirationCalculator;
    private AwTokenStorageInterface $tokenStorage;

    public function __construct(
        RecurringManager $recurringManager,
        PaypalSoapApi $paypalSoapApi,
        RouterInterface $router,
        Manager $cartManager,
        RequestStack $requestStack,
        ExpirationCalculator $expirationCalculator,
        AwTokenStorageInterface $tokenStorage,
        LoggerInterface $paymentLogger,
        LoggerInterface $logger
    ) {
        parent::__construct($paymentLogger, $logger);
        $this->recurringManager = $recurringManager;
        $this->paypalSoapApi = $paypalSoapApi;
        $this->router = $router;
        $this->cartManager = $cartManager;
        $this->requestStack = $requestStack;
        $this->expirationCalculator = $expirationCalculator;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @Route("/prepare", name="aw_cart_paypal_prepare")
     * @Security("is_granted('ROLE_USER')")
     * @Template("@AwardWalletMain/Cart/PayPal/prepare.html.twig")
     */
    public function prepareAction(Request $request)
    {
        if (!$this->check()) {
            return $this->redirect($this->router->generate('aw_cart_common_paymenttype'));
        }
        $session = $request->getSession();
        $session->set('PayPalStep', 'prepare');

        return [];
    }

    /**
     * @Route("/process", name="aw_cart_paypal_process")
     * @Security("is_granted('ROLE_USER')")
     */
    public function processAction(Request $request, MobilePaymentWarningChecker $mobilePaymentWarningChecker)
    {
        if (!$this->check()) {
            return $this->redirect($this->router->generate('aw_cart_common_paymenttype'));
        }

        $cart = $this->cartManager->getCart();
        $session = $request->getSession();

        if (!$session->has('PayPalStep')) {
            return $this->redirect($this->router->generate('aw_cart_paypal_prepare'));
        }

        $isDirectItem = function (CartItem $item) {
            return !(
                $item instanceof AwPlusSubscription
            );
        };

        if ($session->get('PayPalStep') == 'prepare') {
            // prepare
            try {
                $result = $this->preparePayPal($cart);

                // redirect
                if (!$cart->isSandboxMode()) {
                    return new JsonResponse([
                        'status' => 'ok',
                        'url' => "https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=" . $result->Token,
                    ]);
                } else {
                    return new JsonResponse([
                        'status' => 'ok',
                        'url' => "https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=" . $result->Token,
                    ]);
                }
            } catch (\Exception $e) {
                $error = $this->processPayPalException($e, $cart);

                return new JsonResponse([
                    'status' => 'fail',
                    'error' => $error,
                ]);
            }
        } else {
            $usrRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);
            $subscriptionParams = $this->getSubscriptionParams($cart);

            $directAmount = array_sum(array_map(function (CartItem $item) { return $item->getTotalPrice(); }, $cart->getItems()->filter($isDirectItem)->toArray()));

            if ($directAmount > 0) {
                try {
                    $this->logger->info("paying direct payment", ["DirectAmount" => $directAmount, "UserID" => $cart->getUser()->getUserid(), "CartID" => $cart->getCartid()]);
                    $this->processPayPal($cart, $isDirectItem);
                    $directPaid = true;
                } catch (\Exception $e) {
                    $error = $this->processPayPalException($e, $cart);

                    return new JsonResponse([
                        'status' => 'fail',
                        'error' => $error,
                    ]);
                }
            }

            // Recurring

            try {
                $mobilePaymentWarningChecker->addWarnings($cart->getUser());

                if (!$cart->isAwPlusRecurringPayment() && $cart->hasItemsByType([AwPlus::TYPE, AwPlusSubscription::TYPE])) {
                    $this->recurringManager->cancelRecurringPayment($cart->getUser());

                    if ($cart->hasItemsByType([AwPlusRecurring::TYPE, AwPlusSubscription::TYPE])) {
                        $subscriptionParams['paymentInfo'] = ['Token' => $session->get('PayPalToken')];
                        $this->logger->info("creating subscription", ["Subscription" => $subscriptionParams, "UserID" => $cart->getUser()->getUserid(), "CartID" => $cart->getCartid()]);
                        $profileId = $this->createRecurringPayment($subscriptionParams);

                        if (is_string($profileId)) {
                            $cart->getUser()->setPaypalrecurringprofileid($profileId);
                            $cart->getUser()->setSubscription(Usr::SUBSCRIPTION_PAYPAL);
                            $cart->getUser()->setSubscriptionType(Usr::SUBSCRIPTION_TYPE_AWPLUS);
                            // will be replaced with transaction id when we will receive ipn
                            $cart->setBillingtransactionid($profileId);
                        } else {
                            throw new \Exception("Failed to create recurring profile");
                        }
                    }
                }
            } catch (\Exception $e) {
                if (stripos(get_class($e), 'PHPUnit') !== false) {
                    throw $e;
                }
                $error = $this->processPayPalException($e, $cart);
                $session->remove('CreditCardInfo');

                if (!empty($directPaid)) {
                    $this->logger->critical("failed to create recurring paypal recurring payment", ["CartID" => $cart->getCartid(), "UserID" => $cart->getUser()->getUserid()]);
                    $session->remove('PayPalStep');

                    return new JsonResponse([
                        'status' => 'ok',
                        'url' => $this->router->generate('aw_cart_common_complete', ['id' => $cart->getCartid()]),
                    ]);
                }

                return new JsonResponse([
                    'success' => 0,
                    'error' => $error,
                ]);
            }

            // Complete
            $this->cartManager->markAsPayed($cart);

            $this->getDoctrine()->getManager()->flush();

            // Clear session
            $session->remove('PayPalStep');

            return new JsonResponse([
                'status' => 'ok',
                'url' => $this->router->generate('aw_cart_common_complete', ['id' => $cart->getCartid()]),
            ]);
        }
    }

    /**
     * @Route("/cancel", name="aw_cart_paypal_cancel")
     * @Security("is_granted('ROLE_USER')")
     * @Template("@AwardWalletMain/Cart/PayPal/cancel.html.twig")
     */
    public function cancelAction()
    {
        if (!$this->check()) {
            return $this->redirect($this->router->generate('aw_cart_common_paymenttype'));
        }

        return [];
    }

    /**
     * @Route("/accept", name="aw_cart_paypal_accept")
     * @Security("is_granted('ROLE_USER')")
     * @Template("@AwardWalletMain/Cart/PayPal/accept.html.twig")
     */
    public function acceptAction(Request $request)
    {
        if (!$this->check()) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'status' => 'fail',
                    'url' => $this->router->generate('aw_cart_common_paymenttype'),
                ]);
            } else {
                return $this->redirect($this->router->generate('aw_cart_common_paymenttype'));
            }
        }

        $cart = $this->cartManager->getCart();
        $session = $request->getSession();

        if ($request->isMethod("POST")) {
            $this->logger->info("paying", ["CartID" => $cart->getCartid(), "UserID" => $cart->getUser()->getUserid()]);
            $session->set('PayPalStep', 'complete');
            $session->set('PayPalToken', $request->query->get('token'));
            $session->set('PayPalPayerID', $request->query->get('PayerID'));

            return new JsonResponse([
                'status' => 'ok',
                'url' => $this->router->generate('aw_cart_paypal_process'),
            ]);
        }

        $this->logger->info("payment received", ["CartID" => $cart->getCartid(), "UserID" => $cart->getUser()->getUserid()]);

        try {
            $service = $this->paypalSoapApi->getPaypalServiceForCart($cart->isSandboxMode(), $cart);
            $getExpressCheckoutDetailsRequest = new GetExpressCheckoutDetailsRequestType($request->query->get('token'));
            $getExpressCheckoutReq = new GetExpressCheckoutDetailsReq();
            $getExpressCheckoutReq->GetExpressCheckoutDetailsRequest = $getExpressCheckoutDetailsRequest;

            $response = $service->GetExpressCheckoutDetails($getExpressCheckoutReq);
            $this->logger->info("Ack: " . $response->Ack, ["CartID" => $cart->getCartid(), "UserID" => $cart->getUser()->getUserid()]);
        } catch (\Exception $e) {
            $error = $this->processPayPalException($e, $cart);

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'status' => 'fail',
                    'error' => $error,
                ]);
            } else {
                throw $e;
            }
        }

        return [
            'FirstAndLastName' => $response->GetExpressCheckoutDetailsResponseDetails->PayerInfo->PayerName->FirstName . ' ' . $response->GetExpressCheckoutDetailsResponseDetails->PayerInfo->PayerName->LastName,
            'cart' => $cart,
            'names' => strval($cart),
        ];
    }

    /**
     * @Route("/cancel-recurring", name="aw_cart_cancel_recurring", methods={"POST"})
     * @Security("is_granted('ROLE_USER') and is_granted('CSRF')")
     */
    public function cancelRecurringAction()
    {
        $user = $this->tokenStorage->getBusinessUser();
        $profileID = $user->getPaypalrecurringprofileid();
        $result = $this->recurringManager->cancelRecurringPayment($user);

        if ($result) {
            $this->mainLogger->warning("canceled subscription", ["UserID" => $user->getUserid(), 'ProfileID' => $profileID]);
        }

        return new JsonResponse([
            'success' => $result,
            'redirect' => $this->router->generate('aw_user_subscription_lock_in_price'),
        ]);
    }

    protected function check()
    {
        $cart = $this->cartManager->getCart();

        if (
            !$cart->isPayPalPaymentType()
            || ($cart->getTotalPrice() == 0 && !$cart->isAwPlusSubscription())
            || $cart->recalcNeeded()
        ) {
            return false;
        }

        return true;
    }

    protected function preparePayPal(Cart $cart)
    {
        $service = $this->paypalSoapApi->getPaypalServiceForCart($cart->isSandboxMode(), $cart);
        $paymentDetails = $this->getPaymentDetails($cart);
        $setECReqDetails = new SetExpressCheckoutRequestDetailsType();
        $setECReqDetails->PaymentDetails[0] = $paymentDetails;
        $setECReqDetails->CancelURL = $this->router->generate('aw_cart_paypal_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $setECReqDetails->ReturnURL = $this->router->generate('aw_cart_paypal_accept', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $setECReqDetails->BuyerEmail = $this->getUser()->getEmail();
        $setECReqDetails->NoShipping = 1;

        if ($cart->hasItemsByType([AwPlusRecurring::TYPE, AwPlusSubscription::TYPE])) {
            $billingAgreementDetails = new BillingAgreementDetailsType('RecurringPayments');
            /** @var AwPlusSubscription $subscription */
            $subscription = $cart->getItemsByType([AwPlusSubscription::TYPE])->first();

            // if($subscription){
            //  $billingAgreementDetails->BillingAgreementDescription = (string)$subscription . ": " . $subscription->getQuantity();
            // }else{
            $billingAgreementDetails->BillingAgreementDescription = (string) $cart;
            // }

            $setECReqDetails->BillingAgreementDetails = [$billingAgreementDetails];
            $setECReqDetails->PaymentAction = 'Sale';
        }

        $setECReqType = new SetExpressCheckoutRequestType();
        $setECReqType->Version = '104.0';
        $setECReqType->SetExpressCheckoutRequestDetails = $setECReqDetails;
        $setECReq = new SetExpressCheckoutReq();
        $setECReq->SetExpressCheckoutRequest = $setECReqType;

        // PPHttpConfig::$DEFAULT_CURL_OPTS[CURLOPT_SSL_CIPHER_LIST] = 'DEFAULT@SECLEVEL=1';
        $response = $service->SetExpressCheckout($setECReq);

        return $response;
    }

    protected function processPayPal(Cart $cart, ?\Closure $itemFilter = null)
    {
        $session = $this->requestStack->getMasterRequest()->getSession();
        $token = $session->get('PayPalToken');
        $payer = $session->get('PayPalPayerID');

        if (!$token || !$payer) {
            throw new \Exception("Incorrect PayPal token or payer");
        }

        $service = $this->paypalSoapApi->getPaypalServiceForCart($cart->isSandboxMode(), $cart);
        $paymentDetails = $this->getPaymentDetails($cart, $itemFilter);

        $DoECRequestDetails = new DoExpressCheckoutPaymentRequestDetailsType();
        $DoECRequestDetails->PayerID = $payer;
        $DoECRequestDetails->Token = $token;
        $DoECRequestDetails->PaymentAction = 'Sale';
        $DoECRequestDetails->PaymentDetails[0] = $paymentDetails;

        $DoECRequest = new DoExpressCheckoutPaymentRequestType();
        $DoECRequest->DoExpressCheckoutPaymentRequestDetails = $DoECRequestDetails;

        $DoECReq = new DoExpressCheckoutPaymentReq();
        $DoECReq->DoExpressCheckoutPaymentRequest = $DoECRequest;

        $response = $service->DoExpressCheckoutPayment($DoECReq);
        $cart->setBillingtransactionid($response->DoExpressCheckoutPaymentResponseDetails->PaymentInfo[0]->TransactionID);
        $this->logger->info("paypal success", ["BillingTransactionID" => $cart->getBillingtransactionid(), "UserID" => $cart->getUser()->getUserid(), "CartID" => $cart->getCartid(), "response" => TraceProcessor::filterArguments($response)]);
        $this->getDoctrine()->getManager()->persist($cart);
        $this->getDoctrine()->getManager()->flush();
    }

    protected function getSubscriptionParams(Cart $cart)
    {
        $subscriptionItem = $cart->getSubscriptionItem();

        if (empty($subscriptionItem)) {
            return null;
        }

        if (!empty($subscriptionItem->getScheduledDate())) {
            $startDate = clone $subscriptionItem->getScheduledDate();
        } else {
            $startDate = new \DateTime();
        }

        if ($subscriptionItem::DURATION === SubscriptionPeriod::DURATION_1_YEAR) {
            $frequency = 12;
        } elseif ($subscriptionItem::DURATION === SubscriptionPeriod::DURATION_6_MONTHS) {
            $frequency = 6;
        } elseif ($subscriptionItem::DURATION === SubscriptionPeriod::DURATION_1_MONTH) {
            $frequency = 1;
        } else {
            throw new \Exception("unknown duration for paypal payment type: " . $subscriptionItem::DURATION . ", " . get_class($subscriptionItem));
        }

        $result = [
            'cart' => $cart,
            'period' => 'Month',
            'frequency' => $frequency,
            'startDate' => $startDate,
            'amount' => $subscriptionItem->getPrice(),
            'description' => (string) $cart,
        ];

        $discount = $cart->getDiscount();

        if (!empty($discount)) {
            $result['amount'] -= abs($discount->getPrice());
        }

        //        $debug = $this->get('kernel')->isDebug();
        //        if ($debug){
        //            $result['startDate'] = new \DateTime('+1 hour');
        //            $result['frequency'] = 1;
        //            $result['period'] = 'Day';
        //            $result['amount'] = 2;
        //            if(isset($result['trialAmount'])) {
        //                $result['trialAmount'] = 1;
        //            }
        //        }

        return $result;
    }

    /**
     * Options:
     *  array['cart'] Cart
     *  array['amount'] float
     *  array['trialAmount'] float
     *  array['period'] string
     *  array['frequency'] integer
     *  array['paymentInfo'] array
     *  array['description'] string.
     */
    protected function createRecurringPayment(array $options)
    {
        if (!isset($options['cart'])) {
            $options['cart'] = $this->cartManager->getCart();
        }
        // $startdate = date("Y-m-d\TH:i:s\Z", strtotime("+".$options['frequency']." ".$options['period']));
        $startdate = date("Y-m-d\TH:i:s\Z", $options['startDate']->getTimestamp());

        $RPProfileDetails = new RecurringPaymentsProfileDetailsType();
        $RPProfileDetails->BillingStartDate = $startdate;

        $paymentBillingPeriod = new BillingPeriodDetailsType();
        $paymentBillingPeriod->BillingFrequency = $options['frequency'];
        $paymentBillingPeriod->BillingPeriod = $options['period'];
        $paymentBillingPeriod->Amount = new BasicAmountType('USD', $options['amount']);

        $scheduleDetails = new ScheduleDetailsType();
        $scheduleDetails->PaymentPeriod = $paymentBillingPeriod;
        $scheduleDetails->Description = $options['description'];
        $scheduleDetails->MaxFailedPayments = 3;

        if (!empty($options['trialAmount'])) {
            $trialPeriod = new BillingPeriodDetailsType();
            $trialPeriod->BillingFrequency = $options['frequency'];
            $trialPeriod->BillingPeriod = $options['period'];
            $trialPeriod->TotalBillingCycles = 1;
            $trialPeriod->Amount = new BasicAmountType('USD', $options['trialAmount']);
            $scheduleDetails->TrialPeriod = $trialPeriod;
        }

        $createRPProfileRequestDetail = new CreateRecurringPaymentsProfileRequestDetailsType();

        if (isset($options['paymentInfo']['Token'])) {
            $createRPProfileRequestDetail->Token = $options['paymentInfo']['Token'];
        } else {
            $personName = new PersonNameType();
            $personName->FirstName = $options['paymentInfo']['FirstName'];
            $personName->LastName = $options['paymentInfo']['LastName'];

            $payerInfo = new PayerInfoType();
            $payerInfo->PayerName = $personName;
            $payerInfo->PayerCountry = $options['paymentInfo']['Country'];

            $creditCard = new CreditCardDetailsType();
            $creditCard->CardOwner = $payerInfo;
            $creditCard->CreditCardType = $options['paymentInfo']['CreditCardType'];
            $creditCard->CreditCardNumber = $options['paymentInfo']['CreditCardNumber'];
            $creditCard->CVV2 = $options['paymentInfo']['CVV2'];
            $creditCard->ExpMonth = str_pad($options['paymentInfo']['ExpMonth'], 2, '0', STR_PAD_LEFT);
            $creditCard->ExpYear = $options['paymentInfo']['ExpYear'];

            $createRPProfileRequestDetail->CreditCard = $creditCard;
        }
        $createRPProfileRequestDetail->RecurringPaymentsProfileDetails = $RPProfileDetails;
        $createRPProfileRequestDetail->ScheduleDetails = $scheduleDetails;

        $createRPProfileRequest = new CreateRecurringPaymentsProfileRequestType();
        $createRPProfileRequest->CreateRecurringPaymentsProfileRequestDetails = $createRPProfileRequestDetail;

        $createRPProfileReq = new CreateRecurringPaymentsProfileReq();
        $createRPProfileReq->CreateRecurringPaymentsProfileRequest = $createRPProfileRequest;

        $service = $this->paypalSoapApi->getPaypalServiceForCart($options['cart']->isSandboxMode(), $options['cart']);
        $response = $service->CreateRecurringPaymentsProfile($createRPProfileReq);
        $this->logger->info("paypal response: " . json_encode($response));
        $this->logger->info("paypal response ack: " . $response->Ack);

        switch ($response->Ack) {
            case 'SuccessWithWarning':
                $errors = isset($response->Errors) ? $this->ppErrorsToString($response->Errors) : null;
                $this->mainLogger->addError("Create recurring profile with warning", [$errors]);

                // no break
            case 'Success':
                return $response->CreateRecurringPaymentsProfileResponseDetails->ProfileID;

                break;

            default:
                throw new \Exception($this->ppErrorsToString($response->Errors));

                break;
        }

        return null;
    }

    protected function getPaymentDetails(Cart $cart, ?\Closure $itemFilter = null)
    {
        $user = $cart->getUser();
        $currencyID = 'USD';
        $paymentDetails = new PaymentDetailsType();
        $itemTotalValue = 0;
        $expiration = $this->expirationCalculator->getAccountExpiration($user->getId());
        $context = ["CartID" => $cart->getCartid(), "UserID" => $user->getId()];
        $contents = array_map(function (CartItem $item) {
            return [["Type" => basename(str_replace('\\', '/', get_class($item))), "Cnt" => $item->getCnt(), "Price" => $item->getPrice(), "Name" => $item->getName()]];
        }, $cart->getItems()->toArray());
        $havePrepaidItem = $cart->hasPrepaidAwPlusSubscription();
        $this->logger->warning(
            'preparing payment details',
            array_merge($context, $expiration, [
                "IsAwPlusSubscription" => $cart->isAwPlusSubscription(),
                "AccountLevel" => $user->getAccountlevel(),
                "PlusExpirationDate" => $user->getPlusExpirationDate(),
                "Items" => $contents,
            ])
        );

        foreach ($cart->getItems() as $i => $cartItem) {
            if ($havePrepaidItem && $cartItem instanceof AwPlusSubscription) {
                continue;
            }

            if (!empty($itemFilter) && !$itemFilter($cartItem)) {
                continue;
            }

            $itemAmount = new BasicAmountType(
                $currencyID,
                $cartItem instanceof AwPlusPrepaid ? $cartItem->getPrice() * $cartItem->getCnt() : $cartItem->getPrice()
            );
            $itemTotalValue += $cartItem->getTotalPrice();

            $itemDetails = new PaymentDetailsItemType();
            $itemDetails->Name = $cartItem->getName() . ' Order #' . $cart->getCartid();
            $itemDetails->Amount = $itemAmount;
            $itemDetails->Quantity = $cartItem instanceof OneCard ? $cartItem->getQuantity() : 1;

            $paymentDetails->PaymentDetailsItem[$i] = $itemDetails;
        }
        $orderTotalValue = $itemTotalValue;
        $paymentDetails->ItemTotal = new BasicAmountType($currencyID, $itemTotalValue);
        $paymentDetails->OrderTotal = new BasicAmountType($currencyID, $orderTotalValue);
        $paymentDetails->OrderDescription = 'Order #' . $cart->getCartid();
        $paymentDetails->InvoiceID = $cart->getCartid() . 'paid on ' . date("Y-m-d H:i:s");
        $this->logger->warning("payment details", array_merge($context, ["details" => $paymentDetails]));

        return $paymentDetails;
    }
}
