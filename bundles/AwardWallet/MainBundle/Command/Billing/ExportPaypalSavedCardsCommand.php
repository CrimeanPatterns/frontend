<?php

namespace AwardWallet\MainBundle\Command\Billing;

use AwardWallet\Common\Memcached\Item;
use AwardWallet\Common\Memcached\Util;
use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\Billing\PaypalRestApi;
use AwardWallet\MainBundle\Service\Billing\PaypalTransactionsSource;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PayPal\Api\Payment;
use PayPal\Exception\PayPalConnectionException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExportPaypalSavedCardsCommand extends Command
{
    protected static $defaultName = 'aw:export-paypal-saved-cards';

    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var LoggerInterface
     */
    private $logger;
    private PaypalTransactionsSource $paypalTransactionsSource;
    private InputInterface $input;
    private EntityManagerInterface $em;
    private PaypalRestApi $paypalRestApi;
    private Util $memcachedUtil;

    public function __construct(
        Connection $connection,
        LoggerInterface $logger,
        PaypalTransactionsSource $paypalTransactionsSource,
        EntityManagerInterface $em,
        PaypalRestApi $paypalRestApi,
        Util $memcachedUtil
    ) {
        parent::__construct();

        $this->connection = $connection;
        $this->logger = $logger;
        $this->paypalTransactionsSource = $paypalTransactionsSource;
        $this->em = $em;
        $this->paypalRestApi = $paypalRestApi;
        $this->memcachedUtil = $memcachedUtil;
    }

    public function configure()
    {
        $this
            ->addArgument('export-file', InputArgument::REQUIRED)
            ->addArgument('log-file', InputArgument::REQUIRED)
            ->addOption('limit', null, InputOption::VALUE_REQUIRED)
            ->addOption('show-all', null, InputOption::VALUE_NONE)
            ->addOption('transaction-history-depth', null, InputOption::VALUE_REQUIRED, '', "-13 month")
            ->addOption('userId', null, InputOption::VALUE_REQUIRED)
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->logger->info("searching users with credit card payment method");
        $users = $this->connection->executeQuery("
            select 
                u.UserID,
                u.AccountLevel,
                u.FirstName,
                u.LastName,
                u.PayPalRecurringProfileID 
            from 
                Usr u
            where 
                u.AccountLevel <> " . ACCOUNT_LEVEL_BUSINESS . "
                and u.Subscription in (" . Usr::SUBSCRIPTION_SAVED_CARD . ", " . Usr::SUBSCRIPTION_PAYPAL . ")"
                . ($input->getOption('userId') ? " and u.UserID = " . (int) $input->getOption('userId') : "")
            . ($input->getOption('limit') ? " limit " . $input->getOption('limit') : "")
        )->fetchAllAssociative();
        $this->logger->info("loaded " . count($users) . " with card/paypal subscription");
        // $this->loadTransactions();
        $errors = [];
        $exported = 0;
        $exportFile = fopen($input->getArgument('export-file'), "w");
        $logFile = fopen($input->getArgument('log-file'), "w");

        foreach ($users as $user) {
            if ($input->getOption('show-all')) {
                $this->logger->info("User {$user['UserID']}, card {$user['PayPalRecurringProfileID']}, tx: {$lastTx}");
            }

            $lastTx = null;

            foreach ($this->getLastSubscriptionCarts($user['UserID']) as $cart) {
                if (!empty($cart->getBillingtransactionid()) && strpos($cart->getBillingtransactionid(), 'I-') !== 0) {
                    $lastTx = $cart->getBillingtransactionid();
                }

                if ($lastTx === null) {
                    $lastTx = $this->searchCartTransaction($user['UserID'], $cart->getPaydate()->getTimestamp(), $cart->getBillfirstname() ?? $user['FirstName'], $cart->getBilllastname() ?? $user['LastName']);
                }

                if ($lastTx !== null) {
                    break;
                }
            }

            if (strpos($lastTx, "PAY-") === 0) {
                $lastTx = $this->convertTxNumber($lastTx);
            }

            if (empty($lastTx)) {
                $this->logger->notice("can't find tx: " . json_encode($user));
            }

            if (!empty($lastTx)) {
                if (!fputs($exportFile, $lastTx . "\n")) {
                    throw new \Exception("write error");
                }

                $user['CartID'] = $cart->getCartid();
                $user['TransactionID'] = $lastTx;

                if (!fputs($logFile, json_encode($user) . "\n")) {
                    throw new \Exception("write error");
                }

                $exported++;
            } else {
                $errors[$user['UserID']] = $user;
            }

            $this->em->clear();
        }

        foreach ($errors as $error) {
            $this->logger->notice(json_encode($error));
        }

        fclose($exportFile);
        fclose($logFile);

        $this->logger->info("done, exported: $exported, errors: " . count($errors));

        return 0;
    }

    /**
     * @return Payment[]
     */
    private function loadTransactions(int $startDate, int $endDate): array
    {
        $this->logger->debug("loading paypal transactions from " . date("Y-m-d H:i:s", $startDate) . " to " . date("Y-m-d H:i:s", $endDate));
        $result = [];
        $iterable = $this->paypalTransactionsSource->getTransactions(
            $startDate,
            $endDate
        );

        foreach ($iterable as $tx) {
            $result[] = $tx;
        }

        $this->logger->info("loaded paypal transactions: " . count($result));

        return $result;
    }

    private function searchCartTransaction(int $userId, int $payDate, string $firstName, string $lastName): ?string
    {
        return $this->memcachedUtil->getThrough("cart_tx_" . sha1($userId . $payDate . $firstName . $lastName), function () use ($userId, $payDate, $firstName, $lastName) {
            $result = null;

            $this->logger->info("searching tx number for user $userId");

            foreach ($this->loadTransactions($payDate - 180, $payDate + 180) as $tx) {
                if (
                    strcasecmp($tx->getPayer()->getPayerInfo()->getLastName(), $lastName) === 0
                    && strcasecmp($tx->getPayer()->getPayerInfo()->getFirstName(), $firstName) === 0
                    && abs(strtotime($tx->getCreateTime()) - $payDate) < 60
                ) {
                    if ($result !== null) {
                        $this->logger->notice("ambiguous transactions for user $userId, " . date("Y-m-d H:i:s", $payDate));

                        return new Item(null, 86400 * 7);
                    }

                    $result = $tx->getId();
                    $this->logger->info("found tx for $userId: $result");
                }
            }

            return new Item($result, 86400 * 7);
        });
    }

    /**
     * @return Cart[]
     */
    private function getLastSubscriptionCarts(int $userId): array
    {
        $q = $this->em->createQuery("
            select 
                c 
            from 
                AwardWallet\MainBundle\Entity\Cart c 
            where 
                c.user = :userId 
                and c.paymenttype in (" . Cart::PAYMENTTYPE_CREDITCARD . ", " . Cart::PAYMENTTYPE_PAYPAL . ")
                and c.paydate is not null
            order by
                c.paydate desc
        ");

        $q->execute(["userId" => $userId]);

        $result = [];

        foreach ($q->getResult() as $cart) {
            /** @var Cart $cart */
            if ($cart->isAwPlusSubscription() || $cart->isAwPlusRecurringPayment() || $cart->isAwPlus()) {
                $result[] = $cart;
            }
        }

        return $result;
    }

    private function convertTxNumber(string $tx): ?string
    {
        return $this->memcachedUtil->getThrough('tx_converted_' . $tx, function () use ($tx) {
            try {
                $payment = $this->paypalRestApi->getPaymentInfo($tx);
            } catch (PayPalConnectionException $exception) {
                if ($exception->getCode() == 404) {
                    $this->logger->notice("404 while converting $tx");

                    return new Item(null, 3600);
                }
            }
            $resources = $payment->getTransactions()[0]->getRelatedResources()[0];

            if ($resources->getSale()) {
                return new Item($resources->getSale()->getId(), 86400 * 7);
            }

            if ($resources->getAuthorization()) {
                return new Item($resources->getAuthorization()->getId(), 86400 * 7);
            }

            throw new \Exception("can't find tx number: {$tx}");
        });
    }
}
