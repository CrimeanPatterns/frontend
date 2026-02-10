<?php

namespace AwardWallet\MainBundle\Command\Billing;

use AwardWallet\MainBundle\Entity\CartItem\AwPlusPrepaid;
use AwardWallet\MainBundle\Entity\Repositories\CartRepository;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDO\MySQL\Driver;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class CalcPrepaymentsCommand extends Command
{
    protected static $defaultName = 'aw:calc-prepayments';
    private Connection $connection;
    private LoggerInterface $logger;
    private UsrRepository $usrRepository;
    private EntityManagerInterface $entityManager;
    private CartRepository $cartRepository;
    private Connection $stagingConnection;

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
        $this->stagingConnection = new Connection([
            'host' => 'host.docker.internal',
            'dbname' => 'awardwallet',
            'user' => 'awardwallet',
            'password' => 'awardwallet',
            'port' => 3307,
        ], new Driver());
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->logger->info("loading users");
        $sql = "
            select
                u.UserID,
                min(c.CartID) as CartID
            from
                Usr u
                join Cart c on c.UserID = u.UserID
                join CartItem ci on ci.CartID = c.CartID
            where
                c.PayDate is not null
                and ci.TypeID = " . AwPlusPrepaid::TYPE . "
            group by
                u.UserID
        ";

        if ($input->getOption('userId')) {
            $sql .= " and UserID = " . (int) $input->getOption('userId');
        }

        $users = $this->connection->fetchAllAssociative($sql);
        $this->logger->info("got " . count($users) . " users who bought prepaid aw+");
        $stagingPrepaidUsers = $this->stagingConnection->fetchFirstColumn("
                    select distinct
                        u.UserID 
                    from 
                        Usr u 
                        join Cart c on c.UserID = u.UserID 
                        join CartItem ci on ci.CartID = c.CartID 
                    where ci.TypeID = " . AwPlusPrepaid::TYPE . "
                    and c.PayDate is not null"
        );
        $users = array_filter($users, fn (array $user) => !in_array($user['UserID'], $stagingPrepaidUsers));
        $this->logger->info("got " . count($users) . " users who bought prepaid aw+, and we don't have this buy on staging");

        $users = it($users)
            ->chunk(100)
            ->flatMap(function (array $users) {
                $userIds = array_map(fn (array $user) => $user['UserID'], $users);
                $subStatuses = $this->stagingConnection->fetchAllKeyValue("select UserID, Subscription from Usr where UserID in (:users)", ["users" => $userIds], ["users" => Connection::PARAM_INT_ARRAY]);
                $users = array_filter($users, fn (array $user) => array_key_exists($user['UserID'], $subStatuses));
                $users = array_map(fn (array $user) => [
                    'UserID' => $user['UserID'],
                    'CartID' => $user['CartID'],
                    'Subscription' => $subStatuses[$user['UserID']],
                ], $users);

                return $users;
            })
            ->toArray()
        ;

        $this->logger->info("got " . count($users) . " users who bought prepaid aw+ and we know their subscription status");
        $subscribers = array_filter($users, fn (array $user) => $user['Subscription'] !== null);

        $this->logger->info("processed " . count($users) . " users, subscribers: " . count($subscribers) . ", subscribers/all ratio: " . round(count($subscribers) / count($users) * 100) . "%");

        return 0;
    }

    protected function configure()
    {
        $this
            ->addOption('userId', null, InputOption::VALUE_REQUIRED)
        ;
    }
}
