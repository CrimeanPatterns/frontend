<?php

namespace AwardWallet\MainBundle\Command\Account;

use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Account\LufthansaExpiration;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class LufthansaExpirationCommand extends Command
{
    protected static $defaultName = 'aw:email:account:lufthansa-expiration';

    /** @var UsrRepository */
    private $userRepository;

    /** @var Connection */
    private $connection;
    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;
    private Connection $replicaUnbufferedConnection;
    private Mailer $mailer;

    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        Connection $replicaUnbufferedConnection,
        Mailer $mailer
    ) {
        parent::__construct();
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->replicaUnbufferedConnection = $replicaUnbufferedConnection;
        $this->mailer = $mailer;
    }

    protected function configure()
    {
        $this
            ->setDescription('Mailing Lufthansa expiration warning')
            ->addOption('test', 't', InputOption::VALUE_NONE, 'run test')
            ->addOption('userId', 'u', InputOption::VALUE_OPTIONAL, 'filter by userId')
            ->addOption('providerId', 'p', InputOption::VALUE_OPTIONAL, 'filter by providerId')
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'limit of emails');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logger = $this->logger;
        $this->connection = $this->entityManager->getConnection();
        $unbuffConn = $this->replicaUnbufferedConnection;
        $entityManager = $this->entityManager;
        $this->userRepository = $entityManager->getRepository(Usr::class);

        if ($input->getOption('test')) {
            $this->test($input, $output);

            return 0;
        }

        $userId = $input->getOption('userId');
        $providerId = (int) $input->getOption('providerId');
        $limit = (int) $input->getOption('limit');

        if (empty($providerId)) {
            $providerId = 39; // Lufthansa
        }

        if (!empty($userId)) {
            $userId = explode(',', $userId);
            $userId = array_map('intval', $userId);
        }

        $notifiedAccounts = [];
        $accounts = $unbuffConn->executeQuery('
            SELECT AccountID, UserID, Login
            FROM Account
            WHERE
                    ProviderID = ' . $providerId . '
                AND ErrorCode  IN (' . implode(',', [ACCOUNT_CHECKED, ACCOUNT_TIMEOUT, ACCOUNT_QUESTION]) . ')
                ' . (empty($userId) ? '' : 'AND UserID IN (' . implode(',', $userId) . ')') . '
        ');

        $output->writeln('Fetch account');
        $processed = $index = 0;

        while ($account = $accounts->fetch()) {
            if ($limit && $index >= $limit) {
                break;
            }

            $history = $this->connection->fetchAll('
                SELECT
                    MONTH(ah.PostingDate) as _month,
                    YEAR(ah.PostingDate) as _year,
                    SUM(CASE WHEN ah.Miles > 0 THEN ah.Miles ELSE 0 END) as _sumPositiveAmount
                FROM
                        AccountHistory ah
                JOIN
                        Account a
                WHERE
                            ah.AccountID   = ' . $account['AccountID'] . '
                        AND ah.AccountID   = a.AccountID
                        AND a.Disabled     = 0
                        AND ah.Description LIKE ' . $this->connection->quote('%M&M Credit Card%') . '
                GROUP BY
                        MONTH(ah.PostingDate),
                        YEAR(ah.PostingDate),
                        ah.PostingDate
                ORDER BY
                        ah.PostingDate DESC
                LIMIT 3
            ');

            $result = $this->calc($history);

            if (is_array($result) && array_key_exists('date', $result)) {
                if ($this->notifyUser($account, $result['date'])) {
                    $notifiedAccounts[] = $account['AccountID'];
                    ++$index;
                }
            }

            if (0 === (++$processed % 200)) {
                $output->writeln('processed ' . $processed . ', sent: ' . $index . ' ...');
            }
        }

        $logger->info('Expiration notifications sent to accounts: ' . implode(', ', $notifiedAccounts), ['accountId' => $notifiedAccounts]);
        $output->writeln('Emails are sent, count: ' . $index . ', accounts [' . implode(', ', $notifiedAccounts) . ']');

        return 0;
    }

    private function calc($history)
    {
        $ago1Month = date('Yn', strtotime('-1 month'));
        $ago2Month = date('Yn', strtotime('-2 month'));

        $historyIndex = 0;
        $isPositiveAmount1MonthAgo = false;
        $found1MonthAgoRow = false;

        foreach ($history as $item) {
            ++$historyIndex;
            $date = $item['_year'] . $item['_month'];
            $isPositive = (float) $item['_sumPositiveAmount'] > 0;

            if ($date === $ago1Month) {
                $found1MonthAgoRow = true;
                $isPositiveAmount1MonthAgo = $isPositive;

                if ($isPositive) {
                    return false;
                }

                continue;
            }

            if ($isPositive && $date === $ago2Month
                && (1 === $historyIndex || !$isPositiveAmount1MonthAgo || !$found1MonthAgoRow)
            ) {
                return ['date' => new \DateTime('-1 month')];
            }
        }

        return false;
    }

    private function notifyUser($account, \DateTime $date)
    {
        /** @var Usr $user */
        $user = $this->userRepository->findBy([
            'userid' => $account['UserID'],
            // 'accountlevel' => ACCOUNT_LEVEL_AWPLUS,
        ]);

        if (empty($user)) {
            return false;
        }

        $login = $this->connection->fetchColumn('SELECT Val FROM AccountProperty WHERE AccountID = ' . $account['AccountID'] . ' AND ProviderPropertyID = 926 AND SubAccountID IS NULL LIMIT 1');
        empty($login) ? $login = $account['Login'] : null;

        $mailer = $this->mailer;
        $template = new LufthansaExpiration($user[0]);
        $template->account = $account;
        $template->login = 'XXXX-' . substr($login, -4);
        $template->date = $date;
        $message = $mailer->getMessageByTemplate($template);
        $mailer->send($message);

        return true;
    }

    private function test(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $currentMonth = date('Y-n');
        $ago1Month = date('Y-n', strtotime('-1 month'));
        $ago2Month = date('Y-n', strtotime('-2 month'));

        $testData = [
            // Есть данные только по ТЕКУЩЕМУ месяцу и они положительные - пропускаем
            [
                'notify' => false,
                [
                    '_month' => explode('-', $currentMonth)[1],
                    '_year' => explode('-', $currentMonth)[0],
                    '_sumPositiveAmount' => 1,
                ],
            ],
            // Есть данные только по ПРОШЛОМУ месяцу и они положительные - пропускаем
            [
                'notify' => false,
                [
                    '_month' => explode('-', $ago1Month)[1],
                    '_year' => explode('-', $ago1Month)[0],
                    '_sumPositiveAmount' => 1,
                ],
            ],
            // Есть данные только по ПОЗАПРОШЛОМУ месяцу - пропускаем
            [
                'notify' => false,
                [
                    '_month' => explode('-', $ago2Month)[1],
                    '_year' => explode('-', $ago2Month)[0],
                    '_sumPositiveAmount' => 0,
                ],
            ],
            // В текущем небыло транзакций, но есть в предыдущем - пропускаем
            [
                'notify' => false,
                [
                    '_month' => explode('-', $currentMonth)[1],
                    '_year' => explode('-', $currentMonth)[0],
                    '_sumPositiveAmount' => 0,
                ],
                [
                    '_month' => explode('-', $ago1Month)[1],
                    '_year' => explode('-', $ago1Month)[0],
                    '_sumPositiveAmount' => 1,
                ],
            ],
            // Есть данные только о позапрошлом месяце и по ним есть транзакции - УВЕДОМЛЯЕМ
            [
                'notify' => true,
                [
                    '_month' => explode('-', $ago2Month)[1],
                    '_year' => explode('-', $ago2Month)[0],
                    '_sumPositiveAmount' => 1,
                ],
            ],
            // В прошлом небыло положительных но в позапрошом есть - УВЕДОМЛЯЕМ
            [
                'notify' => true,
                [
                    '_month' => explode('-', $ago1Month)[1],
                    '_year' => explode('-', $ago1Month)[0],
                    '_sumPositiveAmount' => 0,
                ],
                [
                    '_month' => explode('-', $ago2Month)[1],
                    '_year' => explode('-', $ago2Month)[0],
                    '_sumPositiveAmount' => 1,
                ],
            ],
            // Наличие в даных текущего месяца и логика наличия положительных транзакций в позапрошлом месяце
            [
                'notify' => true,
                [
                    '_month' => explode('-', $currentMonth)[1],
                    '_year' => explode('-', $currentMonth)[0],
                    '_sumPositiveAmount' => 0,
                ],
                [
                    '_month' => explode('-', $ago1Month)[1],
                    '_year' => explode('-', $ago1Month)[0],
                    '_sumPositiveAmount' => 0,
                ],
                [
                    '_month' => explode('-', $ago2Month)[1],
                    '_year' => explode('-', $ago2Month)[0],
                    '_sumPositiveAmount' => 1,
                ],
            ],
        ];

        $index = 0;

        foreach ($testData as $history) {
            $required = $history['notify'];
            unset($history['notify']);

            $result = $this->calc($history);
            $result = (is_array($result) && array_key_exists('date', $result)) ? true : false;

            if ($result === $required) {
                $io->success('dataset - ' . $index);
            } else {
                $io->error('dataset - ' . $index . ' - ERROR, need: ' . ($required ? 'true' : 'false'));
                $io->writeln(var_export($history, true));
            }

            ++$index;
        }
    }
}
