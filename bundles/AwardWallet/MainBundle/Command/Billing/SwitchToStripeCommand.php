<?php

namespace AwardWallet\MainBundle\Command\Billing;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\Repositories\CartRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\Billing\PaypalRestApi;
use AwardWallet\MainBundle\Service\Billing\PaypalSoapApi;
use AwardWallet\MainBundle\Service\Paypal\AgreementHack;
use Doctrine\DBAL\Connection;
use PayPal\Api\Agreement;
use PayPal\Api\AgreementStateDescriptor;
use PayPal\Api\AgreementTransaction;
use PayPal\PayPalAPI\RefundTransactionReq;
use PayPal\PayPalAPI\RefundTransactionRequestType;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class SwitchToStripeCommand extends Command
{
    protected static $defaultName = 'aw:billing:switch-to-stripe';
    private Connection $connection;
    private LoggerInterface $logger;
    private $paypalJsonl = []; // [userId => ["UserID":"608759","AccountLevel":"2","FirstName":"frederic","LastName":"deluen","PayPalRecurringProfileID":"I-RB4G1UMEVSJJ","CartID":1432899,"TransactionID":"7BP509440A760832V"], ...
    private $stripeCsv = []; // [userId => [old_id,source_old_id,created_customer,source_new_id,card_fingerprint,card_last4,card_exp_month,card_exp_year,card_brand]
    private PaypalRestApi $paypalRestApi;
    private CartRepository $cartRepository;
    private PaypalSoapApi $paypalSoapApi;

    public function __construct(Connection $connection,
        LoggerInterface $logger,
        PaypalRestApi $paypalRestApi,
        CartRepository $cartRepository,
        PaypalSoapApi $paypalSoapApi
    ) {
        parent::__construct();
        $this->connection = $connection;
        $this->logger = $logger;
        $this->paypalRestApi = $paypalRestApi;
        $this->cartRepository = $cartRepository;
        $this->paypalSoapApi = $paypalSoapApi;
    }

    public function configure()
    {
        $this
            ->addArgument('mode', InputArgument::REQUIRED, 'switch|cancel-paypal-profiles')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED)
            ->addOption('paypal-jsonl', null, InputOption::VALUE_REQUIRED, '.jsonl file with paypal export results')
            ->addOption('stripe-csv', null, InputOption::VALUE_REQUIRED, '.csv file with strip import results')
            ->addOption('apply', null, InputOption::VALUE_NONE, 'apply changes, otherwise dry run')
            ->addOption('userId', null, InputOption::VALUE_REQUIRED, 'process only this user')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$input->getOption('paypal-jsonl')) {
            $this->logger->error("paypal-jsonl option required");

            return 1;
        }

        if (!$input->getOption('stripe-csv')) {
            $this->logger->error("stripe-csv option required");

            return 2;
        }

        $this->loadPaypalJsonl($input->getOption('paypal-jsonl'));
        $this->loadStripeCsv($input->getOption('stripe-csv'));
        $this->filterByUser($input->getOption('userId'));
        $this->loadPlusExpirationDates();

        switch ($input->getArgument('mode')) {
            case "switch":
                $this->switch($input->getOption('limit'), $input->getOption('apply'));

                break;

            case "cancel-paypal-profiles":
                $this->cancelPaypalProfiles($input->getOption('limit'), $input->getOption('userId'), $input->getOption('apply'));

                break;

            default:
                throw new \Exception("unknown mode");
        }

        return 0;
    }

    private function loadPaypalJsonl(string $file): void
    {
        $f = fopen($file, "rb");

        while ($s = fgets($f)) {
            $s = trim($s);

            if ($s === '') {
                continue;
            }

            $row = json_decode($s, true);
            $this->paypalJsonl[(int) $row["UserID"]] = $row;
        }
        fclose($f);
        $this->logger->info("loaded " . count($this->paypalJsonl) . " users from paypal jsonl");
    }

    private function loadStripeCsv(string $file): void
    {
        $f = fopen($file, "rb");
        $headers = fgetcsv($f);

        while ($row = fgetcsv($f)) {
            if (count($row) === 1) {
                continue;
            }

            if (count($row) !== count($headers)) {
                throw new \Exception("row " . implode(",", $row) . " mismatch headers: " . implode(", ", $headers));
            }

            $row = array_combine($headers, $row);
            $this->stripeCsv[(int) $row["source_old_id"]] = $row;
        }
        fclose($f);
        $this->logger->info("loaded " . count($this->stripeCsv) . " users from stripe csv");
    }

    private function loadPlusExpirationDates(): void
    {
        $count = it($this->stripeCsv)
            ->chunkWithKeys(100)
            ->map(function (array $rows) {
                $ids = array_keys($rows);

                return it($this->connection->executeQuery(
                    "select UserID, PlusExpirationDate, Subscription, PaypalRecurringProfileID from Usr where UserID in (?)",
                    [$ids],
                    [Connection::PARAM_INT_ARRAY]
                )->fetchAllAssociative())
                    ->map(function ($row) {
                        $this->stripeCsv[$row['UserID']] = array_merge($this->stripeCsv[$row['UserID']], $row);
                        $this->paypalJsonl[$row['UserID']] = array_merge($this->paypalJsonl[$row['UserID']], ["PlusExpirationDate" => $row['PlusExpirationDate']]);
                    })
                    ->count()
                ;
            })
            ->sum()
        ;
        $this->logger->info("loaded $count plus expiration dates");
    }

    private function switch(?int $limit, bool $apply): void
    {
        uasort($this->stripeCsv, function (array $a, array $b) {
            return ($a["PlusExpirationDate"] ?? "2099-01-01") <=> ($b["PlusExpirationDate"] ?? "2099-01-01");
        });
        $this->logger->info("first plus expiration date: " . array_values($this->stripeCsv)[0]["PlusExpirationDate"]);
        $this->stripeCsv = array_filter($this->stripeCsv, function (array $row) { return isset($row['PlusExpirationDate']) && in_array($row['Subscription'], [Usr::SUBSCRIPTION_SAVED_CARD, Usr::SUBSCRIPTION_PAYPAL]); });
        $this->logger->info("filtered users: " . count($this->stripeCsv));

        $it = it($this->stripeCsv);

        if ($limit) {
            $it = $it->slice(0, $limit);
        }

        $switched = $it
            ->mapIndexed(function (array $row, int $userId) use ($apply) {
                $this->logger->info("switching user $userId, PlusExpirationDate: {$row['PlusExpirationDate']}, Subscription: {$row['Subscription']}, PaypalRecurringProfileID: {$row['PaypalRecurringProfileID']}, paymentMethod: {$row["source_new_id"]}, customer: {$row["created_customer"]}");
                $cancelAgreement = $this->isAgreement($row['PaypalRecurringProfileID'] ?? '') && $this->shouldCancelPaypalProfile($userId, $row['PaypalRecurringProfileID']);

                if ($apply) {
                    $this->connection->executeStatement(
                        "update Usr set Subscription = " . Usr::SUBSCRIPTION_STRIPE . ", PaypalRecurringProfileID = :paymentMethod, DefaultTab = :customer 
                    where UserID = :userId limit 1",
                        [
                            "userId" => $userId,
                            "paymentMethod" => $row["source_new_id"],
                            "customer" => $row["created_customer"],
                        ]
                    );
                }

                if ($cancelAgreement && $apply) {
                    $this->cancelAgreement($userId, $row['PaypalRecurringProfileID']);
                }
            })
            ->count()
        ;

        $this->logger->info("switched $switched users");
    }

    private function cancelPaypalProfiles(?int $limit, ?int $userId, bool $apply): void
    {
        $this->logger->info("checking paypal profiles");
        uasort($this->paypalJsonl, function (array $a, array $b) {
            return ($a["PlusExpirationDate"] ?? "2099-01-01") <=> ($b["PlusExpirationDate"] ?? "2099-01-01");
        });
        $this->logger->info("first plus expiration date: " . array_values($this->paypalJsonl)[0]["PlusExpirationDate"]);
        $candidates = array_filter($this->paypalJsonl, function (array $row) {
            return $this->isAgreement($row['PayPalRecurringProfileID']);
        });
        $this->logger->info("we have " . count($candidates) . " users with paypal recurring profiles");
        $subscriptionTypes = $this->loadSubscriptionTypes(array_keys($candidates));
        $this->logger->info("loaded " . count($subscriptionTypes) . " subscription types");

        $transactionsToRefund = [];
        $agreementsToCancel = [];

        it($candidates)
            ->filter(function (array $row) use ($subscriptionTypes) {
                if (!array_key_exists($row["UserID"], $subscriptionTypes)) {
                    $this->logger->info("{$row['UserID']}: no such user, should cancel billing agreement");

                    return true;
                }

                $subscriptionType = $subscriptionTypes[$row["UserID"]];

                if ($subscriptionType != Usr::SUBSCRIPTION_STRIPE) {
                    $this->logger->debug("{$row['UserID']}: not a stripe subscription - " . ($subscriptionType ? Usr::SUBSCRIPTION_NAMES[$subscriptionType] : "no subscription"));

                    return false;
                }

                return true;
            })
            ->slice(0, $limit ?: 1000000)
            ->apply(function (array $row) use (&$transactionsToRefund, &$agreementsToCancel) {
                $tx = $this->getPaypalTransactionToRefund($row['UserID'], $row['PayPalRecurringProfileID']);

                if ($tx !== null) {
                    $transactionsToRefund[$row['UserID']] = $tx;
                }

                if ($this->shouldCancelPaypalProfile($row['UserID'], $row['PayPalRecurringProfileID'])) {
                    $agreementsToCancel[$row['UserID']] = $row['PayPalRecurringProfileID'];
                }
            });

        it($agreementsToCancel)
            ->applyIndexed(function (string $profileId, int $userId) use ($apply) {
                $this->logger->info("{$userId}: agreement to cancel: {$profileId}");

                if ($apply) {
                    $this->cancelAgreement($userId, $profileId);
                }
            });

        it($transactionsToRefund)
            ->applyIndexed(function (string $tx, int $userId) use ($apply) {
                $this->logger->info("{$userId}: transaction to refund: {$tx}");

                if ($apply) {
                    $this->refundPaypalTransaction($userId, $tx);
                }
            });

        $this->logger->info("we have " . count($transactionsToRefund) . " transactions to refund, and " . count($agreementsToCancel) . " agreements to cancel");
    }

    private function loadSubscriptionTypes(array $userIds): array
    {
        return it($userIds)
            ->chunk(100)
            ->map(function (array $userIds) {
                return $this->connection->executeQuery("select UserID, Subscription from Usr where UserID in (?)", [$userIds], [Connection::PARAM_INT_ARRAY])->fetchAllKeyValue();
            })
            ->flatMapIndexed(function (array $rows) {
                return $rows;
            })
            ->toArrayWithKeys()
        ;
    }

    private function filterByUser(?int $userId): void
    {
        if ($userId === null) {
            return;
        }

        $filterFunc = function (int $key) use ($userId) {
            return $key === $userId;
        };

        $this->paypalJsonl = array_filter($this->paypalJsonl, $filterFunc, ARRAY_FILTER_USE_KEY);
        $this->stripeCsv = array_filter($this->stripeCsv, $filterFunc, ARRAY_FILTER_USE_KEY);
        $this->logger->info("we have " . count($this->paypalJsonl) . " paypal users and " . count($this->stripeCsv) . " stripe users after filtering");
    }

    private function shouldCancelPaypalProfile(int $userId, string $profileId): bool
    {
        $agreement = AgreementHack::get($profileId, $this->paypalRestApi->getApiContext());

        if ($agreement->state !== 'Active') {
            $this->logger->info("{$userId}: agreement is not active: {$agreement->state}");

            return false;
        }

        $this->logger->info("{$userId}: we should cancel agreement: {$profileId}");

        return true;
    }

    private function cancelAgreement(int $userId, string $profileId): void
    {
        $agreement = AgreementHack::get($profileId, $this->paypalRestApi->getApiContext());
        $agreementStateDescriptor = new AgreementStateDescriptor();
        $agreementStateDescriptor->setNote("Deleting the agreement by " . basename(self::class));
        $this->logger->info("{$userId}: cancelling agreement: {$agreement->getId()}");
        $agreement->cancel($agreementStateDescriptor, $this->paypalRestApi->getApiContext());
    }

    private function getPaypalTransactionToRefund(int $userId, string $profileId): ?string
    {
        $allTr = Agreement::searchTransactions($profileId, ['start_date' => date('Y-m-d', strtotime('-2 months')), 'end_date' => date('Y-m-d', strtotime('+5 days'))], $this->paypalRestApi->getApiContext());
        $toRefund = array_filter($allTr->agreement_transaction_list, function (AgreementTransaction $tr) use ($userId) {
            return $this->shouldRefundPaypalTransaction($tr, $userId);
        });

        if (count($toRefund) > 1) {
            throw new \Exception("$userId: too much transactions to refund: " . count($toRefund));
        }

        return $toRefund ? $toRefund[0] : null;
    }

    private function shouldRefundPaypalTransaction(AgreementTransaction $tr, int $userId): bool
    {
        if ($tr->getStatus() !== 'Completed') {
            return false;
        }

        if ($tr->getTransactionType() === 'Refund') {
            $this->logger->info("$userId: tx {$tr->getTransactionId()} is already refunded");

            return false;
        }

        $this->logger->info("$userId: tx {$tr->getTransactionId()} on {$tr->getTimeStamp()}");
        $cartId = $this->connection->executeQuery("select CartID from Cart where PayDate is not null and UserID = :userId and BillingTransactionID = :tx", ["userId" => $userId, "tx" => $tr->getTransactionId()])->fetchOne();

        if ($cartId !== false) {
            $this->logger->info("$userId: this tx already processed in cart {$cartId}");

            return false;
        }

        $this->logger->info("$userId: tx not found in orders");
        $cartId = $this->connection->executeQuery(
            "select CartID from Cart 
                where PayDate >= adddate(:payDate, -14) and PayDate <= adddate(:payDate, 14) 
                and UserID = :userId and PaymentType = " . Cart::PAYMENTTYPE_STRIPE_INTENT,
            ["userId" => $userId, "payDate" => $tr->getTimeStamp()]
        )->fetchOne();

        if ($cartId === false && !$this->userExists($userId)) {
            $this->logger->info("$userId: user deleted himself");

            return false;
        }

        if ($cartId === false) {
            $this->logger->warning("$userId: no nearby stripe payment");

            return false;
        }

        return $this->shouldRefundStripeCart($userId, $cartId, $tr->getTransactionId());
    }

    private function shouldRefundStripeCart(int $userId, int $cartId, string $tx): bool
    {
        $this->logger->info("$userId: found nearby stripe payment, cart: {$cartId}");
        /** @var Cart $cart */
        $cart = $this->cartRepository->find($cartId);

        if (!$cart->isAwPlusRecurringPayment() && !$cart->isAwPlus()) {
            $this->logger->warning("$userId: this is not aw plus recurring payment");

            return false;
        }

        $this->logger->info("$userId: should refund cart $cartId, tx: $tx");

        return true;
    }

    private function refundPaypalTransaction(int $userId, string $tx)
    {
        $service = $this->paypalSoapApi->getPaypalService();
        $request = new RefundTransactionReq();
        $request->RefundTransactionRequest = new RefundTransactionRequestType();
        $request->RefundTransactionRequest->TransactionID = $tx;
        $this->logger->info("$userId: refunding $tx");
        $response = $service->RefundTransaction($request);

        if ($response->Ack == "Failure" && $response->Errors[0]->LongMessage == 'This transaction has already been fully refunded') {
            $this->logger->warning("{$userId}: $tx already refunded");

            return;
        }

        if ($response->Ack != "Success") {
            throw new \Exception("failed to refund transaction: " . json_encode($response->Errors));
        }

        $this->logger->info("{$userId}: $tx was refunded");
    }

    private function userExists(int $userId): bool
    {
        return $this->connection->executeQuery("select 1 from Usr where UserID = ?", [$userId])->fetchOne() !== false;
    }

    private function isAgreement(string $profileId): bool
    {
        return substr($profileId, 0, 2) === 'I-';
    }
}
