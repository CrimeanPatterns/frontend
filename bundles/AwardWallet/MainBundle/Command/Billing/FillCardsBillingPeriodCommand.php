<?php

namespace AwardWallet\MainBundle\Command\Billing;

use AwardWallet\MainBundle\Entity\Repositories\CartRepository;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\Cart\SubscriptionPeriod;
use AwardWallet\MainBundle\Globals\Cart\SubscriptionPrice;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FillCardsBillingPeriodCommand extends Command
{
    protected static $defaultName = 'aw:fill-cards-billing-period';
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
        $this->logger->info("loading users");
        $sql = "
            select 
                UserID 
            from
                Usr 
            where
                Subscription in (" . join(", ", [Usr::SUBSCRIPTION_STRIPE, Usr::SUBSCRIPTION_SAVED_CARD, Usr::SUBSCRIPTION_MOBILE]) . ")
        ";

        if ($input->getOption('userId')) {
            $sql .= " and UserID = " . (int) $input->getOption('userId');
        }

        if ($input->getOption('start-from')) {
            $sql .= " and UserID >= " . (int) $input->getOption('start-from');
        }

        if ($input->getOption('fix-missing')) {
            $sql .= " and SubscriptionPeriod is null";
        }

        if ($input->getOption('limit')) {
            $sql .= " limit " . $input->getOption('limit');
        }

        $users = $this->connection->fetchAllAssociative($sql);
        $this->logger->info("got " . count($users) . " users");

        foreach ($users as $user) {
            $this->processUser($user['UserID']);
        }
        $this->logger->info("processed " . count($users) . " users");
    }

    protected function configure()
    {
        $this
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'how many users to process')
            ->addOption('userId', null, InputOption::VALUE_REQUIRED)
            ->addOption('start-from', null, InputOption::VALUE_REQUIRED)
            ->addOption('fix-missing', null, InputOption::VALUE_NONE)
        ;
    }

    private function processUser(int $userId)
    {
        $user = $this->usrRepository->find($userId);
        $subscriptionCart = $this->cartRepository->getActiveAwSubscription($user, true, true);
        $user->setSubscriptionPeriod(SubscriptionPeriod::DURATION_TO_DAYS[$subscriptionCart->getPlusItem()->getDuration()]);
        $user->setSubscriptionPrice(SubscriptionPrice::getPrice($user->getSubscriptionType(), $subscriptionCart->getPlusItem()->getDuration()));
        $this->entityManager->flush();
        $this->logger->info("user {$user->getId()}: set subscription period to {$user->getSubscriptionPeriod()}, price: {$user->getSubscriptionPrice()}, total: {$subscriptionCart->getTotalPrice()}");
    }
}
