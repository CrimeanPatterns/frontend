<?php

namespace AwardWallet\MainBundle\Command\Fix;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\Billing\PaypalRestApi;
use AwardWallet\MainBundle\Service\Billing\PlusManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PayPal\Api\Agreement;
use PayPal\Api\AgreementTransaction;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FixPaypalDoublesCommand extends Command
{
    protected static $defaultName = 'aw:fix-paypal-doubles';
    private EntityManagerInterface $entityManager;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var PaypalRestApi
     */
    private $paypalApi;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var array
     */
    private $warnings = [];
    private PlusManager $plusManager;

    public function __construct(
        EntityManagerInterface $entityManager,
        PaypalRestApi $paypalApi,
        PlusManager $plusManager
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->connection = $entityManager->getConnection();
        $this->paypalApi = $paypalApi;
        $this->plusManager = $plusManager;
    }

    protected function configure()
    {
        $this
            ->setDescription('fix paypal fake profiles')
            ->addOption('force', null, InputOption::VALUE_NONE, 'apply changes')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        $userRepo = $this->entityManager->getRepository(Usr::class);

        $carts = $this->connection->executeQuery("
            select 
                c.UserID, 
                c.CartID, c2.CartID as c2CartID, 
                c.PayDate, c2.PayDate as c2PayDate,
                c2.BillingTransactionID,
                u.PaypalRecurringProfileID,
                u.AccountLevel, 
                u.PlusExpirationDate
            from Cart c 
            join CartItem ci on c.CartID = ci.CartID and ci.TypeID = 16
            join Cart c2 on c.UserID = c2.UserID
            join CartItem ci2 on c2.CartID = ci2.CartID and ci2.TypeID = 16
            join Usr u on c.UserID = u.UserID
            
            where 
            
            c.PaymentType = 5 and c.BillingTransactionID is null 
            and c.PayDate is not null
            
            and c2.PayDate is not null 
            and c2.PaymentType = 5 and c2.BillingTransactionID is not null 
            
            and c2.PayDate > c.PayDate and DateDiff(c2.PayDate, c.PayDate) < 2
            
            order by c.PayDate desc 
            
            limit 1000        
        ")->fetchAll(\PDO::FETCH_ASSOC);
        $output->writeln("fixing paypal doubles profiles, " . count($carts) . " carts found");

        foreach ($carts as $cart) {
            $this->logger->info("processing", $cart);

            if (!empty($cart['PaypalRecurringProfileID'])) {
                $apiContext = $this->paypalApi->getApiContext();
                $agreement = Agreement::get($cart['PaypalRecurringProfileID'], $apiContext);
                $transactions = Agreement::searchTransactions($agreement->getId(), ['start_date' => date('Y-m-d', strtotime('-15 years')), 'end_date' => date('Y-m-d', strtotime('+5 days'))], $apiContext);
                $completed = array_filter($transactions->agreement_transaction_list, function (AgreementTransaction $tx) {
                    return $tx->status == "Completed";
                });

                if (count($completed) != 1) {
                    $this->addWarning($cart, "unexpected transactions count: " . count($completed));

                    continue;
                }
            }
            $this->logger->info("update Cart set BillingTransactionID = :txId 
            where CartID = :cartId", ["cartId" => $cart['CartID'], 'txId' => $cart['BillingTransactionID']]);
            $this->logger->info("delete from Cart where CartID = :cartId", ["cartId" => $cart['c2CartID']]);

            if ($input->getOption('force')) {
                $this->logger->info("correcting");
                $this->connection->executeUpdate("update Cart set BillingTransactionID = :txId 
                where CartID = :cartId", ["cartId" => $cart['CartID'], 'txId' => $cart['BillingTransactionID']]);
                $this->connection->executeUpdate("delete from Cart where CartID = :cartId", ["cartId" => $cart['c2CartID']]);
            } else {
                $this->logger->info("dry run");
            }

            /** @var Usr $user */
            $user = $userRepo->find($cart['UserID']);
            $expiration = $userRepo->getAccountExpiration($user->getUserid());
            $this->plusManager->correctExpirationDate($user, $expiration['date'], 'expiration recalculated');
            $this->logger->info("new expiration", ["date" => date("Y-m-d", $expiration['date'])]);
        }

        foreach ($this->warnings as $warning) {
            $output->writeln($warning);
        }
        $output->writeln("done");

        return 0;
    }

    private function addWarning(array $cart, $message)
    {
        $this->warnings[] = "cart {$cart['CartID']}: " . $message;
        $this->output->writeln("WARNING: " . $message);
    }
}
