<?php

namespace AwardWallet\MainBundle\Command\Billing;

use AwardWallet\MainBundle\Entity\Repositories\CartRepository;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\Billing\PaypalIpnProcessor;
use AwardWallet\MainBundle\Service\Billing\PaypalRestApi;
use AwardWallet\MainBundle\Service\Paypal\AgreementHack;
use Doctrine\DBAL\Connection;
use PayPal\Api\Agreement;
use PayPal\Api\PaymentDefinition;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RestorePaypalTransactionsCommand extends Command
{
    protected static $defaultName = 'aw:restore-paypal-transactions';

    private LoggerInterface $logger;
    private PaypalRestApi $paypalRestApi;
    private InputInterface $input;
    private PaypalIpnProcessor $paypalIpnProcessor;
    private UsrRepository $usrRepository;
    private CartRepository $cartRepository;

    public function __construct(
        Connection $connection,
        LoggerInterface $logger,
        PaypalRestApi $paypalRestApi,
        PaypalIpnProcessor $paypalIpnProcessor,
        UsrRepository $usrRepository,
        CartRepository $cartRepository
    ) {
        parent::__construct();
        $this->connection = $connection;
        $this->logger = $logger;
        $this->paypalRestApi = $paypalRestApi;
        $this->paypalIpnProcessor = $paypalIpnProcessor;
        $this->usrRepository = $usrRepository;
        $this->cartRepository = $cartRepository;
    }

    public function configure()
    {
        $this
            ->addArgument('agreement-id', InputArgument::REQUIRED, 'paypal agreement id')
            ->addOption('restore-subscription', null, InputOption::VALUE_NONE)
            ->addOption('userId', null, InputOption::VALUE_REQUIRED)
            ->setDescription('import missed transactions from agreement to Cart')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $apiContext = $this->paypalRestApi->getApiContext();
        $agreement = AgreementHack::get($input->getArgument('agreement-id'), $apiContext);

        if (preg_match('/Order #(\d+)/ims', $agreement->getDescription(), $matches)) {
            $cartId = $matches[1];
        } else {
            $this->logger->warning("could not detect cart: " . $agreement->getDescription());
        }

        if ($input->getOption('userId')) {
            $user = $this->usrRepository->find($input->getOption('userId'));
            $cart = $this->cartRepository->getActiveAwSubscription($user, false);

            if (null === $cart) {
                throw new \Exception("Can't find subscription for user {$user->getId()}");
            }

            $cartId = $cart->getCartid();
        }

        $this->logger->info("cart $cartId");
        $userId = $this->connection->fetchOne("select UserID from Cart where CartID = ?", [$cartId]);

        if ($userId === false) {
            throw new \Exception("Cart $cartId not found");
        }

        /** @var Usr $user */
        $user = $this->usrRepository->find($userId);
        $this->logger->info("user $userId, {$user->getFirstname()} {$user->getLastname()}, {$user->getEmail()}");

        $transactions = Agreement::searchTransactions($agreement->getId(), ['start_date' => date('Y-m-d', strtotime('2023-04-11')), 'end_date' => date('Y-m-d', strtotime('+5 days'))], $apiContext);

        foreach ($transactions->agreement_transaction_list as $transaction) {
            if ($transaction->getStatus() !== 'Completed') {
                continue;
            }

            $this->paypalIpnProcessor->processTransaction(
                $user,
                $transaction->getTransactionId(),
                $agreement->getId(),
                $this->convertPaymentDefinitionToCycle($agreement->getPlan()->getPaymentDefinitions()[0]),
                $transaction->getAmount()->getValue(),
                $cartId
            );
        }

        if ($input->getOption('restore-subscription')) {
            if ($user->getSubscription() !== null) {
                throw new \Exception("user already have subscription:" . $user->getSubscription());
            }

            if ($user->getPaypalrecurringprofileid() !== null) {
                throw new \Exception("user already paypal profile:" . $user->getPaypalrecurringprofileid());
            }

            if ($agreement->getState() !== 'Active') {
                throw new \Exception("invalid agreement state:" . $agreement->getState());
            }

            $this->connection->executeStatement(
                "update Usr set Subscription = :sub, PayPalRecurringProfileID = :profile where UserID = :userId limit 1",
                [
                    "sub" => Usr::SUBSCRIPTION_PAYPAL,
                    "profile" => $input->getArgument('agreement-id'),
                    "userId" => $user->getId(),
                ]
            );

            $this->logger->info("restored user paypal subscription");
        }

        return 0;
    }

    private function convertPaymentDefinitionToCycle(PaymentDefinition $def): string
    {
        if ($def->getFrequency() === 'MONTH') {
            return "every " . $def->getFrequencyInterval() . " " . ucfirst(strtolower($def->getFrequency())) . "s";
        }

        throw new \Exception("unknown payment definition: " . $def->getFrequency());
    }
}
