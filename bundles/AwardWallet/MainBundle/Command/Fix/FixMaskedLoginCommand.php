<?php

namespace AwardWallet\MainBundle\Command\Fix;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FixMaskedLoginCommand extends Command
{
    protected static $defaultName = 'aw:fix-masked-accounts';

    private Connection $connection;

    public function __construct(
        Connection $connection
    ) {
        parent::__construct();
        $this->connection = $connection;
    }

    public function configure()
    {
        $this
            ->setDescription('Fixing state of accounts with masked login')
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'do not skip accounts with invalid masks');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var Connection $conn */
        $conn = $this->connection;
        $c = 0;
        $skipped = [];
        $output->writeln(date('Y-m-d H:i:s') . ' query started');
        $query = $conn->executeQuery('
            select a.AccountID, a.Login, p.Code 
            from Account a
                left join Provider p on a.ProviderID = p.ProviderID
            where a.State = 1 and p.Code is not null and a.Login like \'%**%\'');
        $output->writeln(date('Y-m-d H:i:s') . ' query finished, updating');

        while ($row = $query->fetchAssociative()) {
            if (!empty($input->getOption('all')) || preg_match('/^[*]{4}[^*]+$|^[^*]+[*]{4}$|^[^*]+[*]{2}[^*]+$/', $row['Login']) > 0) {
                $conn->update('Account', ['State' => ACCOUNT_PENDING], ['AccountID' => $row['AccountID']], [\PDO::PARAM_INT, \PDO::PARAM_INT]);
                $c++;
            } else {
                $skipped[] = $row;
            }
        }
        $output->writeln(date('Y-m-d H:i:s') . ' updated ' . $c . ' accounts');
        $output->writeln(date('Y-m-d H:i:s') . ' skipped ' . count($skipped) . ' accounts');

        foreach ($skipped as $row) {
            $output->writeln(sprintf('%d %s %s', $row['AccountID'], $row['Code'], $row['Login']));
        }
        $output->writeln('done');

        return 0;
    }
}
