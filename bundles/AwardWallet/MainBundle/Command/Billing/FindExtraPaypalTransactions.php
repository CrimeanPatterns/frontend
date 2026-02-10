<?php

namespace AwardWallet\MainBundle\Command\Billing;

use AwardWallet\MainBundle\Service\Billing\PaypalTransactionsSource;
use Doctrine\DBAL\Connection;
use PayPal\Api\Payment;
use PayPal\Api\RelatedResources;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FindExtraPaypalTransactions extends Command
{
    public static $defaultName = 'aw:find-extra-paypal-transactions';
    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var PaypalTransactionsSource
     */
    private $paypalTransactionsSource;

    public function __construct(PaypalTransactionsSource $paypalTransactionsSource, Connection $connection)
    {
        parent::__construct();
        $this->connection = $connection;
        $this->paypalTransactionsSource = $paypalTransactionsSource;
    }

    public function configure()
    {
        $this
            ->addArgument('start-date', InputArgument::REQUIRED)
            ->addArgument('end-date', InputArgument::REQUIRED)
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $missing = [];

        foreach ($this->paypalTransactionsSource->getTransactions(strtotime($input->getArgument('start-date')), strtotime($input->getArgument('end-date'))) as $tx) {
            $output->writeln("checking transaction: " . $tx->getId());

            if ($this->isMissingTransaction($tx)) {
                $missing[] = $tx;
                $output->writeln("missing: {$tx->getId()}");
            }
        }
        $output->writeln("done, missing transactions: " . count($missing));

        return 0;
    }

    private function isMissingTransaction(Payment $tx): bool
    {
        if ($tx->transactions[0]->amount->total === '0.01') {
            // cc check
            return false;
        }

        if (count(array_filter($tx->transactions[0]->getRelatedResources(), function (RelatedResources $resources) {
            return !empty($resources->refund);
        })) > 0) {
            // refunded
            return false;
        }

        return $this->connection->executeQuery("select CartID from Cart where BillingTransactionID = ?", [$tx->getId()])->fetchColumn() === false;
    }
}
