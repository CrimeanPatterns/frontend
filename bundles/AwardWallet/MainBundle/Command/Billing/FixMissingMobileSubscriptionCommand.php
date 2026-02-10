<?php

namespace AwardWallet\MainBundle\Command\Billing;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\Repositories\CartRepository;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FixMissingMobileSubscriptionCommand extends Command
{
    protected static $defaultName = 'aw:fix-missing-mobile-subscription';

    private Connection $connection;
    private LoggerInterface $logger;
    private UsrRepository $usrRepository;
    private EntityManagerInterface $entityManager;
    private CartRepository $cartRepository;
    private InputInterface $input;

    private int $fixes = 0;

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
        $this->input = $input;
        $this->logger->info("loading users");
        $sql = "
            select 
                UserID 
            from
                Usr 
            where
                Subscription is null
                and AccountLevel = " . ACCOUNT_LEVEL_AWPLUS . "
                and SubscriptionType = " . Usr::SUBSCRIPTION_TYPE_AWPLUS;

        if ($input->getOption('userId')) {
            $sql .= " and UserID = " . (int) $input->getOption('userId');
        }

        if ($input->getOption('limit')) {
            $sql .= " limit " . $input->getOption('limit');
        }

        $users = $this->connection->fetchAllAssociative($sql);
        $this->logger->info("got " . count($users) . " users");

        foreach ($users as $user) {
            $this->processUser($user['UserID']);
        }

        $this->logger->info("processed " . count($users) . " users, fixed: {$this->fixes}");
    }

    protected function configure()
    {
        $this
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'how many users to process')
            ->addOption('userId', null, InputOption::VALUE_REQUIRED)
            ->addOption('fix', null, InputOption::VALUE_NONE)
        ;
    }

    private function processUser(int $userId)
    {
        $user = $this->usrRepository->find($userId);
        $subscriptionCart = $this->cartRepository->getActiveAwSubscription($user, false);

        if ($subscriptionCart === null) {
            $this->logger->warning("user {$user->getId()}: missing subscription, and no subscription cart");

            return;
        }

        if (
            $subscriptionCart->hasMobileAwPlusSubscription()
            && $subscriptionCart->getCartid() === $user->getLastSubscriptionCartItem()->getCart()->getCartid()
        ) {
            $this->fixSubscription($user, $subscriptionCart);
        } else {
            $this->logger->warning("user {$user->getId()}: missing subscription, and not mobile");
        }
    }

    private function fixSubscription(Usr $user, Cart $subscriptionCart)
    {
        if ($subscriptionCart->getPaydate()->getTimestamp() < strtotime("2024-10-01")) {
            $this->logger->warning("subscription is too old", ["UserID" => $user->getId(), "CartID" => $subscriptionCart->getCartid()]);

            return;
        }

        $this->logger->info("will fix subscription", ["UserID" => $user->getId(), "CartID" => $subscriptionCart->getCartid()]);

        if (!$this->input->getOption('fix')) {
            $this->logger->info("dry run");

            return;
        }

        $user->setSubscription(Usr::SUBSCRIPTION_MOBILE);
        $this->entityManager->flush();
        $this->logger->info("user mobile subscription recovered", ["CartID" => $subscriptionCart->getCartid(), "UserID" => $user->getId()]);

        $this->fixes++;
    }
}
