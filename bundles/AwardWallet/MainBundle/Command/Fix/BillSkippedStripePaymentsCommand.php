<?php

namespace AwardWallet\MainBundle\Command\Fix;

use AwardWallet\MainBundle\Command\BillCardsCommand;
use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem\Supporters3MonthsUpgrade;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\Cart\AT201SubscriptionInterface;
use AwardWallet\MainBundle\Globals\Cart\SubscriptionPeriod;
use AwardWallet\MainBundle\Globals\Cart\SubscriptionPrice;
use AwardWallet\MainBundle\Globals\LoggerContext\ContextAwareLoggerWrapper;
use AwardWallet\MainBundle\Service\Billing\ExpirationCalculator;
use AwardWallet\MainBundle\Service\ElasticSearch\Client;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Stripe\PaymentMethod;
use Stripe\StripeClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class BillSkippedStripePaymentsCommand extends Command
{
    protected static $defaultName = 'aw:fix:bill-skipped-stripe-payments';
    private ContextAwareLoggerWrapper $logger;
    private Client $elastic;
    private InputInterface $input;
    private Connection $connection;
    private \DateTime $startDate;
    private \DateTime $endDate;
    private StripeClient $stripe;
    private EntityManagerInterface $entityManager;
    private ExpirationCalculator $expirationCalculator;
    private BillCardsCommand $billCardsCommand;
    private OutputInterface $output;

    public function __construct(
        LoggerInterface $paymentLogger,
        Client $elastic,
        Connection $connection,
        StripeClient $stripe,
        EntityManagerInterface $entityManager,
        ExpirationCalculator $expirationCalculator,
        BillCardsCommand $billCardsCommand
    ) {
        parent::__construct();
        $this->logger = new ContextAwareLoggerWrapper($paymentLogger);
        $this->logger->pushContext(['command' => self::$defaultName]);
        $this->elastic = $elastic;
        $this->connection = $connection;
        $this->stripe = $stripe;
        $this->entityManager = $entityManager;
        $this->expirationCalculator = $expirationCalculator;
        $this->billCardsCommand = $billCardsCommand;
    }

    public function configure()
    {
        $this
            ->addOption('userId', null, InputOption::VALUE_REQUIRED)
            ->addOption('apply', null, InputOption::VALUE_NONE)
            ->addOption('recover-limit', null, InputOption::VALUE_REQUIRED)
            ->addOption('bill', null, InputOption::VALUE_NONE)
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->logger->info("Start fixing skipped Stripe payments");
        $this->input = $input;
        $this->output = $output;
        $this->startDate = new \DateTime('2024-12-01');
        $this->endDate = new \DateTime('today +1 week');
        $recovered = it($this->loadMissedPayments())
            ->map(fn (array $row) => ['UserID' => $row['context']['UserID'], 'DowngradeDate' => new \DateTime(substr($row['@timestamp'], 0, strlen('2025-01-29T11:17:45'))), 'RequestID' => $row['RequestID']])
            ->flatMap(function (array $row) {
                $result = $this->connection->fetchAssociative(
                    'SELECT FirstName, LastName, DefaultTab, AccountLevel, Subscription FROM Usr WHERE UserID  = ?',
                    [$row['UserID']]
                );

                if ($result === false) {
                    $this->logger->info("user not found in database", ["UserID" => $row['UserID']]);

                    return [];
                }

                return [array_merge($row, $result)];
            })
            ->filter(function (array $row) {
                if ($row['Subscription'] !== null) {
                    $this->logger->info("user already has subscription {$row['Subscription']}", ["UserID" => $row['UserID']]);

                    return false;
                }

                return true;
            })
            ->filter(function (array $row) {
                if (substr($row['DefaultTab'], 0, 4) !== 'cus_') {
                    $this->logger->info("DefaultTab does not contain stripe customer", ["UserID" => $row['UserID']]);

                    return false;
                }

                return true;
            })
            ->flatMap(function (array $row) {
                $q = $this->entityManager->createQuery("select c from AwardWalletMainBundle:Cart c where c.user = :user and c.paydate < :date and c.paymenttype = :paymentType order by c.paydate desc");
                $carts = $q->execute(["user" => $row['UserID'], "date" => $row['DowngradeDate'], "paymentType" => Cart::PAYMENTTYPE_STRIPE_INTENT]);
                /** @var Cart $cart */
                $cart = null;

                foreach ($carts as $c) {
                    /** @var Cart $c */
                    if ($c->getSubscriptionItem()) {
                        $cart = $c;

                        break;
                    }
                }

                if ($cart === null) {
                    $this->logger->info("no last cart found", ["UserID" => $row['UserID']]);

                    return [];
                }

                $this->logger->info("last cart found, cart id: {$cart->getCartid()}", ["UserID" => $row['UserID']]);
                $result = $cart->getSubscriptionItem()->getCartitemid();
                $subscriptionType = $cart->getSubscriptionItem() instanceof AT201SubscriptionInterface ? Usr::SUBSCRIPTION_TYPE_AT201 : Usr::SUBSCRIPTION_TYPE_AWPLUS;

                if ($cart->getPaydate()->getTimestamp() < strtotime("2023-11-01")) {
                    $this->logger->info("last cart was paid too long time ago: " . $cart->getPaydate()->format("Y-m-d"), ["UserID" => $row['UserID']]);

                    return [];
                }

                return [array_merge($row, [
                    'LastSubscriptionCartItemID' => $result,
                    'SubscriptionPeriod' => SubscriptionPeriod::DURATION_TO_DAYS[$cart->getSubscriptionItem()::DURATION],
                    'SubscriptionType' => $subscriptionType,
                    'SubscriptionPrice' => SubscriptionPrice::getPrice($subscriptionType, $cart->getSubscriptionItem()::DURATION),
                ])];
            })
            ->filter(function (array $row) {
                $cartsBoughtAfterDowngrade = $this->connection->fetchOne("select count(CartID) from Cart where UserID = :userId and PayDate > :date", ["userId" => $row['UserID'], "date" => $row['DowngradeDate']->format("Y-m-d H:i:s")]);

                if ($cartsBoughtAfterDowngrade > 1) {
                    $this->logger->info("user already bought {$cartsBoughtAfterDowngrade} carts after downgrade", ["UserID" => $row['UserID']]);

                    return false;
                }

                if ($cartsBoughtAfterDowngrade == 0) {
                    return true;
                }

                $q = $this->entityManager->createQuery("select c from AwardWalletMainBundle:Cart c where c.user = :user and c.paydate > :date");
                $carts = $q->execute(["user" => $row['UserID'], "date" => $row['DowngradeDate']]);
                /** @var Cart $cart */
                $cart = null;

                foreach ($carts as $c) {
                    /** @var Cart $c */
                    if ($c->getSubscriptionItem()) {
                        $cart = $c;

                        break;
                    }
                }

                if ($cart === null) {
                    return true;
                }

                if ($cart->getPlusItem() && $cart->getPlusItem() instanceof Supporters3MonthsUpgrade) {
                    return true;
                }

                $this->logger->info("user already bought subscription cart {$cart->getCartid()} after downgrade", ["UserID" => $row['UserID']]);

                return false;
            })
            ->filter(function (array $row) {
                if (
                    count([...$this->elastic->query(
                        "RequestID: {$row['RequestID']} AND message: \"removed user subscription\" AND context.UserID: {$row['UserID']}",
                        $this->startDate,
                        $this->endDate,
                        1
                    )]) === 0
                ) {
                    $this->logger->info("user subscription was not removed, RequestID: {$row['RequestID']}", ["UserID" => $row['UserID']]);
                }

                if (count([...$this->elastic->query(
                    "RequestID: {$row['RequestID']} AND message: \"user has active subscription Stripe, give it a chance to fire\" AND context.UserID: {$row['UserID']}",
                    $this->startDate,
                    $this->endDate,
                    1
                )]) > 0
                ) {
                    return true;
                }

                if (count([...$this->elastic->query(
                    "RequestID: {$row['RequestID']} AND message: \"send notifications about 'expires soon'\"",
                    $this->startDate,
                    $this->endDate,
                    1
                )]) > 0
                ) {
                    // it is aw:expire-soon command
                    return true;
                }

                if (count([...$this->elastic->query(
                    "RequestID: {$row['RequestID']} AND context.route: aw_oldsite_bootfirewall",
                    $this->startDate,
                    $this->endDate,
                    1
                )]) > 0
                ) {
                    $this->logger->info("it was refund through manager interface, RequestID: {$row['RequestID']}", ["UserID" => $row['UserID']]);

                    return false;
                }

                $this->logger->warning("user subscription was not removed for unknown reason, review, RequestID: {$row['RequestID']}", ["UserID" => $row['UserID']]);

                return false;
            })
            ->flatMap(function (array $row) {
                $methods = $this->stripe->customers->allPaymentMethods($row['DefaultTab']);
                /** @var PaymentMethod $paymentMethod */
                $paymentMethod = $methods->first();

                if ($paymentMethod === null) {
                    $this->logger->info("no payment method", ["UserID" => $row['UserID']]);

                    return [];
                }

                return [array_merge($row, ['PaymentMethod' => $paymentMethod->id])];
            })
            ->take($this->input->getOption('recover-limit') ?? 1000000)
            ->flatMap(function (array $row) {
                $expiration = $this->expirationCalculator->getAccountExpiration($row['UserID']);
                $row['PlusExpirationDate'] = date("Y-m-d", $expiration['date']);
                $this->logger->info("recovering user: " . json_encode($row) . (!$this->input->getOption('apply') ? ', dry-run' : ''), ["UserID" => $row['UserID']]);

                if ($this->input->getOption('apply')) {
                    $this->connection->update(
                        "Usr",
                        [
                            'PayPalRecurringProfileID' => $row['PaymentMethod'],
                            'Subscription' => Usr::SUBSCRIPTION_STRIPE,
                            'SubscriptionType' => $row['SubscriptionType'],
                            'SubscriptionPrice' => $row['SubscriptionPrice'],
                            'SubscriptionPeriod' => $row['SubscriptionPeriod'],
                            'LastSubscriptionCartItemID' => $row['LastSubscriptionCartItemID'],
                            'PlusExpirationDate' => $row['PlusExpirationDate'],
                            'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
                        ],
                        [
                            'UserID' => $row['UserID'],
                        ]
                    );

                    if ($this->input->getOption('bill')) {
                        $this->entityManager->clear();
                        $this->billCardsCommand->run(new ArrayInput(['--userId' => $row['UserID']]), $this->output);
                    }
                } else {
                    $this->logger->info("dry run");
                }

                return [$row];
            })
            ->count()
        ;
        $this->logger->info("done, recovered: {$recovered}");
    }

    private function loadMissedPayments(): array
    {
        $this->logger->info("loading missed payments");
        $query = 'message: "Downgrading user with recurring payment and no failures"';

        if ($this->input->getOption('userId')) {
            $this->logger->info("limited to UserID: " . $this->input->getOption('userId'));
            $query .= ' AND context.UserID: ' . $this->input->getOption('userId');
        }

        $result = [...$this->elastic->query(
            $query,
            $this->startDate,
            $this->endDate,
            500
        )];

        $this->logger->info("got " . count($result) . " records");

        return $result;
    }
}
