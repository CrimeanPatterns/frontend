<?php

namespace AwardWallet\MainBundle\Command\Fix;

use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Service\Billing\ExpirationCalculator;
use AwardWallet\MainBundle\Service\Billing\PlusManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FixDoubleTrialsCommand extends Command
{
    public static $defaultName = 'aw:fix-double-trials';
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var ExpirationCalculator
     */
    private $expirationCalculator;
    /**
     * @var PlusManager
     */
    private $plusManager;
    /**
     * @var UsrRepository
     */
    private $usrRepository;

    public function __construct(LoggerInterface $paymentLogger, Connection $connection, ExpirationCalculator $expirationCalculator, PlusManager $plusManager, UsrRepository $usrRepository)
    {
        parent::__construct();
        $this->logger = $paymentLogger;
        $this->connection = $connection;
        $this->expirationCalculator = $expirationCalculator;
        $this->plusManager = $plusManager;
        $this->usrRepository = $usrRepository;
    }

    public function configure()
    {
        $this->addOption('apply', null, InputOption::VALUE_NONE, 'apply fixes, otherwise dry run');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $apply = $input->getOption('apply');

        $this->logger->info("searching for users with double trials");
        $q = $this->connection->executeQuery("
            select 
              c.UserID,
              count(distinct ci.CartItemID) as TrialsGiven,
              count(distinct c.CartID) as CartsCount,
              count(distinct ci2.CartItemID) as CartItemsCount,
              min(c.PayDate) as MinPayDate,
              max(c.PayDate) as MaxPayDate,
              min(c.CartID) as MinCartID,
              max(c.CartID) as MaxCartID
            from 
              Cart c
              join CartItem ci on c.CartID = ci.CartID
              join CartItem ci2 on c.CartID = ci2.CartID
            where
              ci.TypeID = 10
              and c.PayDate is not null
              and c.UserID is not null
            group by 
              c.UserID
            having 
              count(distinct ci.CartItemID) > 1
              and max(c.PayDate) > adddate(now(), -182)
              and ABS(TIME_TO_SEC(TIMEDIFF(min(c.PayDate), max(c.PayDate)))) < 300
              and count(distinct ci2.CartItemID) = count(distinct ci.CartItemID)
        ");

        $this->logger->info("processing");
        $fixed = 0;

        while ($row = $q->fetch(FetchMode::ASSOCIATIVE)) {
            $this->logger->info("found: " . json_encode($row));

            if ($row["CartsCount"] > 2) {
                $this->logger->warning("too many carts, skip");

                continue;
            }

            if ($apply) {
                $fixed++;
                $this->logger->info("fixing, deleting cart {$row['MaxCartID']}");
                $this->connection->executeUpdate("delete from Cart where CartID = ?", [$row['MaxCartID']]);
                $expiration = $this->expirationCalculator->getAccountExpiration($row['UserID']);
                $user = $this->usrRepository->find($row['UserID']);
                $this->plusManager->correctExpirationDate($user, $expiration['date'], "expiration recalculated");
            }
        }

        $this->logger->info("done, fixed: {$fixed}");

        return 0;
    }
}
