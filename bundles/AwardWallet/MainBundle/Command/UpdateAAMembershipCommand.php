<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateAAMembershipCommand extends Command
{
    protected static $defaultName = 'aw:update-aa-membership';

    protected Connection $connection;

    public function __construct(
        Connection $connection
    ) {
        parent::__construct();
        $this->connection = $connection;
    }

    protected function configure()
    {
        $this
            ->setDescription('Update American Airlines Membership Snapshot')
            ->setDefinition([])
            ->addOption(
                'date',
                null,
                InputOption::VALUE_REQUIRED,
                'Date which snapshot will be assigned to'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $curDate = $input->getOption('date');

        if ($curDate) {
            if (!preg_match('/\d{4,4}-\d{2,2}-\d{2,2}/', $curDate)) {
                exit("ERROR: Date should be in YYYY-MM-DD format\n");
            }
        } else {
            $curDate = date("Y-m-d");
        }
        $output->writeln("<info>Date is set to $curDate</info>");
        $monthBeginning = date('Y-m', strtotime($curDate)) . '-01';
        $monthEnding = date('Y-m-d', strtotime('+1 month', strtotime($monthBeginning)));

        $this->connection->executeQuery(
            "delete from AAMembership where SnapDate >= ? and SnapDate <= ?",
            [$monthBeginning, $monthEnding],
            [\PDO::PARAM_STR, \PDO::PARAM_STR]
        );
        $aaid = $this->connection->executeQuery(
            "select ProviderID from Provider where Code = ?",
            ['aa'],
            [\PDO::PARAM_STR]
        )->fetch(\PDO::FETCH_ASSOC)['ProviderID'];
        $usid = $this->connection->executeQuery(
            "select ProviderID from Provider where Code = ?",
            ['dividendmiles'],
            [\PDO::PARAM_STR]
        )->fetch(\PDO::FETCH_ASSOC)['ProviderID'];
        $q = $this->connection->executeQuery(
            "select UserID, FirstName, LastName, Balance, ExpirationDate, Login, AccountID, ProviderID, UserAgentID, utype
            from
            (
                select
                u.UserID,
                u.FirstName,
                u.LastName,
                a.Balance,
                a.ExpirationDate,
                a.Login,
                a.AccountID,
                a.ProviderID,
                a.UserAgentID,
                1 as utype
            from
                Usr u
                join Account a on a.UserID = u.UserID
            where
                a.UserAgentID is null and
                (a.ProviderID = ? or a.ProviderID = ?)
            union
            select
                ua.AgentID as UserID,
                ua.FirstName,
                ua.LastName,
                a.Balance,
                a.ExpirationDate,
                a.Login,
                a.AccountID,
                a.ProviderID,
                a.UserAgentID,
                2 as utype
            from
                Account a
                join UserAgent ua on a.UserAgentID = ua.UserAgentID
            where
                ua.ClientID is null and
                (a.ProviderID = ? or a.ProviderID = ?)) x where 1 = 1",
            [$aaid, $usid, $aaid, $usid],
            [\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT]
        );
        $rowCount = 0;

        while ($row = $q->fetch(\PDO::FETCH_ASSOC)) {
            if (($rowCount % 1000) == 0) {
                $output->writeln("$rowCount accounts processed..");
            }
            $firstName = $row['FirstName'];
            $lastName = $row['LastName'];
            $balance = $row['Balance'];
            $uid = $row['UserID'];
            $aid = $row['AccountID'];
            $pid = $row['ProviderID'];

            if ($row['utype'] == 1) {
                $visitFilter = " and VisitDate >= ? and VisitDate <= ?";
                $q2 = $this->connection->executeQuery(
                    'select coalesce(sum(Visits), 0) as s from Visit where UserID = ?' . $visitFilter,
                    [$monthBeginning, $monthEnding, $row['UserID']],
                    [\PDO::PARAM_STR, \PDO::PARAM_STR, \PDO::PARAM_INT]
                );

                if ($r = $q2->fetch(\PDO::FETCH_ASSOC)) {
                    $visits = $r['s'];
                } else {
                    $visits = 0;
                }
            } else {
                $visits = null;
            }
            $expiration = $row['ExpirationDate'];
            $account = $row['Login'];
            $q3 = $this->connection->executeQuery(
                'select Val from AccountProperty ap where ap.AccountID = ? and ap.ProviderPropertyID in (select ProviderPropertyID from ProviderProperty pp where (pp.ProviderID = ? or pp.ProviderID = ?)and pp.Code = "Status")',
                [$row['AccountID'], $aaid, $usid],
                [\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT]
            );

            if ($r = $q3->fetch(\PDO::FETCH_ASSOC)) {
                $val = $r['Val'];
            } else {
                $val = '';
            }
            $status = $val;

            if ($row['utype'] == 1) {
                $reqs = "select count(*) as c from Account a join Provider p on a.ProviderID = p.ProviderID where a.UserID = ? and a.UserAgentID is null and p.Category = ? and p.Kind = 1";
                $q4 = $this->connection->executeQuery(
                    $reqs, [$row['UserID'], 1], [\PDO::PARAM_INT, \PDO::PARAM_INT]
                )->fetch(\PDO::FETCH_ASSOC);
                $t1 = $q4['c'];
                $q4 = $this->connection->executeQuery(
                    $reqs, [$row['UserID'], 2], [\PDO::PARAM_INT, \PDO::PARAM_INT]
                )->fetch(\PDO::FETCH_ASSOC);
                $t2 = $q4['c'];
                $q4 = $this->connection->executeQuery(
                    $reqs, [$row['UserID'], 3], [\PDO::PARAM_INT, \PDO::PARAM_INT]
                )->fetch(\PDO::FETCH_ASSOC);
                $t3 = $q4['c'];
            } else {
                $reqs = "select count(*) as c from Account a join Provider p on a.ProviderID = p.ProviderID where a.UserAgentID = ? and p.Category = ? and p.Kind = 1";
                $q4 = $this->connection->executeQuery(
                    $reqs, [$row['UserAgentID'], 1], [\PDO::PARAM_INT, \PDO::PARAM_INT]
                )->fetch(\PDO::FETCH_ASSOC);
                $t1 = $q4['c'];
                $q4 = $this->connection->executeQuery(
                    $reqs, [$row['UserAgentID'], 2], [\PDO::PARAM_INT, \PDO::PARAM_INT]
                )->fetch(\PDO::FETCH_ASSOC);
                $t2 = $q4['c'];
                $q4 = $this->connection->executeQuery(
                    $reqs, [$row['UserAgentID'], 3], [\PDO::PARAM_INT, \PDO::PARAM_INT]
                )->fetch(\PDO::FETCH_ASSOC);
                $t3 = $q4['c'];
            }
            $this->connection->executeQuery(
                "insert into AAMembership
                (UserID, ProviderID, AccountID, FirstName, LastName, Visits, Balance, Expiration, Account, Status, Tier1, Tier2, Tier3, SnapDate)
                values
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [$uid, $pid, $aid, $firstName, $lastName, $visits, $balance, $expiration, $account, $status, $t1, $t2, $t3, $curDate],
                [\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_STR, \PDO::PARAM_STR, \PDO::PARAM_INT, \PDO::PARAM_STR, \PDO::PARAM_STR, \PDO::PARAM_STR, \PDO::PARAM_STR, \PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_STR]
            );
            $rowCount++;
        }
        $output->writeln("$rowCount accounts stored");
        $output->writeln("cleaning up visits");
        $this->connection->executeUpdate("delete from Visit where VisitDate < adddate(now(), -60)");

        return 0;
    }
}
