<?php

namespace AwardWallet\MainBundle\Command\Billing;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusPrepaid;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Event\CartMarkPaidEvent;
use AwardWallet\MainBundle\Service\Billing\PaypalCartPaidListener;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SuspendPaypalCommand extends Command
{
    protected static $defaultName = 'aw:billing:suspend-paypal';

    private LoggerInterface $logger;
    private Connection $connection;
    private EntityManagerInterface $entityManager;

    private array $errors = [];
    private PaypalCartPaidListener $cartPaidListener;

    public function __construct(
        LoggerInterface $paymentLogger,
        Connection $connection,
        EntityManagerInterface $entityManager,
        PaypalCartPaidListener $cartPaidListener
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
            $this->processUser($user["UserID"], $input);
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
                UserID, 
                PaypalRecurringProfileID,
                SubscriptionPeriod,
                NextBillingDate,
                PlusExpirationDate,
                PaypalSuspendedUntilDate
            from
                Usr 
            where
                Subscription = " . Usr::SUBSCRIPTION_PAYPAL . "
                and AccountLevel <> " . ACCOUNT_LEVEL_FREE . "
                and PlusExpirationDate is not null
                and DATEDIFF(PlusExpirationDate, NextBillingDate) >= 90
        ";

        if ($input->getOption('userId')) {
            $sql .= " and UserID = " . (int) $input->getOption('userId');
        }

        if ($input->getOption('start-from')) {
            $sql .= " and UserID >= " . (int) $input->getOption('start-from');
        }

        if ($input->getOption('before')) {
            $sql .= " and UserID < " . (int) $input->getOption('before');
        }

        if ($input->getOption('fix-missing')) {
            $sql .= " and PaypalSuspendedUntilDate is null";
        }

        if ($input->getOption('prepaid')) {
            $sql .= " and UserID in (select c.UserID from Cart c join CartItem ci on c.CartID = ci.CartID where c.PayDate is not null and ci.TypeID = " . AwPlusPrepaid::TYPE . ")";
        }

        if ($input->getOption('limit')) {
            $sql .= " limit " . $input->getOption('limit');
        }

        $users = $this->connection->fetchAllAssociative($sql);
        $this->logger->info("got " . count($users) . " users");

        return $users;
    }

    private function processUser(int $userId, InputInterface $input): void
    {
        $cartId = $this->connection->fetchOne("select CartID from Cart where UserID = ?", [$userId]);
        $cart = $this->entityManager->find(Cart::class, $cartId);

        if ($input->getOption('apply')) {
            $this->cartPaidListener->onCartMarkPaid(new CartMarkPaidEvent($cart));
        } else {
            $this->logger->info("dry run, user: $userId");
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
