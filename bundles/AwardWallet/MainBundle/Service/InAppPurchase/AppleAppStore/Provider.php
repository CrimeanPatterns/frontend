<?php

namespace AwardWallet\MainBundle\Service\InAppPurchase\AppleAppStore;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusWeekSubscription;
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
use AwardWallet\MainBundle\Service\InAppPurchase\Exception\ConnectException;
use AwardWallet\MainBundle\Service\InAppPurchase\Exception\QuietVerificationException;
use AwardWallet\MainBundle\Service\InAppPurchase\Exception\VerificationException;
use AwardWallet\MainBundle\Service\InAppPurchase\LoggerContext;
use AwardWallet\MainBundle\Service\InAppPurchase\Product;
use AwardWallet\MainBundle\Service\InAppPurchase\PurchaseInterface;
use AwardWallet\MainBundle\Service\InAppPurchase\Subscription\AwPlus as SubscriptionAwPlus;
use AwardWallet\MainBundle\Service\InAppPurchase\Subscription\AwPlusDiscounted;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class Provider extends AbstractProvider
{
    public const OPTION_ENABLE_SCAN_USERS = 'enable_scan_users';
    public const OPTION_CANCEL_SUBSCRIPTION = 'cancel_subscription';

    public const PRODUCT_AWPLUS = 10;
    public const PRODUCT_AWPLUS_SUBSCR = 13; // 11
    public const PRODUCT_AWPLUS_SUBSCR_DISCOUNT = 12;
    public const PRODUCT_AWPLUS_SUBSCR_WEEK = 'awardwallet_test';
    public const PRODUCT_UPDATE_CREDIT1 = '1_update_credit';
    public const PRODUCT_UPDATE_CREDITS3 = '3_update_credits';
    public const PRODUCT_UPDATE_CREDITS5 = '5_update_credits';
    public const PRODUCT_UPDATE_CREDITS10 = '10_update_credits';

    public const BUNDLE_ID = 'com.awardwallet.iphone';

    public const ENDPOINT_SANDBOX = 'https://sandbox.itunes.apple.com/verifyReceipt';
    public const ENDPOINT_PRODUCTION = 'https://buy.itunes.apple.com/verifyReceipt';

    public const RESULT_OK = 0;
    // The App Store could not read the JSON object you provided.
    public const RESULT_APPSTORE_CANNOT_READ = 21000;
    // The data in the receipt-data property was malformed or missing.
    public const RESULT_DATA_MALFORMED = 21002;
    // The receipt could not be authenticated.
    public const RESULT_RECEIPT_NOT_AUTHENTICATED = 21003;
    // The shared secret you provided does not match the shared secret on file for your account.
    // Only returned for iOS 6 style transaction receipts for auto-renewable subscriptions.
    public const RESULT_SHARED_SECRET_NOT_MATCH = 21004;
    // The receipt server is not currently available.
    public const RESULT_RECEIPT_SERVER_UNAVAILABLE = 21005;
    // This receipt is valid but the subscription has expired. When this status code is returned to your server, the receipt data is also decoded and returned as part of the response.
    // Only returned for iOS 6 style transaction receipts for auto-renewable subscriptions.
    public const RESULT_RECEIPT_VALID_BUT_SUB_EXPIRED = 21006;
    // This receipt is from the test environment, but it was sent to the production environment for verification. Send it to the test environment instead.
    // special case for app review handling - forward any request that is intended for the Sandbox but was sent to Production, this is what the app review team does
    public const RESULT_SANDBOX_RECEIPT_SENT_TO_PRODUCTION = 21007;
    // This receipt is from the production environment, but it was sent to the test environment for verification. Send it to the production environment instead.
    public const RESULT_PRODUCTION_RECEIPT_SENT_TO_SANDBOX = 21008;
    // This receipt could not be authorized. Treat this the same as if a purchase was never made.
    public const RESULT_RECEIPT_NOT_AUTHORIZED = 21010;

    public const ERRORS = [
        self::RESULT_APPSTORE_CANNOT_READ => 'The App Store could not read the JSON object you provided.',
        self::RESULT_DATA_MALFORMED => 'The data in the receipt-data property was malformed.',
        self::RESULT_RECEIPT_NOT_AUTHENTICATED => 'The receipt could not be authenticated.',
        self::RESULT_SHARED_SECRET_NOT_MATCH => 'The shared secret you provided does not match the shared secret on file for your account.',
        self::RESULT_RECEIPT_SERVER_UNAVAILABLE => 'The receipt server is not currently available.',
        self::RESULT_RECEIPT_VALID_BUT_SUB_EXPIRED => 'This receipt is valid but the subscription has expired. When this status code is returned to your server, the receipt data is also decoded and returned as part of the response.',
        self::RESULT_SANDBOX_RECEIPT_SENT_TO_PRODUCTION => 'This receipt is a sandbox receipt, but it was sent to the production service for verification.',
        self::RESULT_PRODUCTION_RECEIPT_SENT_TO_SANDBOX => 'This receipt is a production receipt, but it was sent to the sandbox service for verification.',
        self::RESULT_RECEIPT_NOT_AUTHORIZED => 'This receipt could not be authorized.',
    ];

    private Connector $connector;

    private LoggerInterface $logger;

    private TranslatorInterface $translator;

    private UserDetector $userDetector;

    private string $secret;

    private bool $useSandbox;

    public function __construct(
        Connector $connector,
        LoggerInterface $paymentLogger,
        TranslatorInterface $translator,
        EntityManagerInterface $em,
        ApiVersioningService $apiVersioning,
        UserDetector $userDetector,
        LocalizeService $localizeService,
        string $secret,
        bool $isSandbox
    ) {
        parent::__construct($em);
        $this->connector = $connector;
        $this->logger = $paymentLogger;
        $this->translator = $translator;
        $this->userDetector = $userDetector;
        $this->secret = $secret;
        $this->useSandbox = $isSandbox;

        $baseAwPlusSubscriptionDescription = $this->useLatestMobileVersion || $apiVersioning->supports(MobileVersions::AWPLUS_DESCRIPTION_LONG) ?
            $this->translator->trans('pay-subscription.product.description.long.v2', [
                '%p_on%' => '<p>', '%p_off%' => '</p>',
                '%list_on%' => '<ul>', '%list_off%' => '</ul>',
                '%item_on%' => '<li>', '%item_off%' => '</li>',
                '%link_1%' => '<a href="#/terms">',
                '%link_2%' => '<a href="#/privacy">',
                '%link_off%' => '</a>',
                '%store_name%' => "iTunes",
                '%price%' => $localizeService->formatCurrency(AwPlusSubscription::PRICE, 'USD'),
            ], 'mobile') :
            $this->translator->trans("pay-subscription.product.description", [
                '%list_on%' => '<ul>', '%list_off%' => '</ul>',
                '%item_on%' => '<li>', '%item_off%' => '</li>',
            ], "mobile");

        $this->products = [
            ConsumableAwPlus::class => new Product(self::PRODUCT_AWPLUS, 'paid subscription', $this->translator->trans("pay.product.description", [], "mobile")),
            SubscriptionAwPlus::class => new Product(self::PRODUCT_AWPLUS_SUBSCR, 'paid subscription', $baseAwPlusSubscriptionDescription),
            AwPlusDiscounted::class => new Product(self::PRODUCT_AWPLUS_SUBSCR_DISCOUNT, 'paid subscription', $baseAwPlusSubscriptionDescription),
            Credit1::class => new ConsumableProduct(self::PRODUCT_UPDATE_CREDIT1, Credit1::COUNT_ITEMS),
            Credit3::class => new ConsumableProduct(self::PRODUCT_UPDATE_CREDITS3, Credit3::COUNT_ITEMS),
            Credit5::class => new ConsumableProduct(self::PRODUCT_UPDATE_CREDITS5, Credit5::COUNT_ITEMS),
            Credit10::class => new ConsumableProduct(self::PRODUCT_UPDATE_CREDITS10, Credit10::COUNT_ITEMS),
        ];
    }

    public function validate(array $data, ?Usr $currentUser = null, array $options = []): array
    {
        $this->log("validate data", $currentUser, $data);
        $transactionId = null;
        $enableScanUsers = $options[self::OPTION_ENABLE_SCAN_USERS] ?? true;

        if (isset($data['TransactionID'], $data['Receipt']) && !empty($data['TransactionID']) && !empty($data['Receipt'])) {
            $oldReceipt = $data['Receipt'];
        }

        if (isset($data['type'], $data['transactionReceipt']) && $data['type'] === 'ios-appstore' && !empty($data['transactionReceipt'])) {
            $transactionReceipt = $data['transactionReceipt'];
        }

        if (isset($data['type'], $data['appStoreReceipt']) && $data['type'] === 'ios-appstore' && !empty($data['appStoreReceipt'])) {
            $appStoreReceipt = $data['appStoreReceipt'];
        }

        if (isset($oldReceipt)) {
            $transactionId = $data['TransactionID'];
            $receipt = $oldReceipt;
            $this->log("old request", $currentUser, [
                'transactionID' => $transactionId,
                'receipt' => $receipt,
            ]);
        } elseif (isset($transactionReceipt)) {
            if (isset($data['id']) && !empty($data['id'])) {
                $transactionId = $data['id'];
            }
            $receipt = $transactionReceipt;
            $this->log("old request", $currentUser, [
                'transactionID' => $transactionId,
                'transactionReceipt' => $receipt,
            ]);
        } elseif (isset($appStoreReceipt)) {
            if (isset($data['id']) && !empty($data['id'])) {
                $transactionId = $data['id'];
            }
            $receipt = $appStoreReceipt;
            $this->log("request", $currentUser, [
                'transactionID' => $transactionId,
                'appStoreReceipt' => $receipt,
            ]);
        } else {
            throw new QuietVerificationException($currentUser, $data, $this, "Invalid receipt data");
        }

        if (isset($appStoreReceipt)) {
            $this->log("app store receipt", $currentUser, [
                "receipt" => $appStoreReceipt,
            ]);
        }

        if (
            isset($transactionReceipt)
            && !\is_null($bid = $this->findBidId($transactionReceipt))
            && ($bid !== self::BUNDLE_ID)
        ) {
            throw new QuietVerificationException($currentUser, $data, $this, "Invalid bundle id, possible fraud");
        }

        if (isset($transactionId) && !preg_match("/^\d+$/ims", $transactionId)) {
            throw new QuietVerificationException($currentUser, $data, $this, "Invalid transaction id, expected digits-only");
        }

        try {
            $json = $this->connector->sendRequest(
                $this->useSandbox ? self::ENDPOINT_SANDBOX : self::ENDPOINT_PRODUCTION,
                json_encode([
                    'receipt-data' => strval($receipt),
                    'password' => $this->secret,
                ])
            );

            $json = json_decode($json);
            //          $json = json_decode($data['fake_receipt']);

            if (!is_object($json)) {
                throw (new VerificationException($currentUser, null, $this, $this->getStatusText(self::RESULT_DATA_MALFORMED)))->setTemporary(true);
            }

            if (
                !$this->useSandbox
                && property_exists($json, 'status')
                && $json->status === self::RESULT_SANDBOX_RECEIPT_SENT_TO_PRODUCTION
                && isset($currentUser)
                && ($currentUser->hasRole('ROLE_STAFF') || $currentUser->hasRole('ROLE_REVIEWERS'))
            ) {
                $this->log("send request to sandbox");
                $json = $this->connector->sendRequest(
                    self::ENDPOINT_SANDBOX,
                    json_encode([
                        'receipt-data' => strval($receipt),
                        'password' => $this->secret,
                    ])
                );

                $json = json_decode($json);
            }
        } catch (ConnectException $e) {
            throw VerificationException::withThrowable($e, $currentUser, $data, $this)->setTemporary(true);
        }

        $this->log("ios receipt", $currentUser, ["json" => $json]);

        if ($currentUser && ($options[self::OPTION_CANCEL_SUBSCRIPTION] ?? false)) {
            $this->checkSubscriptionStatus($json, $currentUser);
        }

        if (property_exists($json, 'receipt') && is_object($json->receipt) && property_exists($json->receipt, 'bundle_id')) {
            $this->log("app receipt", $currentUser);

            if ($json->status != self::RESULT_OK) {
                $this->handleResponseStatus($json->status, $json, $currentUser);
            }

            if ($json->receipt->bundle_id != self::BUNDLE_ID) {
                throw new QuietVerificationException($currentUser, $json, $this, "Invalid bundle id");
            }

            // find transaction
            $transactions = $this->findTransactions($json, $receipt, $currentUser, $transactionId, $enableScanUsers);

            if (!$transactions) {
                $this->log("transactions not found", $currentUser);

                return [];
            } else {
                return $transactions;
            }
        } elseif (
            (property_exists($json, 'receipt') && property_exists($json->receipt, 'bid'))
            || (property_exists($json, 'latest_receipt_info') && property_exists($json->latest_receipt_info, 'bid'))
        ) {
            $this->log("transaction receipt", $currentUser);

            if (!in_array($json->status, [self::RESULT_OK, self::RESULT_RECEIPT_VALID_BUT_SUB_EXPIRED])) {
                $this->handleResponseStatus($json->status, $json, $currentUser);
            }

            $receipts = [];

            if (property_exists($json, 'receipt') && property_exists($json->receipt, 'bid')) {
                $receipts[] = $json->receipt;
            }

            if (property_exists($json, 'latest_receipt_info') && property_exists($json->latest_receipt_info, 'bid')) {
                $receipts[] = $json->latest_receipt_info;
            }

            $purchases = [];

            foreach ($receipts as $singleReceipt) {
                if ($singleReceipt->bid != self::BUNDLE_ID) {
                    throw new QuietVerificationException($currentUser, $json, $this, "Invalid bundle id");
                }

                try {
                    $pid = $this->getProductId($singleReceipt->product_id);

                    if (!$pid) {
                        throw new VerificationException($currentUser, $json, $this, "Invalid product id");
                    }
                    $user = $this->userDetector->detect($pid, $singleReceipt, $this, $currentUser, $enableScanUsers);

                    if (!isset($user)) {
                        throw new VerificationException($currentUser, $json, $this, "User not detected");
                    } else {
                        $this->log("user detected", $user);
                    }

                    if (property_exists($singleReceipt, 'transaction_id')
                        && !empty($singleReceipt->transaction_id)
                        && (is_null($transactionId) || $transactionId === $singleReceipt->transaction_id)) {
                        if (!property_exists($singleReceipt, 'purchase_date_ms') || empty($singleReceipt->purchase_date_ms) || !is_numeric($singleReceipt->purchase_date_ms)) {
                            throw new VerificationException($currentUser, $json, $this, "Invalid purchase_date_ms");
                        }
                        $transactionDate = new \DateTime("@" . intval($singleReceipt->purchase_date_ms / 1000));

                        // get available products
                        $availableProduct = null;

                        if (
                            property_exists($singleReceipt, 'original_transaction_id')
                            && !empty($singleReceipt->original_transaction_id)
                            && $singleReceipt->transaction_id != $singleReceipt->original_transaction_id
                        ) {
                            $availableProduct = $this->findProductIdByTransactionId($singleReceipt->original_transaction_id, Cart::PAYMENTTYPE_APPSTORE);

                            if (!isset($availableProduct) && sizeof($purchases) > 0) {
                                /** @var PurchaseInterface $purchase */
                                foreach ($purchases as $purchase) {
                                    if ($singleReceipt->original_transaction_id === $purchase->getTransactionId()) {
                                        $availableProduct = $purchase->getPurchaseType();

                                        break;
                                    }
                                }
                            }
                        }

                        if (isset($availableProduct)) {
                            $availableProducts = [$availableProduct];
                        } else {
                            $availableProducts = AbstractPurchase::getAvailableProducts($user, $this);
                        }

                        if (!in_array($pid, $availableProducts)) {
                            throw new VerificationException($user, $json, $this, "Invalid product id");
                        }

                        if (AbstractSubscription::isSubscription($pid)) {
                            $p = AbstractSubscription::create($pid, $user, Cart::PAYMENTTYPE_APPSTORE, $singleReceipt->transaction_id, $transactionDate);

                            if (property_exists($singleReceipt, 'expires_date') && !empty($singleReceipt->expires_date) && is_numeric($singleReceipt->expires_date)) {
                                $p->setExpiresDate(new \DateTime("@" . intval($singleReceipt->expires_date / 1000)));
                            }
                            $p->setRecurring($this->isRecurring($singleReceipt));
                        } else {
                            $p = AbstractConsumable::create($pid, $user, Cart::PAYMENTTYPE_APPSTORE, $singleReceipt->transaction_id, $transactionDate);
                        }

                        if (property_exists($singleReceipt, 'web_order_line_item_id')) {
                            $p->setSecondaryTransactionId((int) $singleReceipt->web_order_line_item_id);
                        }

                        if (property_exists($singleReceipt, 'cancellation_date') && !empty($singleReceipt->cancellation_date) && is_numeric($singleReceipt->cancellation_date)) {
                            $p->setCancellationDate(new \DateTime("@" . intval($singleReceipt->cancellation_date / 1000)));
                        }
                        $p->setUserToken(isset($appStoreReceipt) && $appStoreReceipt != $receipt ? $appStoreReceipt : null);
                        $p->setPurchaseToken($receipt);

                        $purchases[] = $p;
                    }
                } catch (QuietVerificationException $e) {
                    $this->logger->warning(sprintf("In-App Purchase verification exception: %s", $e->getMessage()), $e->getContext());
                } catch (VerificationException $e) {
                    $this->logger->error(sprintf("In-App Purchase verification exception: %s", $e->getMessage()), $e->getContext());
                }
            }

            return $purchases;
        } else {
            $status = property_exists($json, 'status') ? $json->status : self::RESULT_DATA_MALFORMED;
            $this->handleResponseStatus($status, $json, $currentUser);
        }
    }

    public function getPlatformId(): string
    {
        return 'ios';
    }

    public function getCompanyName(): string
    {
        return 'Apple';
    }

    public function findSubscriptions(Usr $user): array
    {
        $purchases = [];

        try {
            if (!empty($receipt = $user->getIosReceipt())) {
                $purchases = array_merge($purchases, $this->validate([
                    'type' => 'ios-appstore',
                    'appStoreReceipt' => $receipt,
                ], $user, [self::OPTION_ENABLE_SCAN_USERS => false]));
            }
        } catch (QuietVerificationException $e) {
            $this->logger->warning(sprintf("In-App Purchase verification exception: %s", $e->getMessage()), array_merge($e->getContext(), [
                'appStoreReceipt' => $receipt,
            ]));
        } catch (VerificationException $e) {
            $this->logger->critical(sprintf("In-App Purchase verification exception: %s", $e->getMessage()), array_merge($e->getContext(), [
                'appStoreReceipt' => $receipt,
            ]));
        }

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
            [$user->getUserid(), Cart::PAYMENTTYPE_APPSTORE, [AwPlusSubscription::TYPE, AwPlusWeekSubscription::TYPE]],
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

            try {
                $purchases = array_merge($purchases, $this->validate([
                    'type' => 'ios-appstore',
                    'transactionReceipt' => $token,
                ], $user, [self::OPTION_ENABLE_SCAN_USERS => false]));
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
        try {
            if (!empty($receipt = $user->getIosReceipt())) {
                $purchases = $this->validate([
                    'type' => 'ios-appstore',
                    'appStoreReceipt' => $receipt,
                ], $user, [self::OPTION_ENABLE_SCAN_USERS => false, self::OPTION_CANCEL_SUBSCRIPTION => true]);

                foreach ($purchases as $purchase) {
                    $billing->processing($purchase);
                }
            }
        } catch (QuietVerificationException $e) {
            $this->logger->warning(sprintf("In-App Purchase verification exception: %s", $e->getMessage()), array_merge($e->getContext(), [
                'appStoreReceipt' => $receipt,
            ]));
        } catch (VerificationException $e) {
            $this->logger->critical(sprintf("In-App Purchase verification exception: %s", $e->getMessage()), array_merge($e->getContext(), [
                'appStoreReceipt' => $receipt,
            ]));
        }

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
            [$user->getUserid(), Cart::PAYMENTTYPE_APPSTORE, [AwPlusSubscription::TYPE, AwPlusWeekSubscription::TYPE]],
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

            try {
                $purchases = $this->validate([
                    'type' => 'ios-appstore',
                    'transactionReceipt' => $token,
                ], $user, [self::OPTION_ENABLE_SCAN_USERS => false]);

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

    protected function findBidId(string $purchaseReceiptEncoded): ?string
    {
        $purchaseReceipt = $this->dumbParsePlaintextPropertyList(@\base64_decode($purchaseReceiptEncoded ?: ''));

        if (!$purchaseReceipt) {
            return null;
        }

        $purchaseInfo = $this->dumbParsePlaintextPropertyList(@\base64_decode($purchaseReceipt['purchase-info'] ?? ''));

        return $purchaseInfo['bid'] ?? null;
    }

    protected function dumbParsePlaintextPropertyList(string $propertyList): array
    {
        $result = [];

        foreach (\explode("\n", $propertyList) as $line) {
            $line = \trim($line);
            $eqParts = \explode(' = ', $line);

            if (\count($eqParts) !== 2) {
                continue;
            }

            $key = \substr($eqParts[0], 1, \strlen($eqParts[0]) - 2);
            $value = \substr($eqParts[1], 1, \strlen($eqParts[1]) - 3);
            $result[$key] = $value;
        }

        return $result;
    }

    private function getStatusText(int $status): string
    {
        $errors = self::ERRORS;

        if (isset($errors[$status])) {
            return $errors[$status];
        }

        return $errors[self::RESULT_DATA_MALFORMED];
    }

    private function handleResponseStatus($status, $data, ?Usr $user = null)
    {
        $message = "Invalid response status: " . $this->getStatusText($status);

        if (in_array($status, [self::RESULT_SANDBOX_RECEIPT_SENT_TO_PRODUCTION, self::RESULT_DATA_MALFORMED])) {
            throw new QuietVerificationException($user, $data, $this, $message);
        }

        throw (new VerificationException($user, $data, $this, $message))->setTemporary($status === self::RESULT_RECEIPT_SERVER_UNAVAILABLE);
    }

    private function findTransactions($json, string $receipt, ?Usr $currentUser = null, ?string $transactionId = null, $enableScanUsers = true)
    {
        $this->log("find transactions", $currentUser, [
            "transactionId" => $transactionId,
        ]);

        if (!isset($json)) {
            return [];
        }

        if (property_exists($json, 'latest_receipt_info')) {
            $info = $json->latest_receipt_info;
        } elseif (property_exists($json, 'receipt') && property_exists($json->receipt, 'in_app')) {
            $info = $json->receipt->in_app;
        } else {
            return [];
        }

        $this->log("found receipts: " . (is_array($info) ? count($info) : 0), $currentUser);

        if (!is_array($info) || !$info) {
            if ($currentUser && $currentUser->getCarts()->exists(function ($k, $cart) {
                /** @var Cart $cart */
                return $cart->isPaid() && $cart->getPaymenttype() === Cart::PAYMENTTYPE_APPSTORE && $cart->isAwPlusSubscription();
            })) {
                $this->log("empty receipt, user has previously subscribed");
            }

            return [];
        }

        $purchases = [];

        foreach ($info as $purchase) {
            if (
                property_exists($purchase, 'transaction_id')
                && !empty($purchase->transaction_id) && (is_null($transactionId) || $transactionId === $purchase->transaction_id)
                && property_exists($purchase, 'purchase_date_ms')
                && !empty($purchase->purchase_date_ms) && is_numeric($purchase->purchase_date_ms)
            ) {
                $purchases[$purchase->transaction_id] = $purchase;
            }
        }

        $this->log("filtered receipts: " . count($purchases), $currentUser);

        if (!sizeof($purchases)) {
            return [];
        }

        $result = [];

        foreach ($purchases as $transactionId => $purchase) {
            $pid = $this->getProductId($purchase->product_id);

            if (!$pid) {
                continue;
            }
            // detect user
            $user = $this->userDetector->detect($pid, $purchase, $this, $currentUser, $enableScanUsers);
            $this->log(sprintf("transaction #%s", $transactionId), $currentUser, [
                "pid" => $pid,
                "detectedUser" => isset($user) ? $user->getUserid() : "null",
            ]);

            if (
                !isset($user)
                || !property_exists($purchase, 'purchase_date_ms')
                || empty($purchase->purchase_date_ms)
                || !is_numeric($purchase->purchase_date_ms)
            ) {
                continue;
            }

            $transactionDate = new \DateTime("@" . intval($purchase->purchase_date_ms / 1000));

            // get available products
            $availableProduct = null;

            if (
                property_exists($purchase, 'original_transaction_id')
                && !empty($purchase->original_transaction_id)
                && strcmp($transactionId, $purchase->original_transaction_id) != 0
            ) {
                $availableProduct = $this->findProductIdByTransactionId($purchase->original_transaction_id, Cart::PAYMENTTYPE_APPSTORE);
            }

            if (isset($availableProduct)) {
                $availableProducts = [$availableProduct];
            } else {
                $availableProducts = AbstractPurchase::getAvailableProducts($user, $this);
            }

            if (!in_array($pid, $availableProducts)) {
                $this->log("unacceptable product", $currentUser, [
                    "pid" => $pid,
                    "available" => var_export($availableProducts, true),
                ]);

                continue;
            }

            if (AbstractSubscription::isSubscription($pid)) {
                $p = AbstractSubscription::create($pid, $user, Cart::PAYMENTTYPE_APPSTORE, $transactionId, $transactionDate);

                if (property_exists($purchase, 'expires_date_ms') && !empty($purchase->expires_date_ms) && is_numeric($purchase->expires_date_ms)) {
                    $p->setExpiresDate(new \DateTime("@" . intval($purchase->expires_date_ms / 1000)));
                }
                $p->setRecurring($this->isRecurring($purchase));
            } else {
                $p = AbstractConsumable::create($pid, $user, Cart::PAYMENTTYPE_APPSTORE, $transactionId, $transactionDate);
            }

            if (property_exists($purchase, 'web_order_line_item_id')) {
                $p->setSecondaryTransactionId((int) $purchase->web_order_line_item_id);
            }

            if (property_exists($purchase, 'cancellation_date') && !empty($purchase->cancellation_date)) {
                $p->setCancellationDate(new \DateTime($purchase->cancellation_date));
            }
            $p->setUserToken($receipt);

            $result[] = $p;
        }

        return $result;
    }

    private function isRecurring($receipt): bool
    {
        /**
         * https://developer.apple.com/library/content/releasenotes/General/ValidateAppStoreReceipt/Chapters/ReceiptFields.html
         * JSON Field Name original_transaction_id
         * This value is the same for all receipts that have been generated for a specific subscription. This value is useful for relating together multiple iOS 6 style transaction receipts for the same individual customer’s subscription.
         */
        return
            property_exists($receipt, "original_transaction_id")
            && property_exists($receipt, "transaction_id")
            && $receipt->original_transaction_id != $receipt->transaction_id
        ;
    }

    private function log(string $message, ?Usr $user = null, array $extraData = []): void
    {
        $this->logger->info($message, array_merge(
            LoggerContext::get($user),
            $extraData
        ));
    }

    private function checkSubscriptionStatus($json, Usr $user): void
    {
        if ($user->getSubscription() !== Usr::SUBSCRIPTION_MOBILE) {
            return;
        }

        $renewalInfo = $json->pending_renewal_info[0] ?? null;

        if ($renewalInfo === null) {
            return;
        }

        if ($renewalInfo->auto_renew_status !== '0') {
            return;
        }

        $this->log("ios subscription is not auto-renewable, removing subscription: " . json_encode($renewalInfo), $user);
        $user->clearSubscription();
        $this->em->flush();
    }
}
