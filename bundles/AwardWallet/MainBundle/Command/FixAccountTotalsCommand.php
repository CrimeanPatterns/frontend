<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Loyalty\AccountSaving\AccountTotalCalculator;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FixAccountTotalsCommand extends Command
{
    public static $defaultName = 'aw:fix-account-totals';

    private LoggerInterface $logger;

    private Connection $unbufConnection;

    private EntityManagerInterface $em;

    private AccountTotalCalculator $calculator;

    public function __construct(
        LoggerInterface $logger,
        Connection $unbufConnection,
        EntityManagerInterface $em,
        AccountTotalCalculator $calculator
    ) {
        parent::__construct();

        $this->logger = $logger;
        $this->unbufConnection = $unbufConnection;
        $this->em = $em;
        $this->calculator = $calculator;
    }

    protected function configure()
    {
        parent::configure();

        $this
            ->addOption('startAccountId', null, InputOption::VALUE_REQUIRED)
            ->addOption('accountId', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY)
            ->addOption('userId', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY)
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'dry run')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger->info('update account totals');
        $dryRun = !empty($input->getOption('dry-run'));

        $filter = '';

        if (!empty($startAccountId = $input->getOption('startAccountId'))) {
            $startAccountId = (int) $startAccountId;
            $this->logger->info(sprintf('start account id: %d', $startAccountId));
            $filter .= " AND AccountID >= $startAccountId";
        }

        if (!empty($accountIds = $input->getOption('accountId'))) {
            $accountIds = array_map('intval', $accountIds);
            $this->logger->info(sprintf('account id: [%s]', implode(', ', $accountIds)));
            $filter .= " AND AccountID IN (" . implode(', ', $accountIds) . ")";
        }

        if (!empty($userIds = $input->getOption('userId'))) {
            $userIds = array_map('intval', $userIds);
            $this->logger->info(sprintf('user id: [%s]', implode(', ', $userIds)));
            $filter .= " AND UserID IN (" . implode(', ', $userIds) . ")";
        }

        if ($dryRun) {
            $this->logger->info('dry run');
        }

        $accountRep = $this->em->getRepository(Account::class);
        $processed = 0;

        $this->unbufConnection->executeStatement("set wait_timeout = 86400");
        $this->unbufConnection->executeStatement("set interactive_timeout = 86400");
        $this->unbufConnection->executeStatement("set net_read_timeout = 86400");
        $q = $this->unbufConnection->executeQuery("
            SELECT AccountID FROM Account WHERE 1 = 1 $filter ORDER BY AccountID ASC
        ");

        while ($accountId = $q->fetchOne()) {
            $accountId = (int) $accountId;
            /** @var Account $account */
            $account = $accountRep->find($accountId);

            if (!$account) {
                $this->logger->info(sprintf('account #%d not found, skip', $accountId));

                continue;
            }

            $total = $this->calculator->calculate($account);
            $this->logger->info(sprintf(
                'account total, %d, %d, total: %s',
                $account->getUser()->getId(),
                $accountId,
                sprintf('%0.2f', $total)
            ));

            if (!$dryRun) {
                $account->setTotalbalance($total);
                $this->em->flush();
            }
            $processed++;

            if (($processed % 100) === 0) {
                $this->em->clear();
            }
        }

        $this->logger->info(sprintf('done. Updated accounts: %d', $processed));

        return 0;
    }
}
