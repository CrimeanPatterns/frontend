<?php

namespace AwardWallet\MainBundle\Controller\Cart;

use AwardWallet\MainBundle\Entity\AbInvoice;
use AwardWallet\MainBundle\Entity\Billingaddress;
use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusGift;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusRecurring;
use AwardWallet\MainBundle\Entity\CartItem\BalanceWatchCredit;
use AwardWallet\MainBundle\Entity\CartItem\Booking;
use AwardWallet\MainBundle\Entity\CartItem\Discount;
use AwardWallet\MainBundle\Entity\Repositories\CartRepository;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Type\CardInfoType;
use AwardWallet\MainBundle\Form\Type\Cart\BillingAddressType;
use AwardWallet\MainBundle\Form\Type\Cart\SelectPaymentType;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorage;
use AwardWallet\MainBundle\Globals\Cart\CartUserInfo;
use AwardWallet\MainBundle\Globals\Cart\CartUserSource;
use AwardWallet\MainBundle\Globals\Cart\CreditCardPaymentTypeSelector;
use AwardWallet\MainBundle\Globals\Cart\Manager;
use AwardWallet\MainBundle\Globals\Cart\UpgradeCodeGenerator;
use AwardWallet\MainBundle\Manager\LogoManager;
use AwardWallet\MainBundle\Security\AntiBruteforceLockerService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraints\CardScheme;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route("/cart")
 */
class CommonController extends AbstractController
{
    private Manager $cartManager;
    private LogoManager $logoManager;
    private LoggerInterface $logger;
    private EntityManagerInterface $em;
    private RouterInterface $router;
    private CartUserSource $cartUserSource;
    private bool $isDebug;
    private ValidatorInterface $validator;

    public function __construct(
        Manager $cartManager,
        LogoManager $logoManager,
        LoggerInterface $logger,
        EntityManagerInterface $em,
        RouterInterface $router,
        CartUserSource $cartUserSource,
        ValidatorInterface $validator,
        bool $isDebug
    ) {
        $this->cartManager = $cartManager;
        $this->logoManager = $logoManager;
        $this->logger = $logger;
        $this->em = $em;
        $this->router = $router;
        $this->cartUserSource = $cartUserSource;
        $this->isDebug = $isDebug;
        $this->validator = $validator;
    }

    /**
     * @Route("/change-payment/{userId}/{hash}", requirements={"userId" = "\d+"}, name="aw_cart_change_payment_method_email")
     * @Route("/change-payment", name="aw_cart_change_payment_method_authorized")
     * @Route("/paypal/change-payment", name="aw_cart_change_payment_method_old")
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function changePaymentMethodAction(
        ?int $userId = null,
        ?string $hash = null,
        UpgradeCodeGenerator $upgradeCodeGenerator,
        UsrRepository $users,
        RouterInterface $router,
        AwTokenStorage $tokenStorage,
        LoggerInterface $paymentLogger,
        \Memcached $memcached,
        Request $request,
        Manager $cartManager,
        CartRepository $cartRepository
    ) {
        $loginLink = $router->generate("aw_login", ["BackTo" => $this->router->generate("aw_cart_change_payment_method_authorized")]);
        $user = $tokenStorage->getUser();
        $cartUserInfo = null;

        if (is_null($user)) {
            if ($userId === null || $hash === null) {
                return new RedirectResponse($loginLink);
            }

            $user = $users->find($userId);

            if ($user === null) {
                $paymentLogger->warning("user {$userId} not found");

                return new RedirectResponse($loginLink);
            }

            $locker = new AntiBruteforceLockerService($memcached, "payment_method_hash_", 60, 5, 10, "Invalid hash", $paymentLogger);

            if ($locker->checkForLockout($request->getClientIp()) !== null || $locker->checkForLockout((string) $userId) !== null) {
                $paymentLogger->warning("hash lockout for user {$userId}");

                return new RedirectResponse($loginLink);
            }

            if ($hash !== $upgradeCodeGenerator->generateCode($user)) {
                $paymentLogger->warning("hash mismatch for user {$userId}");

                return new RedirectResponse($loginLink);
            }

            $cartUserInfo = new CartUserInfo($user->getId(), $user->getId(), true);
        }

        $cart = $cartManager->createNewCart($cartUserInfo);
        // we repeat last subscription, no matter is it now active or not
        $subscription = $cartRepository->getActiveAwSubscription($user, false);

        if ($subscription && !is_null($subscription->getAT201Item())) {
            $cart
                ->clear()
                ->setCalcDate(new \DateTime());

            $cartManager->addAT201SubscriptionItem($cart, $subscription->getAT201Item()::DURATION);
            $cart->setPaymenttype(Cart::PAYMENTTYPE_STRIPE_INTENT);
            $cartManager->save($cart);

            return $this->redirect($router->generate('aw_cart_stripe_orderdetails'));
        }

        $cartManager->fillCart($cart, $user, 0, true);
        $cartManager->save($cart);

        if ($cartUserInfo === null) {
            return $this->redirect($router->generate('aw_cart_common_paymenttype'));
        }

        $cart->setPaymenttype(Cart::PAYMENTTYPE_STRIPE_INTENT);
        $cartManager->save($cart);

        return $this->redirect($router->generate('aw_cart_stripe_orderdetails'));
    }

    /**
     * @Route("/paymentType", name="aw_cart_common_paymenttype", options={"expose"=false})
     * @Security("is_granted('ROLE_USER')")
     * @Template("@AwardWalletMain/Cart/Common/paymentType.html.twig")
     */
    public function paymentTypeAction(Request $request, AuthorizationCheckerInterface $authorizationChecker, CreditCardPaymentTypeSelector $paymentTypeSelector)
    {
        $queryParameters = $this->fetchQueryParameters($request);
        $cart = $this->cartManager->getCart();

        if (!sizeof($cart->getItems())) {
            return $this->redirect($this->router->generate('aw_account_list'));
        }

        if ($cart->recalcNeeded()) {
            return $this->redirect($this->router->generate('aw_users_pay', $queryParameters));
        }

        if ($cart->getTotalPrice() == 0 && !$cart->isAwPlusSubscription()) {
            $cart->removeItemsByType([AwPlusRecurring::TYPE]);
            $cart->setPaymenttype(null);
            $this->cartManager->markAsPayed($cart);
            $queryParameters['id'] = $cart->getCartid();

            return $this->redirect($this->router->generate('aw_cart_common_complete', $queryParameters));
        }

        $backTo = $request->query->get('backTo');
        $entryPoint = $request->query->get('entry');
        $form = $this->createForm(SelectPaymentType::class, ['type' => $paymentTypeSelector->getCreditCardPaymentType($cart)], [
            'cart' => $cart,
            'debug' => $this->isDebug,
            'staff' => $authorizationChecker->isGranted('ROLE_STAFF'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $cart->setPaymenttype($form->getData()['type']);
            $this->cartManager->save($cart);

            switch ($cart->getPaymenttype()) {
                case Cart::PAYMENTTYPE_CREDITCARD:
                case Cart::PAYMENTTYPE_TEST_CREDITCARD:
                    return $this->redirect($this->router->generate('aw_cart_common_orderdetails', $queryParameters));

                case Cart::PAYMENTTYPE_STRIPE_INTENT:
                    return $this->redirect($this->router->generate('aw_cart_stripe_orderdetails', $queryParameters));

                case Cart::PAYMENTTYPE_PAYPAL:
                case Cart::PAYMENTTYPE_TEST_PAYPAL:
                    return $this->redirect($this->router->generate('aw_cart_paypal_prepare', $queryParameters));

                case Cart::PAYMENTTYPE_ETHEREUM:
                case Cart::PAYMENTTYPE_BITCOIN:
                    $queryParameters['cartId'] = $cart->getCartid();

                    return $this->redirect($this->router->generate('aw_cart_coinpayments_prepare', $queryParameters));

                    break;
            }
        }

        if ($authorizationChecker->isGranted('SITE_BUSINESS_AREA')) {
            $payBack = $this->generateUrl('aw_business_pay', ['back' => 1]);
        } elseif ($cart->hasItemsByType([BalanceWatchCredit::TYPE]) && (1 === \count($cart->getItems()) || 2 === \count($cart->getItems()) && $cart->hasItemsByType([Discount::TYPE]))) {
            $payBack = $this->generateUrl('aw_users_pay_balancewatchcredit', ['back' => 1]);
        } elseif ($cart->hasItemsByType([AwPlusGift::TYPE])) {
            $payBack = $this->generateUrl('aw_user_giftawplus', ['back' => 1]);
        } elseif (is_string($backTo) && !empty($backTo) && $entryPoint === md5('aw_cart_common_paymenttype')) {
            $payBack = $backTo;
        } elseif (!is_null($cart->getCoupon()) && $cart->getCoupon()->getFirsttimeonly()) {
            $payBack = $this->generateUrl('aw_users_usecoupon');
        } elseif (is_string($backTo) && empty($backTo)) {
            $payBack = null;
        } else {
            $payBack = $this->generateUrl('aw_users_pay', ['back' => 1]);
        }

        return [
            'form' => $form->createView(),
            'cart' => $cart,
            'payBack' => $payBack,
        ];
    }

    /**
     * @Route("/complete/{id}", name="aw_cart_common_complete", requirements={"id" = "\d+"}, options={"expose"=true})
     * @Security("is_granted('VIEW', cart)")
     * @ParamConverter("cart", class="AwardWalletMainBundle:Cart")
     * @Template("@AwardWalletMain/Cart/Common/complete.html.twig")
     */
    public function completeAction(Request $request, Cart $cart)
    {
        if (empty($cart->getPaydate())) {
            throw $this->createAccessDeniedException();
        }

        // detect booking request
        if ($cart->hasItemsByType([Booking::TYPE])) {
            /** @var Booking $item */
            $item = $cart->getItems()->first();

            if ($item instanceof Booking && $item->getInvoiceId()) {
                /** @var AbInvoice $invoice */
                $invoice = $this->em->getRepository(\AwardWallet\MainBundle\Entity\AbInvoice::class)->find($item->getInvoiceId());

                if ($invoice) {
                    $requestId = $invoice->getMessage()->getRequest()->getAbRequestID();
                    $this->logoManager->setBookingRequest($invoice->getMessage()->getRequest());
                }
            }
        }
        // track order
        $user = $this->cartUserSource->getCartOwner();
        $country = "";

        if (!empty($cart->getBillcountry())) {
            $country = $cart->getBillcountry()->getName();
        } else {
            if (!empty($user->getCountryid())) {
                $country = $this->lookup("Country", "Name", "CountryID", $user->getCountryid());
            }
        }
        $state = "";

        if (!empty($cart->getBillstate())) {
            $state = $cart->getBillstate()->getName();
        } else {
            if (!empty($user->getStateid())) {
                $state = $this->lookup("State", "Name", "StateID", $user->getStateid());
            }
        }
        $city = "";

        if (!empty($cart->getBillcity())) {
            $city = $cart->getBillcity();
        } else {
            if (!empty($user->getCity())) {
                $city = $user->getCity();
            }
        }

        $scheduledPayment = function (Cart $cart) {
            foreach ($cart->getItems() as $item) {
                if (!empty($item->getScheduledDate())) {
                    return $item->getScheduledDate();
                }
            }

            return null;
        };

        $mobileSubscrWarning = null;

        if (true === $request->getSession()->get(Usr::TURN_OFF_IOS_SUBSCRIPTION_WARNING)) {
            $mobileSubscrWarning = 'ios';
        }

        return [
            'cart' => $cart,
            'payer' => $this->cartUserSource->getPayer(),
            'scheduledPayment' => $scheduledPayment($cart),
            'manager' => $this->cartManager,
            'bookingRequestId' => $requestId ?? null,
            'country' => $country,
            'state' => $state,
            'city' => $city,
            'ganalytic' => $cart->getItemsForGA($invoice ?? null),
            'mobileSubscrWarning' => $mobileSubscrWarning,
        ];
    }

    /**
     * @Route("/order/details", name="aw_cart_common_orderdetails", options={"expose"=false})
     * @Security("is_granted('USER_PAYER')")
     * @Template("@AwardWalletMain/Cart/Common/orderDetails.html.twig")
     */
    public function orderDetailsAction(Request $request)
    {
        $queryParameters = $this->fetchQueryParameters($request);
        $cart = $this->cartManager->getCart();

        // TODO: check payment type && total <> 0
        if (!$cart->isCreditCardPaymentType() || $cart->getTotalPrice() == 0 || $cart->recalcNeeded()) {
            return $this->redirect($this->router->generate('aw_users_pay', $queryParameters));
        }

        $cardInfoForm = $this->createForm(CardInfoType::class);
        $result = [
            'cart' => $cart,
        ];

        // kept for future onecard ordering with shipping address, we will merge shipping and billing addresses
        $showAddresses = false;

        if ($showAddresses) {
            $billingType = new Billingaddress();
            $billingType->setAddressname('Name'); // TODO Need refactoring
            $billingForm = $this->createForm(BillingAddressType::class, $billingType);
            $addresses = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Billingaddress::class)->findBy(['userid' => $user->getUserid()]);
            $result['billingAddresses'] = $addresses;

            if (!count($addresses)) {
                $result['billingForm'] = $billingForm->createView();
            } else {
                $result['billingAddresses'] = $addresses;

                if ($request->getSession()->has('billing.address')) {
                    $billingAddress = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Billingaddress::class)->find($request->getSession()->get('billing.address'));

                    if (!$billingAddress) {
                        $request->getSession()->remove('billing.address');
                        $billingAddress = end($addresses);
                    }
                } else {
                    $billingAddress = end($addresses);
                }
                $result['selectedAddress'] = $billingAddress;
            }
        }

        $this->logoManager->setBooker($this->cartManager->getBooker($cart));

        if ('POST' === $request->getMethod()) {
            $cardInfoForm->handleRequest($request);
            $this->logFormErrors('card info form is invalid', $cardInfoForm);

            if ($request->request->has('billing_address') && isset($billingForm)) {
                $billingForm->handleRequest($request);
                $this->logFormErrors('billing form is invalid', $billingForm);

                if ($billingForm->isSubmitted() && $billingForm->isValid() && $cardInfoForm->isSubmitted() && $cardInfoForm->isValid()) { // Save billing only if credit card is valid
                    $billingAddress = $billingForm->getData();
                    $billingAddress->setUserid($user);
                    $this->em->persist($billingAddress);
                    $this->em->flush();
                    $result['selectedAddress'] = $billingAddress;
                    unset($result['billingForm']);
                } else {
                    $result['billingForm'] = $billingForm->createView();
                    $result['cardInfoForm'] = $cardInfoForm->createView();

                    return $result;
                }
            }

            if ($cardInfoForm->isSubmitted() && $cardInfoForm->isValid()) {
                $cardData = $cardInfoForm->getData();
                $nameParts = explode(" ", trim($cardData['full_name']));
                $cardData['first_name'] = array_shift($nameParts);
                $cardData['last_name'] = join(' ', $nameParts);

                $cardNumber = $cardData['card_number'];
                $cardType = $this->detectCardType($cardNumber);

                $request->getSession()->set('CreditCardInfo', [
                    'cardType' => $cardType,
                    'cardNumber' => $cardData['card_number'],
                    'securityCode' => $cardData['security_code'],
                    'expirationMonth' => $cardData['expiration_month'],
                    'expirationYear' => $cardData['expiration_year'],
                ]);

                $cart->setCreditcardtype($cardType);
                $cart->setCreditcardnumber(str_repeat('X', strlen($cardNumber) - 4) . substr($cardNumber, -4));

                $cart->setBillfirstname($cardData['first_name']);
                $cart->setBilllastname($cardData['last_name']);

                if (isset($billingAddress)) {
                    /** @var Billingaddress $billingAddress */
                    $request->getSession()->set('billing.address', $billingAddress->getBillingaddressid());
                    $cart->setBillcountry($billingAddress->getCountryid());
                    $cart->setBillzip($billingAddress->getZip());
                    $cart->setBillstate($billingAddress->getStateid());
                    $cart->setBillcity($billingAddress->getCity());
                    $cart->setBilladdress1($billingAddress->getAddress1());
                    $cart->setBilladdress2($billingAddress->getAddress2());
                }

                $this->cartManager->save($cart);

                return $this->redirect($this->router->generate('aw_cart_common_orderpreview', $queryParameters));
            }
        }

        $result['cardInfoForm'] = $cardInfoForm->createView();
        $result['showBackButton'] = null === $cart->getAT201Item();
        $result['changePaymentMethod'] = $this->cartManager->isChangingPaymentMethod($cart);

        return $result;
    }

    /**
     * @Route("/order/preview", name="aw_cart_common_orderpreview", options={"expose"=false})
     * @Security("is_granted('USER_PAYER')")
     * @Template("@AwardWalletMain/Cart/Common/orderPreview.html.twig")
     */
    public function orderPreviewAction(Request $request)
    {
        $queryParameters = $this->fetchQueryParameters($request);
        $cart = $this->cartManager->getCart();
        $session = $request->getSession();

        // TODO: check payment type && total <> 0
        if (!$cart->isCreditCardPaymentType() || $cart->getTotalPrice() == 0 || $cart->recalcNeeded()) {
            return $this->redirect($this->router->generate('aw_cart_common_paymenttype', $queryParameters));
        }

        if (!$session->has('CreditCardInfo')) {
            return $this->redirect($this->router->generate('aw_cart_common_orderdetails', $queryParameters));
        }

        if ($cart->getPaymenttype() === Cart::PAYMENTTYPE_RECURLY) {
            $url = $this->router->generate("aw_cart_recurly_checkout", $queryParameters);
        } elseif ($cart->getPaymenttype() === Cart::PAYMENTTYPE_STRIPE) {
            $url = $this->router->generate("aw_cart_recurly_checkout", $queryParameters);
        } else {
            $url = $this->router->generate("aw_cart_creditcard_checkout", $queryParameters);
        }

        return [
            'cart' => $cart,
            'url' => $url,
        ];
    }

    public static function fetchQueryParameters(Request $request): array
    {
        $params = [];
        $request->query->has('forceId') ? $params['forceId'] = (int) $request->query->get('forceId') : null;

        if (
            $request->query->has('backTo')
            && is_string($backTo = $request->query->get('backTo'))
            && !empty($backTo)
        ) {
            $params['backTo'] = $backTo;
            $params['entry'] = $request->query->get('entry');
        }

        return $params;
    }

    /**
     * TODO: use foreign keys.
     *
     * @param null $default
     * @return null
     */
    private function lookup($table, $select, $whereField, $whereValue, $default = null)
    {
        $stmt = $this->em->getConnection()->prepare("SELECT $select FROM $table WHERE $whereField = :value");
        $stmt->bindParam(':value', $whereValue, \PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($row === false) {
            return $default;
        }

        return $row[$select];
    }

    private function detectCardType($cardNumber)
    {
        $cardType = null;

        if (!(string) $this->validator->validate($cardNumber, new CardScheme('AMEX'))) {
            $cardType = 'Amex';
        } elseif (!(string) $this->validator->validate($cardNumber, new CardScheme('VISA'))) {
            $cardType = 'Visa';
        } elseif (!(string) $this->validator->validate($cardNumber, new CardScheme('DISCOVER'))) {
            $cardType = 'Discover';
        } elseif (!(string) $this->validator->validate($cardNumber, new CardScheme('MASTERCARD'))) {
            $cardType = 'MasterCard';
        }

        return $cardType;
    }

    private function logFormErrors(string $msg, FormInterface $form)
    {
        if (!$form->isSubmitted() || $form->isValid() || count($errors = $form->getErrors(true, false)) === 0) {
            return;
        }

        $this->logger->info(sprintf('%s, %s', $msg, (string) $errors));
    }
}
