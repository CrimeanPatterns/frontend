<?php

namespace AwardWallet\MainBundle\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ClearStaleRememberMeTokensCommand extends Command
{
    protected static $defaultName = 'aw:clear-stale-remember-me-tokens';
    private Connection $connection;
    private int $securityRememberMeLifetime;

    public function __construct(Connection $connection, int $securityRememberMeLifetime)
    {
        parent::__construct();

        $this->connection = $connection;
        $this->securityRememberMeLifetime = $securityRememberMeLifetime;
    }

    public function configure()
    {
        $this
            ->setDescription('Clear stale remember-me tokens from Database');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var Connection $connection */
        $connection = $this->connection;
        $lifetime = $this->securityRememberMeLifetime;

        if (empty($lifetime) || !is_numeric($lifetime)) {
            throw new \UnexpectedValueException('Invalid cookie lifetime period');
        }
        $date = new \DateTime();
        $date->sub(new \DateInterval("PT{$lifetime}S"));
        $output->writeln("Removing tokens active before " . $date->format(DATE_ATOM) . "...");

        $removedTokens = $connection->executeUpdate('DELETE FROM RememberMeToken WHERE LastUsed < DATE_ADD(NOW(), INTERVAL -? SECOND)', [$lifetime], [\PDO::PARAM_INT]);
        $output->writeln("{$removedTokens} tokens has been removed.");

        return 0;
    }
}
