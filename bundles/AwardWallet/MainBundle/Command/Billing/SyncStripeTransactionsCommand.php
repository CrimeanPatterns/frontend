<?php

namespace AwardWallet\MainBundle\Command\Billing;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Globals\Cart\Manager;
use AwardWallet\MainBundle\Globals\LoggerContext\ContextAwareLoggerWrapper;
use AwardWallet\MainBundle\Service\Billing\StripeCartServices;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Stripe\Charge;
use Stripe\Dispute;
use Stripe\StripeClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class SyncStripeTransactionsCommand extends Command
{
    protected static $defaultName = 'aw:billing:sync-stripe-transactions';
    private LoggerInterface $logger;
    private StripeClient $stripeClient;
    private EntityManagerInterface $entityManager;
    private Manager $cartManager;
    private InputInterface $input;
    private StripeCartServices $stripeCartServices;

    public function __construct(
        LoggerInterface $paymentLogger,
        StripeClient $stripeClient,
        EntityManagerInterface $entityManager,
        Manager $cartManager,
        StripeCartServices $stripeCartServices
    ) {
        parent::__construct();

        $this->logger = new ContextAwareLoggerWrapper($paymentLogger);
        $this->logger->pushContext(['command' => self::$defaultName]);
        $this->stripeClient = $stripeClient;
        $this->entityManager = $entityManager;
        $this->cartManager = $cartManager;
        $this->stripeCartServices = $stripeCartServices;
    }

    public function configure()
    {
        $this
            ->addOption('apply', null, InputOption::VALUE_NONE, 'apply changes')
            ->addOption('apply-refunds', null, InputOption::VALUE_NONE)
            ->addOption('lastDays', null, InputOption::VALUE_REQUIRED, 'process only N last days')
            ->addOption('chargeId', null, InputOption::VALUE_REQUIRED)
            ->addOption('afterChargeId', null, InputOption::VALUE_REQUIRED)
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->logger->info("syncing stripe transactions");
        $startFrom = new \DateTime("2000-01-01");
        $this->input = $input;

        if ($input->getOption('lastDays')) {
            $startFrom = new \DateTime("-" . $input->getOption('lastDays') . " days");
        }

        $processed = it($this->loadTransactions($startFrom, $input->getOption('chargeId'), $input->getOption('afterChargeId')))
            ->filter(fn (Charge $charge) => !in_array($charge->status, [Charge::STATUS_FAILED, Charge::STATUS_PENDING]))
            ->onEach(function (Charge $charge) {
                if ($charge->status !== Charge::STATUS_SUCCEEDED) {
                    throw new \Exception("Unknown charge status: $charge->id, $charge->status");
                }

                $cart = $this->stripeCartServices->findCart($charge->payment_intent, $charge->description);

                $isRefund = $charge->amount === $charge->amount_refunded;

                if ($charge->disputed && !$isRefund) {
                    $dispute = $this->stripeClient->disputes->retrieve($charge->dispute);
                    $isRefund = $dispute->status === Dispute::STATUS_LOST;
                }

                if ($cart === null && $isRefund) {
                    $this->logger->debug("this is refund: {$charge->payment_intent}, customer, {$charge->customer}, {$charge->description}");

                    return;
                }

                if ($cart && $isRefund) {
                    $this->stripeCartServices->deleteRefundedCart($charge, $cart, $this->input->getOption('apply') || $this->input->getOption('apply-refunds'));

                    return;
                }

                if ($cart && $cart->getPaydate() !== null) {
                    return;
                }

                $this->recoverCart($charge);
            })
            ->count()
        ;

        $this->logger->info("synced stripe transactions: $processed");

        return 0;
    }

    private function loadTransactions(\DateTime $startFrom, ?string $chargeId, ?string $afterChargeId): iterable
    {
        if ($chargeId) {
            $this->logger->info("loading only charge $chargeId");

            yield $this->stripeClient->charges->retrieve($chargeId);

            return;
        }

        $lastCharge = $afterChargeId;

        do {
            $options = ['limit' => 100, 'created' => ['gte' => $startFrom->getTimestamp()]];

            if ($lastCharge) {
                $options['starting_after'] = $lastCharge;
            }

            /** @var Charge[] $charges */
            $charges = $this->stripeClient->charges->all($options);

            $reported = false;

            foreach ($charges as $charge) {
                if (!$reported) {
                    $this->logger->info("got " . count($charges) . " transactions" . ($lastCharge ? ", after: $lastCharge" : "") . ", date: " . date("Y-m-d", $charge->created));
                    $reported = true;
                }

                $lastCharge = $charge->id;

                yield $charge;
            }
        } while (count($charges) > 0);
    }

    private function recoverCart(Charge $charge): void
    {
        if (in_array($charge->id, [
            'ch_3OQrYTJuHLZHC74s01Sddedh', // user deleted, 2023
            'ch_3QWgQhJuHLZHC74s1l2cBJ6K', // deborah and kristin glinko, wrong payment, gave 5 years to deborah, ignore kristin's payment, full thread in mail, EKanunnikova
        ])) {
            $this->logger->debug("charge ignored");

            return;
        }

        $this->logger->warning("cart not found for charge {$charge->id}, intent {$charge->payment_intent}, customer {$charge->customer}, {$charge->description}");

        if (!preg_match('/^Order #(\d+)$/ims', $charge->description, $matches)) {
            throw new \Exception("could not extract cart id from charge description: {$charge->description}");
        }

        $cartId = $matches[1];
        $this->logger->info("trying to recover cart {$cartId}");
        $cart = $this->entityManager->find(Cart::class, $cartId);

        if ($cart === null) {
            throw new \Exception("Cart $cartId not found");
        }

        $this->logger->pushContext(['UserID' => $cart->getUser()->getId()]);
        $this->logger->info("cart found");

        try {
            if ($cart->getPaydate() !== null && substr($cart->getBillingtransactionid(), 0, 5) === 'seti_') {
                $this->logger->warning("replacing setup intent id {$cart->getBillingtransactionid()} with payment intent id {$charge->payment_intent}");

                if ($this->input->getOption('apply')) {
                    $cart->setBillingtransactionid($charge->payment_intent);
                    $this->entityManager->flush();
                } else {
                    $this->logger->info("dry run");
                }

                return;
            }

            if ($cart->getPaydate() !== null) {
                throw new \Exception("Cart already paid");
            }

            if (($cart->getTotalPrice() * 100) != $charge->amount) {
                throw new \Exception("Unexpected cart total: \${$cart->getTotalPrice()}, expected {$charge->amount} cents");
            }

            $this->cartManager->setUser($cart->getUser());

            if ($this->input->getOption('apply')) {
                $cart->setBillingtransactionid($charge->payment_intent);
                $this->entityManager->flush();
                $this->cartManager->markAsPayed($cart, null, new \DateTime('@' . $charge->created), true);
            } else {
                $this->logger->info("dry run");
            }
        } finally {
            $this->logger->popContext('UserID');
        }
    }
}
