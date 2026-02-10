<?php

namespace AwardWallet\MainBundle\Controller\Cart;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusGift;
use AwardWallet\MainBundle\Entity\Repositories\CartRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\Cart\CartUserSource;
use AwardWallet\MainBundle\Globals\Cart\CreditCardPaymentTypeSelector;
use AwardWallet\MainBundle\Globals\Cart\Manager;
use AwardWallet\MainBundle\Manager\LogoManager;
use AwardWallet\MainBundle\Service\Billing\MobilePaymentWarningChecker;
use AwardWallet\MainBundle\Service\Billing\RecurringManager;
use AwardWallet\MainBundle\Service\Billing\StripeOffSessionCharger;
use AwardWallet\MainBundle\Service\Billing\StripePaymentMethodHelper;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Stripe\Customer;
use Stripe\Exception\CardException;
use Stripe\SetupIntent;
use Stripe\Stripe;
use Stripe\StripeClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

class StripeIntentController extends AbstractController
{
    private const SESSION_ERROR_KEY = 'stripe_payment_error';
    private const SESSION_CUSTOMER_KEY = 'stripe_customer_id';

    private Manager $cartManager;
    private RouterInterface $router;
    private LogoManager $logoManager;
    private StripeClient $stripe;
    private string $stripePublishableKey;
    private LoggerInterface $logger;
    private SessionInterface $session;
    private CartUserSource $cartUserSource;
    private RecurringManager $recurringManager;
    private CreditCardPaymentTypeSelector $paymentTypeSelector;
    private MobilePaymentWarningChecker $mobilePaymentWarningChecker;
    private StripeOffSessionCharger $charger;
    private StripePaymentMethodHelper $paymentMethodHelper;

    public function __construct(
        Manager $cartManager,
        RouterInterface $router,
        LogoManager $logoManager,
        StripeClient $stripe,
        string $stripePublishableKey,
        LoggerInterface $paymentLogger,
        SessionInterface $session,
        CartUserSource $cartUserSource,
        RecurringManager $recurringManager,
        CreditCardPaymentTypeSelector $paymentTypeSelector,
        MobilePaymentWarningChecker $mobilePaymentWarningChecker,
        StripeOffSessionCharger $charger,
        StripePaymentMethodHelper $paymentMethodHelper
    ) {
        $this->cartManager = $cartManager;
        $this->router = $router;
        $this->logoManager = $logoManager;
        $this->stripe = $stripe;
        $this->stripePublishableKey = $stripePublishableKey;
        $this->logger = $paymentLogger;
        $this->session = $session;
        $this->cartUserSource = $cartUserSource;
        $this->recurringManager = $recurringManager;
        $this->paymentTypeSelector = $paymentTypeSelector;
        $this->mobilePaymentWarningChecker = $mobilePaymentWarningChecker;
        $this->charger = $charger;
        $this->paymentMethodHelper = $paymentMethodHelper;
    }

    /**
     * @Route("/order/stripe-details", name="aw_cart_stripe_orderdetails", options={"expose"=false})
     * @Security("is_granted('USER_PAYER')")
     */
    public function orderDetailsAction(
        Request $request,
        Environment $twig,
        LoggerInterface $paymentLogger,
        SessionInterface $session,
        CartRepository $cartRepository
    ) {
        $queryParameters = CommonController::fetchQueryParameters($request);
        $cart = $this->cartManager->getCart();

        if (!$this->validCart($cart)) {
            return $this->redirect($this->router->generate('aw_users_pay', $queryParameters));
        }

        $backTo = $request->query->get('backTo');
        $entryPoint = $request->query->get('entry');

        if (!is_string($backTo) || empty($backTo) || md5('aw_cart_stripe_orderdetails') !== $entryPoint) {
            $backTo = null;
        }

        if (!isset($backTo) && is_null($cart->getAT201Item())) {
            $backTo = $this->router->generate('aw_cart_common_paymenttype', [
                'backTo' => $request->query->get('backTo'),
                'entry' => $request->query->get('entry'),
            ], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        $user = $cart->getUser();
        $currentSubscription = $cartRepository->getActiveAwSubscription($user);
        $isCartContainsSubscription = $cart->isSubscription();
        $this->logger->info("cart {$cart->getCartid()}, contains subscription: " . json_encode($isCartContainsSubscription) . ", items: " . join(", ", array_map(fn (CartItem $item) => get_class($item), $cart->getItems()->toArray())));
        $queryParameters['cartId'] = $cart->getCartid();

        $templateParams = [
            'cart' => $cart,
            'backToUrl' => $backTo,
            'hasAppleSubscription' => $currentSubscription
                && $user->getSubscription() == Usr::SUBSCRIPTION_MOBILE
                && $currentSubscription->getPaymenttype() == Cart::PAYMENTTYPE_APPSTORE,
            'changePaymentMethod' => $this->cartManager->isChangingPaymentMethod($cart)
                && !$cart->hasPrepaidAwPlusSubscription(),
            'stripePublishableKey' => $this->stripePublishableKey,
            'returnUrl' => $this->router->generate('aw_cart_stripe_returned', $queryParameters, RouterInterface::ABSOLUTE_URL),
            'subscription' => $isCartContainsSubscription,
        ];

        if ($session->has(self::SESSION_ERROR_KEY)) {
            $paymentLogger->info("returned from error");
            $session->remove(self::SESSION_ERROR_KEY);
            $templateParams['error'] = true;
        }

        $this->logoManager->setBooker($this->cartManager->getBooker($cart));

        Stripe::setLogger($paymentLogger);

        $customer = $this->getCartCustomer($cart);

        if ($isCartContainsSubscription) {
            $templateParams['stripeClientSecret'] = $this->createSetupIntent($cart, $customer);
        } else {
            $templateParams['stripeClientSecret'] = $this->createPaymentIntent($cart, $customer, $templateParams['returnUrl']);
        }

        return new Response($twig->render('@AwardWalletMain/Cart/Common/stripeDetails.html.twig', $templateParams));
    }

    /**
     * @Route("/order/stripe-returned", name="aw_cart_stripe_returned", options={"expose"=false})
     * @Security("is_granted('USER_PAYER')")
     */
    public function returnUrlAction(Request $request,
        LoggerInterface $paymentLogger
    ) {
        $cartId = $request->query->getInt('cartId');

        if ($cartId <= 0) {
            $cartId = null;
            $this->logger->warning("ignoring incorrect cartId from query string: {$request->query->getAlnum('cartId')}");
        }

        $cart = $this->cartManager->getCart($cartId);
        $queryParameters = CommonController::fetchQueryParameters($request);
        $paymentLogger->info("received stripe return for cart: {$cart->getCartid()}");

        if (!$this->validCart($cart)) {
            return $this->redirect($this->router->generate('aw_users_pay', $queryParameters));
        }

        if ($cart->getPaymenttype() !== Cart::PAYMENTTYPE_STRIPE_INTENT || $cart->getTotalPrice() == 0 || $cart->recalcNeeded() || $cart->isPaid()) {
            return $this->redirect($this->router->generate('aw_users_pay', $queryParameters));
        }

        $this->logger->info("cart {$cart->getCartid()}, contains subscription: " . json_encode($cart->isSubscription()) . ", items: " . join(", ", array_map(fn (CartItem $item) => get_class($item), $cart->getItems()->toArray())));

        if ($cart->isSubscription()) {
            $error = $this->processSetupIntent($request->query->get("setup_intent"), $cart);
        } else {
            $error = $this->processPaymentIntent($request->query->get("payment_intent"), $cart);
        }

        if ($error) {
            $this->session->set(self::SESSION_ERROR_KEY, $error);

            return new RedirectResponse($this->router->generate("aw_cart_stripe_orderdetails", $queryParameters));
        }

        return new RedirectResponse($this->router->generate(
            "aw_cart_common_complete",
            array_merge($queryParameters, ["id" => $cart->getCartid()])
        ));
    }

    private function getCartCustomer(Cart $cart): Customer
    {
        $payingUser = $this->cartUserSource->getPayer() ?? $cart->getUser();

        if ($payingUser && $payingUser->getStripeCustomerId()) {
            $this->logger->info("loading existing customer: " . $payingUser->getStripeCustomerId());

            return $this->stripe->customers->retrieve($payingUser->getStripeCustomerId());
        }

        if ($payingUser) {
            $this->logger->info("creating new customer for user {$payingUser->getId()}");
            $customer = $this->stripe->customers->create([
                'name' => $payingUser->getFullName(),
                'metadata' => ["user_id" => $payingUser->getId(), "cart_id" => $cart->getCartid()],
                'email' => $payingUser->getEmail(),
            ]);
            $this->logger->info("created customer for user {$payingUser->getId()}: {$customer->id}");
            $payingUser->setStripeCustomerId($customer->id);
            $this->cartManager->save($cart);

            return $customer;
        }

        if ($this->session->has(self::SESSION_CUSTOMER_KEY)) {
            $this->logger->info("loading existing anonymous customer from session");
            $customer = $this->stripe->customers->retrieve($this->session->get(self::SESSION_CUSTOMER_KEY));
            $this->logger->info("loaded existing anonymous customer from session: " . $customer->id);

            return $customer;
        }

        $this->logger->info("creating customer for anonymous user");
        $customer = $this->stripe->customers->create([
            'metadata' => ["cart_id" => $cart->getCartid()],
        ]);
        $this->logger->info("created customer for anonymous user: {$customer->id}");
        $this->session->set(self::SESSION_CUSTOMER_KEY, $customer->id);

        return $customer;
    }

    private function validCart(Cart $cart): bool
    {
        return
            $cart->getPaymenttype() === Cart::PAYMENTTYPE_STRIPE_INTENT
            && $cart->getTotalPrice() > 0.009
            && !$cart->recalcNeeded()
            && $this->paymentTypeSelector->getCreditCardPaymentType($cart) === Cart::PAYMENTTYPE_STRIPE_INTENT
        ;
    }

    private function createSetupIntent(Cart $cart, Customer $customer): string
    {
        $intentOptions = [
            'customer' => $customer->id,
            'automatic_payment_methods' => [
                'enabled' => 'true',
            ],
            'description' => "Order #" . $cart->getCartid(),
            'metadata' => [
                'cart_id' => $cart->getCartid(),
            ],
            //            'payment_method_types' => ['card', 'link'],
            // 'payment_method_types' => ['bancontact', 'card', 'ideal'],
        ];

        if ($cart->getUser()) {
            $intentOptions['metadata']['user_id'] = $cart->getUser()->getId();
        }

        $setupIntent = $this->stripe->setupIntents->create($intentOptions);
        $this->logger->info("created setup intent: " . $setupIntent->id);

        return $setupIntent->client_secret;
    }

    private function createPaymentIntent(Cart $cart, Customer $customer, string $returnUrl)
    {
        $intentOptions = [
            'customer' => $customer->id,
            'automatic_payment_methods' => [
                'enabled' => 'true',
            ],
            'description' => "Order #" . $cart->getCartid(),
            'metadata' => [
                'cart_id' => $cart->getCartid(),
            ],
            'amount' => (int) round($cart->getImmediateAmount() * 100),
            'currency' => 'usd',
            'statement_descriptor_suffix' => ' Order ' . $cart->getCartid(),
        ];

        if ($cart->getUser()) {
            $intentOptions['metadata']['user_id'] = $cart->getUser()->getId();
        }

        $paymentIntent = $this->stripe->paymentIntents->create($intentOptions);
        $this->logger->info("created payment intent: " . $paymentIntent->id);

        return $paymentIntent->client_secret;
    }

    /**
     * @return string|null - error message or null on success
     */
    private function processSetupIntent(string $setupIntentId, Cart $cart): ?string
    {
        $this->logger->info("reading setup intent: " . $setupIntentId);
        $setupIntent = $this->stripe->setupIntents->retrieve($setupIntentId);
        $txId = $setupIntent->id;

        switch ($setupIntent->status) {
            case SetupIntent::STATUS_SUCCEEDED:
                $this->logger->info("payment succeeded, payment method id: {$setupIntent->payment_method}");
                $immediateAmount = $cart->getImmediateAmount();

                if ($immediateAmount > 0.009) {
                    try {
                        $txId = $this->charger->charge($setupIntent->customer, $setupIntent->payment_method, $immediateAmount, $cart->getCartid(), $cart->getUser() ? $cart->getUser()->getId() : null);
                    } catch (CardException $exception) {
                        $this->logger->error("stripe immediate payment error: " . $exception->getMessage());

                        return $exception->getMessage();
                    }
                }

                $this->completeTransaction($cart, $txId, $setupIntent->customer, $setupIntent->payment_method);

                return null;

            default:
                $this->logger->info("unhandled stripe payment status: {$setupIntent->status}");

                return $setupIntent->status;
        }
    }

    /**
     * @return string|null - error message or null on success
     */
    private function processPaymentIntent(string $paymentIntentId, Cart $cart): ?string
    {
        $this->logger->info("reading payment intent: " . $paymentIntentId);
        $paymentIntent = $this->stripe->paymentIntents->retrieve($paymentIntentId);

        switch ($paymentIntent->status) {
            case SetupIntent::STATUS_SUCCEEDED:
                $this->logger->info("payment succeeded, payment method id: {$paymentIntent->payment_method}");
                $this->completeTransaction($cart, $paymentIntent->id, $paymentIntent->customer, $paymentIntent->payment_method);

                return null;

            default:
                $this->logger->info("unhandled stripe payment status: {$paymentIntent->status}");

                return $paymentIntent->status;
        }
    }

    private function completeTransaction(Cart $cart, string $transactionId, string $stripeCustomerId, ?string $stripePaymentMethodId): void
    {
        $cart->setBillingtransactionid($transactionId);
        $payer = $this->cartUserSource->getPayer() ?? $cart->getUser();

        if ($cart->getCreditcardnumber() === null) {
            $this->paymentMethodHelper->updateCreditCardDetails($stripePaymentMethodId, $cart);
        }

        if ($payer && !$cart->hasItemsByType([AwPlusGift::TYPE]) && $payer->getStripeCustomerId() !== $stripeCustomerId) {
            throw new \Exception("something went wrong, payer customer id: {$payer->getStripeCustomerId()} is not equals to received one: {$stripeCustomerId}");
        }

        if ($cart->getUser()) {
            $this->mobilePaymentWarningChecker->addWarnings($cart->getUser());
        }

        if ($payer && $cart->isSubscription()) {
            try {
                $this->recurringManager->cancelRecurringPayment($cart->getUser());
            } catch (\Exception $e) {
                $this->logger->critical("failed to cancel recurring payment profile, CANCEL THROUGH PAYPAL: " . $e->getMessage(), ["UserID" => $cart->getUser()->getId(), "CartID" => $cart->getCartid(), "ProfileID" => $cart->getUser()->getPaypalrecurringprofileid(), "exception" => $e]);
            }

            $cart->getUser()->setPaypalrecurringprofileid($stripePaymentMethodId);
            $cart->getUser()->setSubscription(Usr::SUBSCRIPTION_STRIPE);
        }

        $this->cartManager->markAsPayed($cart);
        $this->session->remove(self::SESSION_CUSTOMER_KEY);
    }
}
