<?php

namespace AwardWallet\MainBundle\Command\Billing;

use AwardWallet\MainBundle\Entity\CartItem;
use AwardWallet\MainBundle\Entity\CartItem\At201Items;
use AwardWallet\MainBundle\Globals\Cart\AT201SubscriptionInterface;
use AwardWallet\MainBundle\Globals\Cart\SubscriptionPeriod;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Stripe\PaymentMethod;
use Stripe\StripeClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FixAt201Command extends Command
{
    public static $defaultName = 'aw:billing:fix-at-201';
    private Connection $connection;
    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;
    private StripeClient $stripe;

    public function __construct(
        Connection $connection,
        LoggerInterface $paymentLogger,
        EntityManagerInterface $entityManager,
        StripeClient $stripe
    ) {
        parent::__construct();

        $this->connection = $connection;
        $this->logger = $paymentLogger;
        $this->entityManager = $entityManager;
        $this->stripe = $stripe;
    }

    public function configure()
    {
        $this
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'how many users to process')
            ->addOption('userId', null, InputOption::VALUE_REQUIRED)
            ->addOption('apply', null, InputOption::VALUE_NONE)
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger->info("loading users");
        $sql = "
        select 
            u.UserID, 
            u.Email, 
            u.FirstName, 
            u.LastName, 
            Subscription, 
            SubscriptionType, 
            u.DefaultTab as CustomerId, 
            u.PayPalRecurringProfileID as PaymentMethodId,
            At201ExpirationDate,
            not isnull(gul.SiteGroupID) as InGroup 
        from 
            Usr u 
            left join GroupUserLink gul on u.UserID = gul.UserID and gul.SiteGroupID = 75
        where 
            u.UserID in (926243, 220147, 469936, 150605, 927076, 926512, 927049, 926469, 921981, 923763, 921273, 419299, 636246, 492433, 628785, 925547, 847749, 878594, 925631, 921833, 925881, 684499, 660383, 523027, 926307, 924505, 835036, 628510, 918595, 925031, 617131, 926509, 857433, 547545, 555060, 926788, 650396, 916058, 926431, 127809, 925942, 358872, 926596, 880773, 921123, 608439)
        ";

        if ($input->getOption('limit')) {
            $sql .= "limit " . (int) $input->getOption('limit');
        }

        $users = $this->connection->fetchAllAssociative($sql);
        $this->logger->info("loaded " . count($users) . " users");

        foreach ($users as $user) {
            $this->processUser($user);
        }

        $this->logger->info("done");

        return 0;
    }

    private function processUser(array $user): void
    {
        $this->logger->info("processing {$user['UserID']}, {$user['Email']}, {$user['FirstName']} {$user['LastName']}, at 201 exp date: {$user['At201ExpirationDate']}");
        // last at 201 cart item
        $lci = $this->connection->fetchAssociative("
            select
                ci.CartItemID,
                ci.TypeID,
                c.PayDate,
                c.CartID
            from
                CartItem ci
                join Cart c on c.CartID = ci.CartID
            where
                c.UserID = :userId
                and c.PayDate is not null
                and ci.TypeID in (" . implode(", ", At201Items::getTypes()) . ")
            order by
                c.PayDate desc
        ", ["userId" => $user['UserID']]);
        /** @var AT201SubscriptionInterface $ci */
        $ci = $this->entityManager->getRepository(CartItem::class)->find($lci['CartItemID']);
        $period = SubscriptionPeriod::DURATION_TO_DAYS[$ci::DURATION];
        $methods = $this->stripe->customers->allPaymentMethods($user['CustomerId']);
        /** @var PaymentMethod $paymentMethod */
        $paymentMethod = $methods->first();
        $this->logger->info("last at201 cart: {$lci['CartID']}, {$lci['PayDate']}, item : {$lci['CartItemID']}, type: {$lci['TypeID']}, days: {$period}, {$user['CustomerId']}, pm: " . ($paymentMethod ? $paymentMethod->id : "null"));

        if ($paymentMethod === null) {
            $this->logger->info("no payment methods, removing at 201 expiration date");
            $this->connection->executeStatement("update Usr set At201ExpirationDate = null where UserID = ?", [$user['UserID']]);

            return;
        }

        $this->logger->info("should set payment method");

        return;

        $this->connection->executeStatement("
            update
                Usr
            set
                AccountLevel = 2,
                Subscription = 5,
                SubscriptionType = 2,
                SubscriptionPeriod = :days,
                PayPalRecurringProfileID = :paymentMethod,
                LastSubscriptionCartItemID = :cartItemId
            where
                UserID = :userId",
            [
                "userId" => $user['UserID'],
                "days" => $period,
                "paymentMethod" => $paymentMethod->id,
                "cartItemId" => $lci['CartItemID'],
            ]
        );
    }
}
