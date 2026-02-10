<?php

namespace AwardWallet\MainBundle\Command\Billing;

use AwardWallet\MainBundle\Entity\Repositories\CartRepository;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FillSubscriptionCartCommand extends Command
{
    protected static $defaultName = 'aw:fill-subscription-cart';
    private Connection $connection;
    private LoggerInterface $logger;
    private UsrRepository $usrRepository;
    private EntityManagerInterface $entityManager;
    private CartRepository $cartRepository;

    public function __construct(
        Connection $connection,
        LoggerInterface $logger,
        UsrRepository $usrRepository,
        CartRepository $cartRepository,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct();
        $this->connection = $connection;
        $this->logger = $logger;
        $this->usrRepository = $usrRepository;
        $this->entityManager = $entityManager;
        $this->cartRepository = $cartRepository;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getOption('apply')) {
            $this->logger->info("dry run");
        }

        $this->logger->info("loading users");
        $sql = "
            select 
                UserID 
            from
                Usr 
            where
                Subscription is not null
        ";

        if (!$input->getOption('all')) {
            $sql .= " and LastSubscriptionCartItemID is null";
        }

        if ($input->getOption('userId')) {
            $sql .= " and UserID = " . (int) $input->getOption('userId');
        }

        if ($input->getOption('limit')) {
            $sql .= " limit " . $input->getOption('limit');
        }

        if ($input->getOption('start-from')) {
            $sql .= " and UserID >= " . (int) $input->getOption('start-from');
        }

        $users = $this->connection->fetchAllAssociative($sql);
        $this->logger->info("got " . count($users) . " users");

        $count = 0;

        foreach ($users as $user) {
            if (!$this->processUser($user['UserID'], $input->getOption('apply'))) {
                return 1;
            }

            $count++;

            if ($count % 1000) {
                $this->entityManager->clear();
            }
        }

        $this->logger->info("processed " . count($users) . " users");

        return 0;
    }

    protected function configure()
    {
        $this
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'how many users to process')
            ->addOption('userId', null, InputOption::VALUE_REQUIRED)
            ->addOption('start-from', null, InputOption::VALUE_REQUIRED)
            ->addOption('apply', null, InputOption::VALUE_NONE)
            ->addOption('all', null, InputOption::VALUE_NONE)
        ;
    }

    private function processUser(int $userId, bool $apply): bool
    {
        $user = $this->usrRepository->find($userId);
        $subscriptionCart = $this->cartRepository->getActiveAwSubscription($user, true, true);

        if ($subscriptionCart === null) {
            $this->logger->warning("subscription cart not found for user " . $userId);

            return false;
        }

        $subscriptionCartItem = $subscriptionCart->getSubscriptionItem();

        if ($subscriptionCartItem === null) {
            $this->logger->info("fallback to getPlusItem for user {$userId}, cart {$subscriptionCart->getCartid()}");
            $subscriptionCartItem = $subscriptionCart->getPlusItem();
        }

        if ($subscriptionCartItem === null) {
            $this->logger->warning("subscription cart item not found for user " . $userId . ", cart {$subscriptionCart->getCartid()}");

            return false;
        }

        $this->logger->info("user {$user->getId()}: set last subscription cart item to {$subscriptionCartItem->getCartitemid()}, cart {$subscriptionCart->getCartid()}");

        if ($apply) {
            $user->setLastSubscriptionCartItem($subscriptionCartItem);
            $this->entityManager->flush();
        }

        return true;
    }
}
