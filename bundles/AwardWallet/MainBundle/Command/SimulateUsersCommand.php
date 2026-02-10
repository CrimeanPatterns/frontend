<?php

namespace AwardWallet\MainBundle\Command;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * this is companion to MysqlBenchmarkCommand.
 */
class SimulateUsersCommand extends Command
{
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var \RemoteWebDriver
     */
    private $webDriver;

    public function __construct(LoggerInterface $logger, Connection $connection)
    {
        parent::__construct();
        $this->logger = $logger;
        $this->connection = $connection;
    }

    protected function configure()
    {
        $this
            ->setName('aw:simulate-users')
            ->addOption('users', null, InputOption::VALUE_REQUIRED, 'simulate N users', 10)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->openSelenium();
        $this->simulateUsers($input->getOption('users'));

        return 0;
    }

    private function openSelenium()
    {
        $this->webDriver = \RemoteWebDriver::create('http://selenium:4444/wd_hub', \DesiredCapabilities::firefox());
        $this->webDriver->manage()->window()->maximize();
    }

    private function simulateUsers(int $count)
    {
        $this->logger->info("loading $count users");
        $maxUserId = $this->connection->query("select max(UserID) from Usr")->fetch(\PDO::FETCH_COLUMN);
        $this->logger->info("max user id: $maxUserId");
        $simulated = 0;

        for ($n = 0; $n < $count; $n++) {
            $startUserId = round(($maxUserId / $count) * $n);
            $userId = $this->connection
                ->query("select u.Login from Account a join Usr u on a.UserID = u.UserID where u.UserID >= $startUserId order by u.UserID limit 1")
                ->fetch(\PDO::FETCH_COLUMN)
            ;

            if ($userId !== false) {
                $this->simulateUser($userId);
                $simulated++;
            }
        }

        $this->logger->info("simulated $simulated users");
    }

    private function simulateUser(string $login)
    {
        $this->logger->info("user $login");
        $this->webDriver->navigate()->to("http://awardwallet.docker/account/list?_switch_user=" . urlencode($login));

        // incomplete. will try to record log on production
    }
}
