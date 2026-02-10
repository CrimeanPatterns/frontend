<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use Doctrine\ORM\Query;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CheckHealthCommand extends Command
{
    protected $dry;
    /** @var OutputInterface */
    protected $output;
    private \Doctrine\Persistence\ManagerRegistry $registry;

    public function __construct(\Doctrine\Persistence\ManagerRegistry $registry)
    {
        $this->registry = $registry;
        parent::__construct();
    }

    public function configure()
    {
        $this->setName("aw:providers:check-health");
        $this->setDescription("Check providers health on custom criteria");
        $this->setDefinition([
            new InputOption('dry', null, InputOption::VALUE_NONE, 'Dry run (only output to console, do not send email)'),
        ]);
    }

    protected function log($s)
    {
        $this->output->writeln(date("Y-m-d H:i:s") . ": $s");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        $this->dry = $input->getOption('dry');

        if ($this->dry) {
            $this->log('Run in dry mode');
        }
        $this->log("Run query");

        $lastHours = 3;
        $minFailRate = 3; // percent
        $limitFailAccounts = 50;

        $doctrine = $this->registry;
        /** @var $connection \Doctrine\DBAL\Connection */
        $connection = $doctrine->getManager()->getConnection();
        $stmt = $connection->executeQuery("
			SELECT p.ProviderID, p.Code, COUNT(a.AccountID) as FailAccounts, p.Accounts, (COUNT(a.AccountID)/p.Accounts*100) as FailRate
            FROM Provider p
                JOIN Account a ON a.ProviderID = p.ProviderID
            WHERE
                p.State >= :state
                AND p.CanCheck = 1
                AND p.CanCheckBalance = 1
                AND a.UpdateDate > NOW() - INTERVAL :hours HOUR
                AND a.Balance = 0
            GROUP BY p.ProviderID, p.Code, p.Accounts
            HAVING (COUNT(a.AccountID)/p.Accounts*100) > :rate
            ORDER BY FailRate DESC, p.Accounts DESC
			",
            [
                ':state' => PROVIDER_ENABLED,
                ':rate' => $minFailRate,
                ':hours' => $lastHours,
            ]
        );
        $rows = $stmt->fetchAll(Query::HYDRATE_ARRAY);

        if (count($rows)) {
            $this->log("Found " . count($rows) . " broken providers");

            $message = "";
            $sendNotification = false;

            foreach ($rows as $row) {
                if ($row['FailAccounts'] > $limitFailAccounts) {
                    $sendNotification = true;
                }
                $message .= "Provider {$row['Code']} update balance to 0 for {$row['FailRate']}% accounts ( {$row['FailAccounts']} / {$row['Accounts']} ) in last {$lastHours} hours.\n";
            }
            $this->log("Message: \n$message");

            if (!$this->dry && $sendNotification) {
                mail(ConfigValue(CONFIG_ERROR_EMAIL), "Broken providers (zero balance)", $message, EMAIL_HEADERS);
            }
            $this->log("Email sent");
        } else {
            $this->log("Not found broken providers");
        }

        return 0;
    }
}
