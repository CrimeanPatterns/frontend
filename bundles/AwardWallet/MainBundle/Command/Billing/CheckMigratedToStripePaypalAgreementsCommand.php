<?php

namespace AwardWallet\MainBundle\Command\Billing;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\Billing\PaypalIpnProcessor;
use AwardWallet\MainBundle\Service\Billing\PaypalRestApi;
use AwardWallet\MainBundle\Service\Paypal\AgreementHack;
use Doctrine\DBAL\Connection;
use PayPal\Api\Agreement;
use PayPal\Api\AgreementStateDescriptor;
use PayPal\Api\PaymentDefinition;
use PayPal\Rest\ApiContext;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CheckMigratedToStripePaypalAgreementsCommand extends Command
{
    protected static $defaultName = 'aw:billing:check-migrated-to-stripe-paypal-agreements';
    private Connection $connection;
    private LoggerInterface $logger;
    private PaypalRestApi $paypalRestApi;
    private InputInterface $input;
    private PaypalIpnProcessor $paypalIpnProcessor;
    private UsrRepository $usrRepository;

    public function __construct(
        Connection $connection,
        LoggerInterface $logger,
        PaypalRestApi $paypalRestApi,
        PaypalIpnProcessor $paypalIpnProcessor,
        UsrRepository $usrRepository
    ) {
        parent::__construct();
        $this->connection = $connection;
        $this->logger = $logger;
        $this->paypalRestApi = $paypalRestApi;
        $this->paypalIpnProcessor = $paypalIpnProcessor;
        $this->usrRepository = $usrRepository;
    }

    public function configure()
    {
        $this
            ->addOption('paypal-jsonl', null, InputOption::VALUE_REQUIRED, 'tx.jsonl file of migrated to stripe users')
            ->addOption('switched-from-paypal', null, InputOption::VALUE_NONE, 'check users who switched from paypal to stripe')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED)
            ->addOption('userId', null, InputOption::VALUE_REQUIRED)
            ->addOption('apply', null, InputOption::VALUE_NONE)
            ->addOption('restore-paypal', null, InputOption::VALUE_NONE)
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $users = [];

        if ($input->getOption('paypal-jsonl')) {
            $users = $this->loadPaypalJsonl($input->getOption('paypal-jsonl'));
            $users = array_filter($users, fn (array $user) => isset($user['PayPalRecurringProfileID']) && substr($user['PayPalRecurringProfileID'], 0, 2) === 'I-');
            $this->logger->info("agreements to check: " . count($users));
            $activePayPalUsers = $this->connection->fetchFirstColumn("select UserID from Usr where Subscription = " . Usr::SUBSCRIPTION_PAYPAL);
            $users = array_filter($users, fn (array $user) => !in_array($user['UserID'], $activePayPalUsers));
            $this->logger->info("agreements to check excluding active paypal: " . count($users));
            $users = array_map(fn ($user) => $user['PayPalRecurringProfileID'], $users);
        }

        if ($input->getOption('switched-from-paypal')) {
            $users = $this->loadSwitchedFromPaypal();
        }

        if ($input->getOption('userId')) {
            $users = array_filter($users, fn (int $userId) => $userId == $input->getOption('userId'), ARRAY_FILTER_USE_KEY);
        }

        if ($input->getOption('limit')) {
            $users = array_slice($users, 0, $input->getOption('limit'), true);
        }

        foreach ($users as $userId => $agreementId) {
            $this->checkAgreement($userId, $agreementId);
        }

        return 0;
    }

    private function loadPaypalJsonl(string $file): array
    {
        $result = [];
        $f = fopen($file, "rb");

        while ($s = fgets($f)) {
            $s = trim($s);

            if ($s === '') {
                continue;
            }

            $row = json_decode($s, true);
            $result[(int) $row["UserID"]] = $row;
        }
        fclose($f);
        $this->logger->info("loaded " . count($result) . " users from paypal jsonl");

        return $result;
    }

    private function checkAgreement(int $userId, string $profileId)
    {
        $apiContext = $this->paypalRestApi->getApiContext();
        $agreement = AgreementHack::get($profileId, $apiContext);

        if ($agreement->state === 'Cancelled' || $agreement->state === 'Suspended') {
            //            $this->logger->info("{$userId}: agreement {$profileId} is cancelled");

            return;
        }

        if ($agreement->state !== 'Active') {
            throw new \Exception("{$userId}: Unexpected agreement {$profileId} state: {$agreement->state}");
        }

        $user = $this->connection->fetchAssociative("select Email, Subscription, DefaultTab, PayPalRecurringProfileID from Usr where UserID = ?", [$userId]);

        if (
            $user
            && $user['Subscription'] === null
            && $user['DefaultTab'] === 'All'
            && $user['PayPalRecurringProfileID'] === null
            && $this->input->getOption('restore-paypal')
        ) {
            if ($this->checkMissedPaypalTransactions($userId, $agreement, $apiContext)) {
                $this->logger->warning("there is missed transactions");
            }

            $this->restorePaypalSubscription($userId, $agreement->getId());
        }

        if ($user === false || $user['Subscription'] != Usr::SUBSCRIPTION_STRIPE) {
            $this->logger->warning("{$userId}: agreement {$profileId} subscription: " . json_encode($user));
            $this->cancelAgreement($userId, $agreement, $apiContext);

            return;
        }

        //        $this->checkTransactions($userId, $agreement, $apiContext);
        //        $this->cancelAgreement($userId, $agreement, $apiContext);
    }

    private function restorePaypalSubscription(int $userId, string $profileId): void
    {
        $this->logger->warning("{$userId}: restoring paypal as payment method");

        if ($this->input->getOption('apply')) {
            $this->connection->executeStatement(
                "update Usr set Subscription = :sub, PayPalRecurringProfileID = :profile where UserID = :userId limit 1",
                [
                    "sub" => Usr::SUBSCRIPTION_PAYPAL,
                    "profile" => $profileId,
                    "userId" => $userId,
                ]
            );
        }

        $this->logger->warning("{$userId}: restored");
    }

    private function checkMissedPaypalTransactions(int $userId, Agreement $agreement, ApiContext $apiContext): bool
    {
        $transactions = Agreement::searchTransactions($agreement->getId(), ['start_date' => date('Y-m-d', strtotime('2023-04-11')), 'end_date' => date('Y-m-d', strtotime('+5 days'))], $apiContext);

        if (count($transactions->agreement_transaction_list) === 0) {
            $this->logger->info("{$userId}: no transactions on {$agreement->getId()}");

            return false;
        }

        $result = false;

        foreach ($transactions->agreement_transaction_list as $transaction) {
            if ($transaction->getStatus() !== 'Completed') {
                $this->logger->warning("{$userId}: transaction {$transaction->getTransactionId()} on {$agreement->getId()} has unexpected status: {$transaction->getStatus()}");

                continue;
            }

            $cartId = $this->connection->fetchOne("select CartID from Cart where PayDate is not null and BillingTransactionID = :txId", ["txId" => $transaction->getTransactionId()]);

            if ($cartId === false) {
                $this->logger->warning("{$userId}: transaction {$transaction->getTransactionId()} on {$agreement->getId()} is not found in Cart");

                if ($this->input->getOption('apply')) {
                    $user = $this->usrRepository->find($userId);
                    $this->paypalIpnProcessor->processTransaction(
                        $user,
                        $transaction->getTransactionId(),
                        $agreement->getId(),
                        $this->convertPaymentDefinitionToCycle($agreement->getPlan()->getPaymentDefinitions()[0]),
                        $transaction->getAmount()->getValue()
                    );
                }

                return false;
            }

            $result = true;
        }

        return $result;
    }

    private function checkTransactions(int $userId, Agreement $agreement, ApiContext $apiContext): void
    {
        $transactions = Agreement::searchTransactions($agreement->getId(), ['start_date' => date('Y-m-d', strtotime('2023-04-11')), 'end_date' => date('Y-m-d', strtotime('+5 days'))], $apiContext);

        if (count($transactions->agreement_transaction_list) === 0) {
            $this->logger->info("{$userId}: no transactions on {$agreement->getId()}");

            return;
        }

        $lastPayPalTx = $transactions->agreement_transaction_list[0];

        if ($lastPayPalTx->getStatus() !== 'Completed') {
            $this->logger->info("{$userId}: last transactions on {$agreement->getId()} is {$lastPayPalTx->getStatus()}");

            return;
        }

        $txDate = strtotime($lastPayPalTx->getTimeStamp());

        if ($txDate < strtotime('2023-04-11')) {
            throw new \Exception("{$userId}: datetime conversion error: " . $lastPayPalTx->getTimeStamp());
        }

        $stripeCartId = $this->connection->fetchOne("select c.CartID from Cart c join CartItem ci on c.CartID = ci.CartID where c.PayDate >= '2023-04-11' and UserID = ? and ci.ScheduledDate is null and ci.Price > 0 and c.PaymentType = " . Cart::PAYMENTTYPE_STRIPE_INTENT, [$userId]);

        if ($stripeCartId === false) {
            $this->logger->info("{$userId}: no stripe payments found for {$agreement->getId()}");

            return;
        }

        $this->logger->warning("{$userId}: possible double payment, cart {$stripeCartId}, agreement {$agreement->getId()}, paypal tx: {$lastPayPalTx->getTransactionId()} on {$lastPayPalTx->getTimeStamp()}");
    }

    private function cancelAgreement(int $userId, Agreement $agreement, ApiContext $apiContext)
    {
        $this->logger->warning("{$userId}: we should cancel agreement {$agreement->getId()}");

        if ($this->input->getOption('apply')) {
            $agreementStateDescriptor = new AgreementStateDescriptor();
            $agreementStateDescriptor->setNote("aw:billing:check-migrated-to-stripe-paypal-agreements");
            $agreement->cancel($agreementStateDescriptor, $apiContext);
            $this->logger->warning("{$userId}: cancelled {$agreement->getId()}");
        }
    }

    private function loadSwitchedFromPaypal(): array
    {
        return $this->connection->executeQuery("select
               pc.UserID,
               pc.BillingTransactionID
            from Cart pc
                     join Cart sc on pc.UserID = sc.UserID
            where pc.BillingTransactionID like 'I-%'
              and pc.PaymentType = 5
              and pc.PayDate >= '2023-03-06'
              and sc.PayDate > pc.PayDate
              and sc.PaymentType = " . Cart::PAYMENTTYPE_STRIPE_INTENT . "
            limit 100")->fetchAllKeyValue();
    }

    private function convertPaymentDefinitionToCycle(PaymentDefinition $def): string
    {
        if ($def->getFrequency() === 'MONTH') {
            return "Every " . $def->getFrequencyInterval() . " " . ucfirst(strtolower($def->getFrequency())) . "s";
        }

        throw new \Exception("unknown payment definition: " . $def->getFrequency());
    }
}
