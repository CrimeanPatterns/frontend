<?php

namespace AwardWallet\MainBundle\Command\Billing;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusPrepaid;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Event\CartMarkPaidEvent;
use AwardWallet\MainBundle\Service\Billing\GooglePlayCartPaidListener;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SuspendAndroidCommand extends Command
{
    protected static $defaultName = 'aw:billing:suspend-android';

    private LoggerInterface $logger;
    private Connection $connection;
    private EntityManagerInterface $entityManager;

    private array $errors = [];
    private GooglePlayCartPaidListener $cartPaidListener;

    public function __construct(
        LoggerInterface $paymentLogger,
        Connection $connection,
        EntityManagerInterface $entityManager,
        GooglePlayCartPaidListener $cartPaidListener
    ) {
        parent::__construct();

        $this->logger = $paymentLogger;
        $this->connection = $connection;
        $this->entityManager = $entityManager;
        $this->cartPaidListener = $cartPaidListener;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $users = $this->loadUsers($input);

        foreach ($users as $user) {
            $this->processUser($user, $input);
        }

        $this->showErrors();

        return count($this->errors) ? 1 : 0;
    }

    protected function configure()
    {
        $this
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'how many users to process')
            ->addOption('userId', null, InputOption::VALUE_REQUIRED)
            ->addOption('start-from', null, InputOption::VALUE_REQUIRED)
            ->addOption('before', null, InputOption::VALUE_REQUIRED)
            ->addOption('fix-missing', null, InputOption::VALUE_NONE)
            ->addOption('prepaid', null, InputOption::VALUE_NONE)
            ->addOption('apply', null, InputOption::VALUE_NONE)
        ;
    }

    private function loadUsers(InputInterface $input): array
    {
        $this->logger->info("loading users");
        $sql = "
            select 
                u.UserID, 
                u.SubscriptionPeriod,
                u.PlusExpirationDate,
                lsc.PaymentType,
                lsc.CartID
            from
                Usr u
                join CartItem lsci on lsci.CartItemID = u.LastSubscriptionCartItemID
                join Cart lsc on lsci.CartID = lsc.CartID
            where
                u.Subscription = " . Usr::SUBSCRIPTION_MOBILE . " and u.IosReceipt is null
                and u.UserID in (
                    select c.UserID from Cart c join CartItem ci on c.CartID = ci.CartID 
                    where 
                    c.PayDate is not null and ci.TypeID = " . AwPlusPrepaid::TYPE . " 
                    and c.CartID not in(
                        select c3.CartID from Cart c3 join CartItem ci3 on c3.CartID = ci3.CartID 
                        where c3.PayDate is not null and ci3.TypeID in (" . AwPlusSubscription::TYPE . ")
                    )
                )
        ";

        if ($input->getOption('userId')) {
            $sql .= " and u.UserID = " . (int) $input->getOption('userId');
        }

        if ($input->getOption('fix-missing')) {
            $sql .= " and PaypalSuspendedUntilDate is null";
        }

        if ($input->getOption('limit')) {
            $sql .= " limit " . $input->getOption('limit');
        }

        $users = $this->connection->fetchAllAssociative($sql);
        $this->logger->info("got " . count($users) . " users");

        return $users;
    }

    private function processUser(array $user, InputInterface $input): void
    {
        $this->logger->info("processing user {$user['UserID']}, cart {$user['CartID']}");
        $cart = $this->entityManager->find(Cart::class, $user['CartID']);

        if ($input->getOption('apply')) {
            $this->cartPaidListener->onCartMarkPaid(new CartMarkPaidEvent($cart));
        } else {
            $this->logger->info("dry run, user: {$user['UserID']}, cart: {$user['CartID']}");
        }
    }

    private function addError(string $error)
    {
        $this->logger->error($error);
        $this->errors[] = $error;
    }

    private function showErrors()
    {
        foreach ($this->errors as $error) {
            $this->logger->error($error);
        }

        if (count($this->errors) > 0) {
            $this->logger->error("we got " . count($this->errors) . " errors");
        } else {
            $this->logger->info("success");
        }
    }
}
