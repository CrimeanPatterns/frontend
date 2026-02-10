<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem\AwPlus;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusGift;
use AwardWallet\MainBundle\Entity\Repositories\CartRepository;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\RecurringPaymentFailed;
use AwardWallet\MainBundle\Globals\Cart\AT201SubscriptionInterface;
use AwardWallet\MainBundle\Globals\Cart\AwPlusUpgradableInterface;
use AwardWallet\MainBundle\Globals\Cart\Manager;
use AwardWallet\MainBundle\Globals\Cart\SubscriptionPeriod;
use AwardWallet\MainBundle\Globals\Cart\SubscriptionPrice;
use AwardWallet\MainBundle\Globals\Cart\UpgradeCodeGenerator;
use AwardWallet\MainBundle\Service\Billing\ExpirationCalculator;
use AwardWallet\MainBundle\Service\Billing\PaypalRestApi;
use AwardWallet\MainBundle\Service\Billing\PlusManager;
use AwardWallet\MainBundle\Service\Billing\StripeOffSessionCharger;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Logger;
use PayPal\Exception\PayPalConnectionException;
use Psr\Log\LoggerInterface;
use Stripe\Exception\CardException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\RouterInterface;

class BillCardsCommand extends Command
{
    public static $defaultName = 'aw:bill-cards';

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var UsrRepository
     */
    private $userRep;
    /**
     * @var ExpirationCalculator
     */
    private $expirationCalculator;

    /**
     * @var CartRepository
     */
    private $cartRep;

    /**
     * @var PaypalRestApi
     */
    private $paypalApi;

    /**
     * @var PlusManager
     */
    private $plusManager;

    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var RouterInterface
     */
    private $router;
    /**
     * @var UpgradeCodeGenerator
     */
    private $upgradeCodeGenerator;
    private Connection $connection;
    private Mailer $mailer;
    private Manager $cartManager;
    private StripeOffSessionCharger $charger;
    private InputInterface $input;

    public function __construct(
        RouterInterface $router,
        UpgradeCodeGenerator $upgradeCodeGenerator,
        UsrRepository $userRep,
        ExpirationCalculator $expirationCalculator,
        CartRepository $cartRep,
        PaypalRestApi $paypalApi,
        PlusManager $plusManager,
        EntityManagerInterface $em,
        Connection $unbufConnection,
        Connection $connection,
        LoggerInterface $paymentLogger,
        Mailer $mailer,
        Manager $cartManager,
        StripeOffSessionCharger $charger
    ) {
        parent::__construct();

        $this->router = $router;
        $this->upgradeCodeGenerator = $upgradeCodeGenerator;
        $this->userRep = $userRep;
        $this->expirationCalculator = $expirationCalculator;
        $this->cartRep = $cartRep;
        $this->paypalApi = $paypalApi;
        $this->plusManager = $plusManager;
        $this->em = $em;
        $this->unbufConnection = $unbufConnection;
        $this->connection = $connection;
        $this->logger = $paymentLogger;
        $this->mailer = $mailer;
        $this->cartManager = $cartManager;
        $this->charger = $charger;
    }

    protected function configure()
    {
        $this
            ->setDescription('Bill user credit cards, renew membership, should be run every day')
            ->addOption('userId', 'u', InputOption::VALUE_REQUIRED, 'UserID filter')
            ->addOption('force', null, InputOption::VALUE_NONE, 'bill user ignoring any condition, DANGER!')
            ->addOption('send-failure', null, InputOption::VALUE_REQUIRED, 'send failure message for this user and exit')
            ->addOption('recover', 'r', InputOption::VALUE_NONE, 'try to recover downgraded users')
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'dry run, do not modify anything')
            ->addOption('retry-count', null, InputOption::VALUE_REQUIRED, 'paypal api max retries', 3)
            ->addOption('limit', null, InputOption::VALUE_REQUIRED)
            ->addOption('paypal-only', null, InputOption::VALUE_NONE)
            ->addOption('new-pricing', null, InputOption::VALUE_NONE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $limitToUserId = $input->getOption('userId');
        $dryRun = !empty($input->getOption('dry-run'));

        if ($sendFailure = $input->getOption('send-failure')) {
            /** @var Usr $user */
            $user = $this->userRep->find($sendFailure);
            $this->sendFailure(
                $user,
                false,
                1234, 30,
                new \DateTime("-1 week"),
                Usr::SUBSCRIPTION_TYPE_AWPLUS,
                SubscriptionPeriod::DURATION_1_YEAR
            );

            return 0;
        }

        if ($dryRun) {
            $output->writeln("dry run");
        }
        $this->logger->pushProcessor(function (array $record) {
            $record['context']['worker'] = 'billcards';

            return $record;
        });
        $this->cartRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Cart::class);
        $errors = 0;

        $this->logger->info("billing user cards");

        $conditions = [];

        if (!empty($input->getOption("recover"))) {
            $conditions[] = "AccountLevel = " . ACCOUNT_LEVEL_FREE . "
                            and Subscription in (" . Usr::SUBSCRIPTION_SAVED_CARD . ", " . Usr::SUBSCRIPTION_STRIPE . ")
                            and PaypalRecurringProfileID is not null
                            and (DATEDIFF(now(), PlusExpirationDate) > 3 or PlusExpirationDate is null)
                            and FailedRecurringPayments = 0";
        } else {
            $conditions[] = "AccountLevel = " . ACCOUNT_LEVEL_AWPLUS . "
                            and Subscription in (" . Usr::SUBSCRIPTION_SAVED_CARD . ", " . Usr::SUBSCRIPTION_STRIPE . ")
                            and SubscriptionType = " . Usr::SUBSCRIPTION_TYPE_AWPLUS . "
                            and PlusExpirationDate <= now()";
            $conditions[] = "AccountLevel = " . ACCOUNT_LEVEL_AWPLUS . "
                            and Subscription in (" . Usr::SUBSCRIPTION_SAVED_CARD . ", " . Usr::SUBSCRIPTION_STRIPE . ")
                            and SubscriptionType = " . Usr::SUBSCRIPTION_TYPE_AT201 . "
                            and AT201ExpirationDate <= now()";
            // try to bill users with refused card in 3 and 6 days after downgrade
            $conditions[] = "AccountLevel = " . ACCOUNT_LEVEL_FREE . "
                            and Subscription in (" . Usr::SUBSCRIPTION_SAVED_CARD . ", " . Usr::SUBSCRIPTION_STRIPE . ")
                            and SubscriptionType = " . Usr::SUBSCRIPTION_TYPE_AWPLUS . "
                            and PaypalRecurringProfileID is not null
                            and DATEDIFF(now(), PlusExpirationDate) in (3, 6)";
        }

        if ($input->getOption('force') && $input->getOption('userId')) {
            $conditions[] = "UserID = " . (int) $input->getOption('userId');
        }

        $sql = "
        select 
            UserID,
            AccountLevel
        from 
            Usr 
        where 
            ((" . implode(") \nor (", $conditions) . "))";

        if (!empty($limitToUserId)) {
            $this->logger->info("limited to $limitToUserId");
            $sql .= " and UserID = " . intval($limitToUserId);
        }

        if ($input->getOption('paypal-only')) {
            $sql .= " and Subscription = " . Usr::SUBSCRIPTION_SAVED_CARD;
        }

        if ($input->getOption('limit')) {
            $sql .= " limit " . $input->getOption('limit');
        }

        $this->logger->info("querying..", ["sql" => $sql]);
        $q = $this->unbufConnection->executeQuery($sql);
        $q->execute();
        $this->logger->info("got data");

        while ($user = $q->fetch(\PDO::FETCH_ASSOC)) {
            if (!$this->processUser($user['UserID'], $user['AccountLevel'], !empty($limitToUserId), $dryRun, $input->getOption('retry-count'))) {
                $errors++;
            }
        }

        $this->logger->log($errors > 0 ? Logger::ERROR : Logger::INFO, "done, errors: $errors");

        return $errors === 0 ? 0 : 123;
    }

    /**
     * @return bool - processed without critical errors, payment failure like expired card is not critical
     */
    private function processUser($userId, $accountLevel, $force, $dryRun, int $retryCount): bool
    {
        $result = false;
        /** @var Usr $user */
        $user = $this->userRep->find($userId);
        $subscrType = $user->getSubscriptionType();

        $plusExpiration = $this->expirationCalculator->getAccountExpiration($userId);

        $this->plusManager->correctExpirationDate($user, $plusExpiration['date'], 'expiration recalculated');

        switch ($subscrType) {
            case Usr::SUBSCRIPTION_TYPE_AWPLUS:
                $expiration = $plusExpiration;

                break;

            case Usr::SUBSCRIPTION_TYPE_AT201:
                $expiration = $this->expirationCalculator->getAccountExpiration(
                    $userId,
                    AT201SubscriptionInterface::class
                );

                break;
        }

        $this->logger->pushProcessor(function (array $record) use ($userId, $accountLevel, $expiration, $subscrType) {
            $record['context']['UserID'] = $userId;
            $record['context']['Level'] = $accountLevel;
            $record['context']['ExpirationDate'] = date("Y-m-d", $expiration['date']);
            $record['context']['LastPay'] = $expiration['lastPrice'];
            $record['context']['SubscriptionType'] = $subscrType;

            return $record;
        });

        try {
            $this->logger->info('processing');

            if ($force || $expiration['date'] < (time() + SECONDS_PER_DAY * 7)) {
                $this->logger->info('upgrading');

                /** @var Cart $cart */
                $cart = $this->cartRep->getActiveAwSubscription($user);

                if (empty($cart)) {
                    $this->logger->critical("active subscription not found");

                    return false;
                }
                $this->logger->info("active subscription, cart: " . $cart->getCartid() . ", payment type: " . $cart->getPaymenttype());

                if ($cart->getPaymenttype() === PAYMENTTYPE_BITCOIN) {
                    $this->logger->info("subscription with bitcoin not supported, ignore");

                    return true;
                }

                if ($cart->getPaymenttype() != PAYMENTTYPE_CREDITCARD && $cart->getPaymenttype() != PAYMENTTYPE_STRIPE_INTENT && $cart->getPaymenttype() != PAYMENTTYPE_PAYPAL) {
                    $this->logger->critical("invalid payment type for previous subscription");

                    return false;
                }
                $try = 0;

                do {
                    [$result, $needRetry] = $this->upgrade($user, $cart, $dryRun);

                    if ($needRetry && $try < $retryCount) {
                        $pause = 30 * (2 ** $try);
                        $this->logger->info("try $try failed, will retry after $pause seconds");
                        sleep($pause);
                    }
                    $try++;
                } while ($needRetry && $try < $retryCount);
            } else {
                $this->logger->info("skipping");
            }

            return $result;
        } finally {
            $this->logger->popProcessor();
        }
    }

    /**
     * @return [
     *      bool - processed without critical errors, payment failure like expired card is not critical,
     *      bool - need retry
     * ]
     */
    private function upgrade(Usr $user, Cart $oldCart, $dryRun): array
    {
        $result = false;
        $needRetry = false;

        /** @var Manager $cartManager */
        $cartManager = $this->cartManager;
        $cartManager->setUser($user);

        $cart = $cartManager->createNewCart();
        $cart->setPaymenttype($oldCart->getPaymenttype());

        if (($cart->getPaymenttype() === Cart::PAYMENTTYPE_CREDITCARD || $cart->getPaymenttype() === Cart::PAYMENTTYPE_PAYPAL) && $user->getSubscription() === Usr::SUBSCRIPTION_STRIPE) {
            $this->logger->info("correcting payment type from credit card to stripe - stripe migration");
            $cart->setPaymenttype(Cart::PAYMENTTYPE_STRIPE_INTENT);
        }
        $cart->setSource(Cart::SOURCE_RECURRING);

        $cart->setBilladdress1($oldCart->getBilladdress1());
        $cart->setBilladdress2($oldCart->getBilladdress2());
        $cart->setBillcity($oldCart->getBillcity());
        $cart->setBillstate($oldCart->getBillstate());
        $cart->setBillfirstname($oldCart->getBillfirstname());
        $cart->setBilllastname($oldCart->getBilllastname());
        $cart->setBillcountry($oldCart->getBillcountry());
        $cart->setBillzip($oldCart->getBillzip());
        $cart->setCreditcardnumber($oldCart->getCreditcardnumber());
        $cart->setCreditcardtype($oldCart->getCreditcardtype());

        // charge 1Y/$49.99 no matter what last cart was
        $duration = SubscriptionPeriod::DAYS_TO_DURATION[$user->getSubscriptionPeriod()];
        $price = SubscriptionPrice::getPrice($user->getSubscriptionType(), $duration);
        $this->logger->info("will charge with new pricing, duration: $duration, price: $price");
        $cartManager->addSubscription(
            $user->getSubscriptionType(),
            $user->getSubscriptionType() === Usr::SUBSCRIPTION_TYPE_AWPLUS
                ? SubscriptionPeriod::DURATION_1_YEAR
                : SubscriptionPeriod::DAYS_TO_DURATION[$user->getSubscriptionPeriod()]
        );
        $oldGift = $oldCart->getItemsByType([AwPlusGift::TYPE])->first();

        if ($oldGift) {
            $this->logger->info("copying gift item", ["GiftID" => $oldGift->getId(), "UserID" => $oldCart->getUser()->getId(), "CartID" => $oldCart->getCartid()]);
            $newGift = clone $oldGift;
            $cart->addItem($newGift);
        }

        $cartManager->save($cart);

        $total = $cart->getTotalPrice();

        if ($total == 0) {
            throw new \Exception("Total is 0");
        }
        $context = ["CartID" => $cart->getCartid(), "Total" => $total];

        if ($dryRun) {
            $this->logger->warning("dry-run, want to upgrade", $context);
        } else {
            try {
                if ($cart->getPaymenttype() == PAYMENTTYPE_CREDITCARD) {
                    $transactionId = $this->paypalApi->payWithSavedCard($cart, $user->getPaypalrecurringprofileid(), $total);
                } elseif ($cart->getPaymenttype() == PAYMENTTYPE_STRIPE_INTENT) {
                    $transactionId = $this->payWithStripe($cart);
                } else {
                    throw new \Exception("unknown payment type");
                }
                $context = array_merge($context, ["TransactionID" => $transactionId]);
                $this->logger->info("successfully paid cart", $context);
                $cart->setBillingtransactionid($transactionId);
                $cartManager->markAsPayed($cart);
                $this->logger->info("upgrade completed", $context);
                $result = true;
            } catch (PayPalConnectionException $e) {
                $data = @json_decode($e->getData(), true);
                $this->logger->warning("paypal api exception: ", array_merge($context, ['paypal_error' => $data]));

                if (!empty($data['name']) && in_array($data['name'], ['UNKNOWN_ERROR', 'INTERNAL_SERVICE_ERROR', 'DCC_PREPROCESSOR_ERROR'])) {
                    // trying to detect expired card, workaround about paypal bug:
                    // https://github.com/paypal/PayPal-REST-API-issues/issues/183
                    $cardInfo = $this->paypalApi->getCardInfo($user->getPaypalrecurringprofileid());

                    if ($cardInfo->expire_year < date("Y") || ($cardInfo->expire_year == date("Y") && $cardInfo->expire_month < date("m"))) {
                        $this->logger->warning("credit card expired", ["expire_year" => $cardInfo->expire_year, "expire_month" => $cardInfo->expire_month]);
                        $data['name'] = 'EXPIRED_CREDIT_CARD';
                    } else {
                        $this->logger->warning("credit card looks ok", ["expire_year" => $cardInfo->expire_year, "expire_month" => $cardInfo->expire_month]);
                    }
                }

                if (!empty($data['name']) && in_array($data['name'], ['CREDIT_CARD_REFUSED', 'EXPIRED_CREDIT_CARD', 'CARD_EXPIRY_DATE_INVALID', 'INVALID_RESOURCE_ID', 'INVALID_CC_EXP_YEAR', 'INSTRUMENT_DECLINED', 'DCC_PREPROCESSOR_ERROR', 'EXPIRED_CREDIT_CARD_TOKEN'])) {
                    $this->logger->warning("credit card refused", $context);
                    $cart->getUser()->setFailedRecurringPayments($cart->getUser()->getFailedRecurringPayments() + 1);
                    $this->em->flush();

                    $plusItem = $cart->getPlusItem();

                    try {
                        $cardInfo = $this->paypalApi->getCardInfo($user->getPaypalrecurringprofileid());
                        $lastFour = substr($cardInfo->number, -4);
                    } catch (PayPalConnectionException $exception) {
                        $this->logger->warning("failed to get cc info: {$exception->getMessage()} will read from db");
                        $lastFour = substr($cart->getCreditcardnumber(), -4);
                    }

                    $this->sendFailure(
                        $user,
                        $plusItem instanceof AwPlusUpgradableInterface && !is_subclass_of($plusItem, AwPlus::class),
                        $lastFour,
                        $total,
                        new \DateTime($cart->getPlusItem()->getDuration()),
                        $user->getSubscriptionType(),
                        $cart->getSubscriptionItem()->getDuration()
                    );

                    $result = true;
                } elseif (!empty($data['name']) && in_array($data['name'], ['INVALID_RESOURCE_ID'])) {
                    $this->logger->critical("credit card not found", $context);
                } else {
                    $this->logger->critical("unknown error from paypal", $context);
                    $needRetry = true;
                }
            } catch (CardException $exception) {
                $this->logger->warning("stripe billcards payment error: " . $exception->getMessage() . ", stripe code: " . $exception->getStripeCode() . ", decline code: " . $exception->getDeclineCode() . ", code: " . $exception->getCode() . ", http body: " . $exception->getHttpBody() . ", json body: " . json_encode($exception->getJsonBody()));
                $cart->getUser()->setFailedRecurringPayments($cart->getUser()->getFailedRecurringPayments() + 1);
                // I'm not sure what will be in last4 when using other than "Card" payment method
                $lastFour = $exception->getJsonBody()["error"]["payment_intent"]["last_payment_error"]["payment_method"]["card"]["last4"] ?? "xxxx";
                $this->logger->info("card last four: $lastFour");
                $plusItem = $cart->getPlusItem();
                $this->sendFailure(
                    $user,
                    $plusItem instanceof AwPlusUpgradableInterface && !is_subclass_of($plusItem, AwPlus::class),
                    $lastFour,
                    $total,
                    new \DateTime($cart->getPlusItem()->getDuration()),
                    $user->getSubscriptionType(),
                    $cart->getSubscriptionItem()->getDuration()
                );

                if (stripos($exception->getMessage(), 'Invalid saved card: pm_') !== false) {
                    $this->logger->critical("invalid stripe saved card, will cleanup and cancel subscription");
                    $user->clearSubscription();
                }

                // will retry in next days, give him time
                //                if (in_array($exception->getStripeCode(), ["card_declined", "generic_decline"])) {
                //                    $user->setAccountlevel(ACCOUNT_LEVEL_FREE);
                //                }
                $this->em->persist($cart->getUser());
                $this->em->flush();
                $result = true;
            }
        }

        return [$result, $needRetry];
    }

    private function sendFailure(
        Usr $user,
        bool $semiAnnual,
        string $cardLastFour,
        int $total,
        \DateTimeInterface $expirationDate,
        int $subscriptionType,
        string $duration
    ): void {
        $template = new RecurringPaymentFailed($user);
        $template->semiAnnualSubscription = $semiAnnual;
        $template->paymentSource = RecurringPaymentFailed::PAYMENT_SOURCE_CC;
        $template->ccNumber = $cardLastFour;
        $template->amount = $total;
        $template->throughDate = $expirationDate;
        $template->paymentLink = $this->router->generate('aw_cart_change_payment_method_email', ['userId' => $user->getId(), "hash" => $this->upgradeCodeGenerator->generateCode($user)]);
        $template->subscriptionType = $subscriptionType;
        $template->subscriptionPeriod = $duration;
        $message = $this->mailer->getMessageByTemplate($template);
        $this->mailer->send($message, [Mailer::OPTION_SKIP_DONOTSEND => true]);
    }

    private function payWithStripe(Cart $cart): string
    {
        try {
            $customerId = $cart->getUser()->getStripeCustomerId();
            /** @var AwPlusGift $gift */
            $gift = $cart->getItemsByType([AwPlusGift::TYPE])->first();

            if ($gift) {
                $this->logger->info("will charge gift giver: " . $gift->getGiverId());
                $customerId = $this->userRep->find($gift->getGiverId())->getStripeCustomerId();
            }

            return $this->charger->charge($customerId, $cart->getUser()->getPaypalrecurringprofileid(), $cart->getTotalPrice(), $cart->getCartid(), $cart->getUser()->getId());
        } catch (CardException $stripeException) {
            $this->logger->warning("stripe billcards payment error (before paypal retry): " . $stripeException->getMessage() . ", stripe code: " . $stripeException->getStripeCode() . ", decline code: " . $stripeException->getDeclineCode() . ", code: " . $stripeException->getCode() . ", http body: " . $stripeException->getHttpBody() . ", json body: " . json_encode($stripeException->getJsonBody()));

            if (substr($cart->getUser()->getOldPaypalRecurringProfileId(), 0, 5) === 'CARD-') {
                $this->logger->info("fallback to paypal card: " . $cart->getUser()->getOldPaypalRecurringProfileId());
                $cart->setPaymenttype(Cart::PAYMENTTYPE_CREDITCARD);
                $this->cartManager->save($cart);

                try {
                    $result = $this->paypalApi->payWithSavedCard($cart, $cart->getUser()->getOldPaypalRecurringProfileId(), $cart->getTotalPrice());
                    $this->logger->info("paypal succeeded after failed stripe");

                    return $result;
                } catch (PayPalConnectionException $e) {
                    $data = @json_decode($e->getData(), true);
                    $this->logger->warning("paypal api exception: " . $e->getMessage(), ['paypal_error' => $data]);

                    throw $stripeException;
                }
            }

            throw $stripeException;
        }
    }
}
