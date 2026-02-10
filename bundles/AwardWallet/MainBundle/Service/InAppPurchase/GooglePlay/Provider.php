<?php

namespace AwardWallet\MainBundle\Service\InAppPurchase\GooglePlay;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusWeekSubscription;
use AwardWallet\MainBundle\Entity\Repositories\CartRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\InAppPurchase\AbstractConsumable;
use AwardWallet\MainBundle\Service\InAppPurchase\AbstractProvider;
use AwardWallet\MainBundle\Service\InAppPurchase\AbstractPurchase;
use AwardWallet\MainBundle\Service\InAppPurchase\AbstractSubscription;
use AwardWallet\MainBundle\Service\InAppPurchase\Billing;
use AwardWallet\MainBundle\Service\InAppPurchase\Consumable\AwPlus as ConsumableAwPlus;
use AwardWallet\MainBundle\Service\InAppPurchase\Consumable\Credit1;
use AwardWallet\MainBundle\Service\InAppPurchase\Consumable\Credit10;
use AwardWallet\MainBundle\Service\InAppPurchase\Consumable\Credit3;
use AwardWallet\MainBundle\Service\InAppPurchase\Consumable\Credit5;
use AwardWallet\MainBundle\Service\InAppPurchase\ConsumableProduct;
use AwardWallet\MainBundle\Service\InAppPurchase\Exception\DecodeException;
use AwardWallet\MainBundle\Service\InAppPurchase\Exception\QuietGoogleException;
use AwardWallet\MainBundle\Service\InAppPurchase\Exception\QuietVerificationException;
use AwardWallet\MainBundle\Service\InAppPurchase\Exception\VerificationException;
use AwardWallet\MainBundle\Service\InAppPurchase\LoggerContext;
use AwardWallet\MainBundle\Service\InAppPurchase\Product;
use AwardWallet\MainBundle\Service\InAppPurchase\Subscription\AwPlus as SubscriptionAwPlus;
use AwardWallet\MainBundle\Service\InAppPurchase\Subscription\AwPlusDiscounted;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Google\Service\AndroidPublisher\Resource\PurchasesSubscriptions;
use Google\Service\Exception;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class Provider extends AbstractProvider
{
    public const PRODUCT_AWPLUS = 'awardwallet_plus_managed';
    public const PRODUCT_AWPLUS_SUBSCR = 'plus_subscription_1year';
    public const PRODUCT_AWPLUS_SUBSCR_DISCOUNT = 'plus_subscription_1year_discounted';
    public const PRODUCT_UPDATE_CREDIT1 = '1_update_credit';
    public const PRODUCT_UPDATE_CREDITS3 = '3_update_credit';
    public const PRODUCT_UPDATE_CREDITS5 = '5_update_credit';
    public const PRODUCT_UPDATE_CREDITS10 = '10_update_credit';
    public const PRODUCT_UPDATE_CREDIT1_NEW = '1_update_credit_new';
    public const PRODUCT_UPDATE_CREDITS3_NEW = '3_update_credit_new';
    public const PRODUCT_UPDATE_CREDITS5_NEW = '5_update_credit_new';
    public const PRODUCT_UPDATE_CREDITS10_NEW = '10_update_credit_new';

    public const BUNDLE_ID = 'com.itlogy.awardwallet';

    public const PURCHASE_STATE_PURCHASED = 0;
    public const PURCHASE_STATE_CANCELED = 1;
    public const PURCHASE_STATE_REFUNDED = 2;

    public const PAYMENT_STATE_PENDING = 0;
    public const PAYMENT_STATE_RECEIVED = 1;
    public const PAYMENT_STATE_TRIAL = 2;

    private LoggerInterface $logger;

    private TranslatorInterface $translator;

    private bool $useSandbox;

    private Decoder $decoder;

    private string $serviceAccountConfig;
    private PurchasesSubscriptions $googlePurchasesSubscriptions;
    private CartRepository $cartRepository;

    public function __construct(
        LoggerInterface $paymentLogger,
        TranslatorInterface $translator,
        EntityManagerInterface $em,
        ApiVersioningService $apiVersioning,
        bool $isSandbox,
        Decoder $decoder,
        string $serviceAccountConfig,
        PurchasesSubscriptions $googlePurchasesSubscriptions,
        CartRepository $cartRepository,
        LocalizeService $localizeService
    ) {
        parent::__construct($em);
        $this->logger = $paymentLogger;
        $this->translator = $translator;
        $this->em = $em;
        $this->useSandbox = $isSandbox;
        $this->decoder = $decoder;
        $this->serviceAccountConfig = $serviceAccountConfig;

        $baseAwPlusSubscriptionDescription = $this->useLatestMobileVersion || $apiVersioning->supports(MobileVersions::AWPLUS_DESCRIPTION_LONG) ?
            $this->translator->trans('pay-subscription.product.description.long.v2', [
                '%p_on%' => '<p>', '%p_off%' => '</p>',
                '%list_on%' => '<ul>', '%list_off%' => '</ul>',
                '%item_on%' => '<li>', '%item_off%' => '</li>',
                '%link_1%' => '<a href="#/terms">',
                '%link_2%' => '<a href="#/privacy">',
                '%link_off%' => '</a>',
                '%store_name%' => 'Google Play',
                '%price%' => $localizeService->formatCurrency(AwPlusSubscription::PRICE, 'USD'),
            ], 'mobile') :
            $this->translator->trans("pay-subscription.product.description", [
                '%list_on%' => '<ul>', '%list_off%' => '</ul>',
                '%item_on%' => '<li>', '%item_off%' => '</li>',
            ], "mobile");

        // todo: credit description
        $creditDescription = '';
        $isNewConsumables = $apiVersioning->supports(MobileVersions::ANDROID_NEW_CONSUMABLES);

        $this->products = [
            ConsumableAwPlus::class => new Product(self::PRODUCT_AWPLUS, 'consumable', $this->translator->trans("pay.product.description", [], "mobile")),
            SubscriptionAwPlus::class => new Product(self::PRODUCT_AWPLUS_SUBSCR, 'paid subscription', $baseAwPlusSubscriptionDescription),
            AwPlusDiscounted::class => new Product(self::PRODUCT_AWPLUS_SUBSCR_DISCOUNT, 'paid subscription', $baseAwPlusSubscriptionDescription),
            Credit1::class => new ConsumableProduct(
                $isNewConsumables ? self::PRODUCT_UPDATE_CREDIT1_NEW : self::PRODUCT_UPDATE_CREDIT1,
                Credit1::COUNT_ITEMS
            ),
            Credit3::class => new ConsumableProduct(
                $isNewConsumables ? self::PRODUCT_UPDATE_CREDITS3_NEW : self::PRODUCT_UPDATE_CREDITS3,
                Credit3::COUNT_ITEMS
            ),
            Credit5::class => new ConsumableProduct(
                $isNewConsumables ? self::PRODUCT_UPDATE_CREDITS5_NEW : self::PRODUCT_UPDATE_CREDITS5,
                Credit5::COUNT_ITEMS
            ),
            Credit10::class => new ConsumableProduct(
                $isNewConsumables ? self::PRODUCT_UPDATE_CREDITS10_NEW : self::PRODUCT_UPDATE_CREDITS10,
                Credit10::COUNT_ITEMS
            ),
        ];
        $this->googlePurchasesSubscriptions = $googlePurchasesSubscriptions;
        $this->cartRepository = $cartRepository;
    }

    public function validate(array $data, ?Usr $currentUser = null, array $options = []): array
    {
        $this->log("validate data: " . var_export($data, true), $currentUser);

        if (!is_array($data) || (!isset($data['signedData']) && !isset($data['receipt'])) || !isset($data['signature'])) {
            throw new QuietVerificationException($currentUser, null, $this, "Invalid receipt data");
        }

        $signedData = $data['signedData'] ?? $data['receipt'];
        $signature = $data['signature'];

        $this->log('signed data: ' . $signedData . ', signature: ' . $signature, $currentUser);

        try {
            $info = $this->decoder->decode($signedData, $signature);
        } catch (DecodeException $e) {
            if ($e->getCode() === 0) {
                throw QuietVerificationException::withThrowable($e, $currentUser, null, $this);
            }

            throw VerificationException::withThrowable($e, $currentUser, null, $this);
        }

        $this->log("signature ok, info: " . var_export($info, true), $currentUser);

        return $this->validatePurchaseData($info, $currentUser);
    }

    public function findSubscriptions(Usr $user): array
    {
        $purchases = [];
        $cartRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Cart::class);

        $stmt = $this->em->getConnection()->executeQuery(
            "
            SELECT
                c.CartID
            FROM
                Cart c
                JOIN CartItem ci ON c.CartID = ci.CartID
            WHERE
                c.UserID = ?
                AND c.PayDate IS NOT NULL
                AND c.PaymentType = ?
                AND c.PurchaseToken IS NOT NULL
                AND ci.TypeID IN (?)
        ",
            [$user->getId(), Cart::PAYMENTTYPE_ANDROIDMARKET, [AwPlusSubscription::TYPE, AwPlusWeekSubscription::TYPE]],
            [\PDO::PARAM_INT, \PDO::PARAM_INT, Connection::PARAM_INT_ARRAY]
        );

        $tokens = [];

        while ($cartId = $stmt->fetchColumn(0)) {
            $cart = $cartRep->find($cartId);

            if (!$cart || !$cart->getPaydate()) {
                continue;
            }

            $token = $cart->getPurchaseToken();

            if (in_array($token, $tokens)) {
                continue;
            }

            $pid = $this->getPlatformProductIdByCart($cart);

            if (!$pid) {
                continue;
            }

            $tokens[] = $token;

            /** @var \DateTime $payDate */
            $payDate = $cart->getPaydate();

            try {
                $purchases = array_merge($purchases, $this->validatePurchaseData([
                    'packageName' => self::BUNDLE_ID,
                    'productId' => $pid,
                    'purchaseState' => self::PURCHASE_STATE_PURCHASED,
                    'purchaseTime' => $payDate->getTimestamp() * 1000,
                    'purchaseToken' => $token,
                    'orderId' => $cart->getBillingtransactionid(),
                ], $user));
            } catch (QuietVerificationException $e) {
                $this->logger->warning(sprintf("In-App Purchase verification exception: %s", $e->getMessage()), $e->getContext());
            } catch (VerificationException $e) {
                $this->logger->critical(sprintf("In-App Purchase verification exception: %s", $e->getMessage()), $e->getContext());
            }
        }

        return $purchases;
    }

    public function scanSubscriptions(Usr $user, Billing $billing): void
    {
        $this->checkSubscriptionIsActive($user);

        $cartRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Cart::class);

        $stmt = $this->em->getConnection()->executeQuery(
            "
            SELECT
                c.CartID
            FROM
                Cart c
                JOIN CartItem ci ON c.CartID = ci.CartID
            WHERE
                c.UserID = ?
                AND c.PayDate IS NOT NULL
                AND c.PaymentType = ?
                AND c.PurchaseToken IS NOT NULL
                AND ci.TypeID IN (?)
        ",
            [$user->getId(), Cart::PAYMENTTYPE_ANDROIDMARKET, [AwPlusSubscription::TYPE, AwPlusWeekSubscription::TYPE]],
            [\PDO::PARAM_INT, \PDO::PARAM_INT, Connection::PARAM_INT_ARRAY]
        );

        $tokens = [];

        while ($cartId = $stmt->fetchColumn(0)) {
            $cart = $cartRep->find($cartId);

            if (!$cart || !$cart->getPaydate()) {
                continue;
            }

            $token = $cart->getPurchaseToken();

            if (in_array($token, $tokens)) {
                continue;
            }

            $pid = $this->getPlatformProductIdByCart($cart);

            if (!$pid) {
                continue;
            }

            $tokens[] = $token;

            /** @var \DateTime $payDate */
            $payDate = $cart->getPaydate();

            try {
                $purchases = $this->validatePurchaseData([
                    'packageName' => self::BUNDLE_ID,
                    'productId' => $pid,
                    'purchaseState' => self::PURCHASE_STATE_PURCHASED,
                    'purchaseTime' => $payDate->getTimestamp() * 1000,
                    'purchaseToken' => $token,
                    'orderId' => $cart->getBillingtransactionid(),
                ], $user);

                foreach ($purchases as $purchase) {
                    $billing->processing($purchase);
                }
            } catch (QuietVerificationException $e) {
                $this->logger->warning(sprintf("In-App Purchase verification exception: %s", $e->getMessage()), $e->getContext());
            } catch (VerificationException $e) {
                $this->logger->critical(sprintf("In-App Purchase verification exception: %s", $e->getMessage()), $e->getContext());
            }
        }
    }

    public function validatePurchaseData(array $info, ?Usr $currentUser = null): array
    {
        $this->log("validate purchase data", $currentUser, $info);

        // detect user
        if (isset($info['developerPayload']) && is_string($info['developerPayload']) && !empty($info['developerPayload'])) {
            $devPayload = $info['developerPayload'];

            if (
                (strpos($devPayload, 'inapp') === 0 || strpos($devPayload, 'subs') === 0)
                && preg_match("/(?<=:)\{.+\}$/ims", $devPayload, $matches)) {
                $devPayload = $matches[0];
            }
            $payload = @json_decode($devPayload);

            if (JSON_ERROR_NONE !== json_last_error()) {
                throw new QuietVerificationException($currentUser, $info, $this, sprintf("Unable to parse response body into JSON (%s): %s", $devPayload, json_last_error()));
            }

            if (!empty($payload) && is_object($payload) && property_exists($payload, 'UserID')) {
                $user = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find(intval($payload->UserID));
            }
        }

        if (!isset($user) && isset($currentUser)) {
            $user = $currentUser;
        }

        if (!isset($user)) {
            throw new VerificationException($currentUser, $info, $this, "User not detected");
        } else {
            $this->log("user detected", $user);
        }

        // check bundle
        if (!isset($info['packageName']) || $info['packageName'] != self::BUNDLE_ID) {
            throw new QuietVerificationException($user, $info, $this, "Missing or invalid packageName");
        }

        // check state
        if (!isset($info['purchaseState']) || !in_array(intval($info['purchaseState']), [self::PURCHASE_STATE_PURCHASED, self::PURCHASE_STATE_REFUNDED])) {
            throw new VerificationException($user, $info, $this, "Missing or invalid purchaseState");
        } else {
            $state = intval($info['purchaseState']);
        }

        // check purchase time
        if (isset($info['purchaseTime']) && is_numeric($info['purchaseTime'])) {
            $transactionDate = new \DateTime("@" . intval($info['purchaseTime'] / 1000));
        } else {
            throw new VerificationException($user, $info, $this, "Missing or invalid purchaseTime");
        }

        // check product id
        if (isset($info['productId'])) {
            $pid = $this->getProductId($info['productId']);
        }

        if (!isset($pid)) {
            throw new VerificationException($user, $info, $this, "Missing or invalid productId");
        }

        // check purchase token
        if (!isset($info['purchaseToken']) || empty($info['purchaseToken'])) {
            throw new VerificationException($user, $info, $this, "Missing or invalid purchaseToken");
        } else {
            $purchaseToken = $info['purchaseToken'];
        }

        // check order id
        $orderId = $info['orderId'] ?? null;

        if (!isset($orderId)) {
            if ($this->useSandbox || ($user->hasRole('ROLE_STAFF') || $user->hasRole('ROLE_REVIEWERS'))) {
                $orderId = 'GPA.0000-0000-0000-00000';
            }
        }

        if (AbstractSubscription::isSubscription($pid)) {
            $this->log("subscription", $user, ["pid" => $pid]);

            try {
                $subscription = $this->getGooglePlaySubscription($info['productId'], $purchaseToken);

                if (empty($subscription->getStartTimeMillis()) || empty($subscription->getExpiryTimeMillis())) {
                    throw new \Exception("google subscription was not found");
                }
                $startDate = new \DateTime("@" . intval($subscription->getStartTimeMillis() / 1000));
                $expirationDate = clone $startDate;
                $expirationDate->modify($pid::$duration);
            } catch (QuietGoogleException $e) {
                throw new QuietVerificationException($user, $info, $this, $e->getMessage(), $e->getCode(), $e);
            } catch (\Exception $e) {
                throw VerificationException::withThrowable($e, $user, $info, $this)->setTemporary(true);
            }
            $this->log("google subscription", $user, [
                'subscription_details' => var_export($subscription, true),
            ]);

            if (!empty($subOrderId = $subscription->getOrderId())) {
                if (!empty($orderId) && $orderId != $subOrderId) {
                    $this->log("replace order id", $user, [
                        'from' => $orderId,
                        'to' => $subOrderId,
                    ]);
                }
                $orderId = $subOrderId;
            }

            if (!isset($orderId)) {
                throw new VerificationException($user, $info, $this, "Missing order id");
            }

            $history = self::getOrdersHistory($orderId);

            if (!is_array($history)) {
                throw new VerificationException($user, $info, $this, "Wrong format order id. Must be in format 'GPA.XXXX-XXXX-XXXX-XXXXX'");
            }

            if (count($history) === 1) {
                $this->log("start subscription", $user);
            }
            $baseOrderId = array_shift($history);
            $availableProduct = $this->findProductIdByTransactionId($baseOrderId, Cart::PAYMENTTYPE_ANDROIDMARKET);

            if (!is_null($availableProduct)) {
                $availableProducts = [$availableProduct];
            } else {
                $availableProducts = AbstractPurchase::getAvailableProducts($user, $this);
            }

            if (!in_array($pid, $availableProducts)) {
                throw new VerificationException($user, $info, $this, "Invalid product id");
            }

            $firstPurchase = AbstractSubscription::create(
                $pid,
                $user,
                Cart::PAYMENTTYPE_ANDROIDMARKET,
                $baseOrderId,
                $startDate
            );
            $firstPurchase->setCanceled(false);
            $firstPurchase->setPurchaseToken($purchaseToken);

            $result = [$firstPurchase];
            $startTs = $firstPurchase->getExpiresDate()->getTimestamp();

            foreach ($history as $singleOrderId) {
                $result[] = $purchase = AbstractSubscription::create($pid, $user, Cart::PAYMENTTYPE_ANDROIDMARKET, $singleOrderId, new \DateTime("@" . $startTs));
                $purchase->setCanceled(false);
                $purchase->setPurchaseToken($purchaseToken);
                $purchase->setRecurring(true);

                $startTs = $purchase->getExpiresDate()->getTimestamp();
            }

            $purchasesCount = count($result);

            foreach ($result as $k => $purchase) {
                /** @var AbstractSubscription $purchase */
                $isLast = $k === ($purchasesCount - 1);

                if ($isLast && is_null($subscription->getPaymentState())) {
                    $this->log('canceled', $user);
                    $purchase->setCanceled(true);
                }
            }

            return $result;
        } else {
            // non subscription
            $this->log("non subscription", $user);

            if (!isset($orderId)) {
                throw new VerificationException($user, $info, $this, "Missing order id");
            }
            $availableProducts = AbstractPurchase::getAvailableProducts($user, $this);

            if (!in_array($pid, $availableProducts)) {
                throw new VerificationException($user, $info, $this, "Invalid product id");
            }

            $p = AbstractConsumable::create($pid, $user, Cart::PAYMENTTYPE_ANDROIDMARKET, $orderId, $transactionDate);
            $p->setCanceled($state === self::PURCHASE_STATE_REFUNDED);
            $p->setPurchaseToken($purchaseToken);

            return [$p];
        }
    }

    /**
     * @throws QuietGoogleException
     * @throws \Google_Exception
     */
    public function getGooglePlaySubscription(string $subscriptionId, string $purchaseToken): \Google_Service_AndroidPublisher_SubscriptionPurchase
    {
        try {
            $client = new \Google_Client();
            $client->setAuthConfig($this->serviceAccountConfig);
            $client->setApplicationName("AwardWallet");
            $client->setScopes([\Google_Service_AndroidPublisher::ANDROIDPUBLISHER]);
            $pub = new \Google_Service_AndroidPublisher($client);

            return $pub->purchases_subscriptions->get(
                self::BUNDLE_ID,
                $subscriptionId,
                $purchaseToken
            );
        } catch (\Google_Exception $e) {
            if (!empty($e->getMessage())
                && preg_match('/(The subscription purchase is no longer available for query)|(The purchase token is no longer valid)/ims', $e->getMessage())) {
                throw new QuietGoogleException($e->getMessage(), $e->getCode(), $e);
            }

            throw $e;
        }
    }

    public function cancelGooglePlaySubscription(string $subscriptionId, string $purchaseToken)
    {
        $client = new \Google_Client();
        $client->setAuthConfig($this->serviceAccountConfig);
        $client->setApplicationName("AwardWallet");
        $client->setScopes([\Google_Service_AndroidPublisher::ANDROIDPUBLISHER]);
        $pub = new \Google_Service_AndroidPublisher($client);

        return $pub->purchases_subscriptions->cancel(
            self::BUNDLE_ID,
            $subscriptionId,
            $purchaseToken
        );
    }

    public function getGooglePlayProduct(string $productId, string $purchaseToken): \Google_Service_AndroidPublisher_ProductPurchase
    {
        $client = new \Google_Client();
        $client->setAuthConfig($this->serviceAccountConfig);
        $client->setApplicationName("AwardWallet");
        $client->setScopes([\Google_Service_AndroidPublisher::ANDROIDPUBLISHER]);
        $pub = new \Google_Service_AndroidPublisher($client);

        return $pub->purchases_products->get(
            self::BUNDLE_ID,
            $productId,
            $purchaseToken
        );
    }

    public function getPlatformId(): string
    {
        return 'android-v3';
    }

    public function getCompanyName(): string
    {
        return 'Google';
    }

    public static function getBaseOrderId(string $orderId): ?string
    {
        if (preg_match("/^(GPA\.\d+\-\d+\-\d+\-\d+)(\.\.)?(\d+)?$/", $orderId, $matches)) {
            return $matches[1];
        }

        return null;
    }

    public static function getOrdersHistory(string $lastOrderId): ?array
    {
        if (preg_match("/^(GPA\.\d+\-\d+\-\d+\-\d+)(\.\.)?(\d+)?$/", $lastOrderId, $matches)) {
            $result = [$matches[1]];

            if (isset($matches[3])) {
                for ($i = 0; $i <= $matches[3]; $i++) {
                    $result[] = $matches[1] . ".." . $i;
                }
            }

            return $result;
        }

        return null;
    }

    private function checkSubscriptionIsActive(Usr $user): void
    {
        if ($user->getSubscription() !== Usr::SUBSCRIPTION_MOBILE) {
            return;
        }

        $subscriptionCart = $user->getLastSubscriptionCartItem()->getCart();

        if ($subscriptionCart->getPaymenttype() !== Cart::PAYMENTTYPE_ANDROIDMARKET) {
            return;
        }

        $firstSubscriptionOrderId = self::getBaseOrderId($subscriptionCart->getBillingtransactionid());
        $firstSubscriptionCart = $this->cartRepository->findOneBy(['billingtransactionid' => $firstSubscriptionOrderId]);

        $platformProductId = $this->getPlatformProductIdByCart($firstSubscriptionCart);
        $this->log("first subscription cart: " . $firstSubscriptionCart->getCartid() . ", current cart: {$subscriptionCart->getCartid()}, order: {$subscriptionCart->getBillingtransactionid()}", $user);

        try {
            $subscription = $this->googlePurchasesSubscriptions->get(Provider::BUNDLE_ID, $platformProductId, $subscriptionCart->getPurchaseToken());
        } catch (Exception $exception) {
            $this->logger->warning($exception->getMessage(), ["UserID" => $user->getId()]);

            if (
                count($exception->getErrors()) === 1
                && (
                    $exception->getErrors()[0]['reason'] === 'subscriptionPurchaseNoLongerAvailable'
                    || $exception->getErrors()[0]['reason'] === 'purchaseTokenNoLongerValid'
                )
            ) {
                $this->log("google subscription was expired in play store", $user);
                $user->clearSubscription();
                $this->em->flush();

                return;
            }

            throw $exception;
        }

        if (!$subscription->autoRenewing) {
            $this->log("google subscription was cancelled in play store", $user);
            $user->clearSubscription();
            $this->em->flush();
        }
    }

    private function log(string $message, ?Usr $user = null, array $extraData = []): void
    {
        $this->logger->info($message, array_merge(
            LoggerContext::get($user),
            $extraData
        ));
    }
}
