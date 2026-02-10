<?php

namespace AwardWallet\MainBundle\Command\Fix;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\FrameworkExtension\Command;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

class FixAccountCredentialsCommand extends Command
{
    protected static $defaultName = 'aw:fix-account-credentials';

    private LoggerInterface $logger;
    private Connection $connection;
    private Connection $unbufferedConnection;
    private EntityManagerInterface $entityManager;

    public function __construct(
        LoggerInterface $logger,
        Connection $connection,
        $replicaUnbufferedConnection,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct();
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->connection = $connection;
        $this->unbufferedConnection = $replicaUnbufferedConnection;
    }

    public function configure()
    {
        $this
            ->setDescription('Clearing special characters around the edges of the password')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'fix logins, password')
            ->addOption('backup', 'b', InputOption::VALUE_OPTIONAL, 'backup')
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'limit')
            ->addOption('accountId', 'id', InputOption::VALUE_OPTIONAL, 'only accountId');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $logger = $this->logger;
        $conn = $this->connection;
        $unbuffConn = $this->unbufferedConnection;
        $limit = (int) $input->getOption('limit');

        $backup = [];
        $backupFile = $input->getOption('backup');

        if (empty($backupFile)) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('Are you sure you want to continue without backup? [y n]', false);

            if (!$helper->ask($input, $output, $question)) {
                return 0;
            }
        }

        if (!empty($backupFile) && (file_exists($backupFile) && !is_writable($backupFile) || !is_writable(dirname($backupFile)))) {
            $io->error($backupFile . ' is not writeable');

            return 0;
        }

        $isForce = !empty($input->getOption('force'));
        $mask = ['⓪', '⓪', '①', '②', '③', '④', '⑤', '⑥', '⑦', '⑧', '⑨', '⑩', '⑪', '⑫', '⑬', '⑭', '⑮', '⑯', '⑰', '⑱', '⑲', '⑳', '➀', '➁', '➂', '➃', '➄', '➅', '➆', '➇', '➈', '➉', '⓿', '❶', '❷', '❸', '❹', '❺', '❻', '❼', '❽', '❾', '❿', '➊', '➋', '➌', '➍', '➎', '➏', '➐', '➑', '➒', '➓', '⓫', '⓬', '⓭', '⓮', '⓯', '⓰', '⓱', '⓲', '⓳', '⓴'];
        $entityNum = array_merge(\range(8192, 8207), \range(8234, 8239), \range(8287, 8303));

        $accountRep = $this->entityManager->getRepository(Account::class);

        $foundPass = $foundLogin = [];
        $forced = $processed = 0;

        $where = [];
        empty($onlyAccountId = $input->getOption('accountId')) ?: $where[] = 'AccountID IN (' . implode(',', array_map('intval', explode(',', $onlyAccountId))) . ')';
        $where[] = 'ErrorCode IN (' . implode(',', [ACCOUNT_INVALID_PASSWORD, ACCOUNT_LOCKOUT, ACCOUNT_PREVENT_LOCKOUT, ACCOUNT_WARNING, ACCOUNT_TIMEOUT, ACCOUNT_MISSING_PASSWORD]) . ')';
        // $where[] = "ErrorMessage IS NOT NULL AND ErrorMessage <> ''"; // only for first execute with ACCOUNT_CHECKED
        $accounts = $unbuffConn->executeQuery('SELECT AccountID, Login, Pass, Login2, Login3 FROM Account ' . (empty($where) ? '' : 'WHERE ' . implode(' AND ', $where)));

        while ($account = $accounts->fetch()) {
            if ($limit && $processed >= $limit) {
                break;
            }

            $updateData = [];
            $accountId = $account['AccountID'];

            $pass = $accountRep->decryptPassword($account['Pass']);
            $cleanPass = str_replace($mask, '', $pass);

            if (mb_strlen($pass) !== mb_strlen($cleanPass)) {
                $foundPass[] = $accountId;
                $backup[$accountId] = ['Pass' => $pass];

                if ($isForce) {
                    $updateData['Pass'] = $accountRep->cryptPassword($cleanPass);
                }
            }

            foreach (['Login', 'Login2', 'Login3'] as $field) {
                $value = $account[$field];

                if (empty($value)) {
                    continue;
                }

                $cleaned = str_replace($mask, '', $value);
                $cleaned = preg_replace('/[\x00-\x1F\x7F]/u', '', $cleaned);
                $cleaned = $this->unicode2Entity($cleaned, $entityNum);

                if (mb_strlen($cleaned) !== mb_strlen($value)) {
                    $foundLogin[] = $accountId;
                    isset($backup[$accountId]) ?: $backup[$accountId] = [];
                    $backup[$accountId][$field] = $value;

                    if ($isForce) {
                        $updateData[$field] = $cleaned;
                    }
                }
            }

            if (!empty($backup)) {
                if ($isForce && !empty($updateData)) {
                    $forced += $conn->update('Account', $updateData, ['AccountID' => $accountId]);
                }

                if (!empty($backupFile)) {
                    file_put_contents($backupFile, '$bak = array_merge($bak, ' . var_export($backup, true) . ');' . "\n", FILE_APPEND);
                    $backup = [];
                }
            }

            if (0 === (++$processed % 50000)) {
                $output->writeln('processed ' . $processed . ', found-pass: ' . \count($foundPass) . ', found-logins: ' . \count($foundLogin) . ' ...');
            }
        }

        empty($foundLogin) ?: $logger->info('fix account credentials - login', $foundLogin);
        empty($foundPass) ?: $logger->info('fix account credentials - password', $foundPass);

        $message = 'DONE, processed: ' . $processed . ', found-pass: ' . \count($foundPass) . ', found-logins: ' . \count($foundLogin) . ', forced: ' . $forced;
        $isForce ? $io->success($message) : $io->writeln($message);

        return 0;
    }

    private function unicode2Entity($string, $entityNum)
    {
        return preg_replace_callback("/([\340-\357])([\200-\277])([\200-\277])/", function ($matches) use ($entityNum) {
            $code = (\ord($matches[1]) - 224) * 4096 + (\ord($matches[2]) - 128) * 64 + (\ord($matches[3]) - 128);

            if (\in_array($code, $entityNum, true)) {
                return '';
            }

            return $matches[0];
        }, $string);
    }
}
