<?php

namespace AwardWallet\MainBundle\Command\Fix;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FixUnitedDupicateHistoryCommand extends Command
{
    protected static $defaultName = 'aw:fix-united-duplicate';

    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;

    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct();
        $this->logger = $logger;
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Remove duplicate rows with empty or zero values')
            ->addOption('accountId', null, InputOption::VALUE_REQUIRED, 'AccountID')
            ->addOption('remove', null, InputOption::VALUE_NONE, 'Remove duplicate entries');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $accountId = (int) $input->getOption('accountId');

        $historys = $this->entityManager->getConnection()->fetchAllAssociative('
            SELECT PostingDate, Description, Miles, Info, UUID
            FROM AccountHistory
            WHERE AccountID = ' . $accountId . '
            ORDER BY PostingDate DESC, Position ASC
        ');

        $uniq = [];
        $removeRows = [];

        foreach ($historys as $history) {
            $date = $history['PostingDate'];
            $desc = $history['Description'];
            $miles = $history['Miles'];
            $originInfo = unserialize($history['Info']);

            $info = [];

            foreach ($originInfo as $key => $value) {
                if (is_array($value)) {
                    throw new \Exception('Nested array');
                }

                $clean = trim($value, '0.-');
                $info[$key] = empty($clean)
                    ? ''
                    : $value;
            }

            $hash = sha1($date . $desc . $miles . json_encode($info));

            if (array_key_exists($hash, $uniq)) {
                $removeRows[] = $history;
            } else {
                $uniq[$hash] = $history;
            }
        }

        if ($input->getOption('remove')) {
            if (empty($removeRows)) {
                $output->writeln('Done. Nothing to delete');

                return 0;
            }

            $uid = (int) $this->entityManager->getConnection()->fetchOne('SELECT UserID FROM Account WHERE AccountID = ' . $accountId . ' LIMIT 1');

            foreach ($removeRows as $row) {
                $context = array_merge([
                    'UserID' => $uid,
                    'AccountID' => $accountId,
                ], $row);
                $this->logger->info('AccountHistory remove duplicate history transactions', $context);
            }

            $removePacks = array_chunk($removeRows, 50);

            foreach ($removePacks as $rows) {
                $this->entityManager->getConnection()->executeQuery('
                    DELETE FROM AccountHistory
                    WHERE
                            AccountID = :accountId
                        AND UUID IN (:uuids)
                ', [
                    'accountId' => $accountId,
                    'uuids' => array_column($rows, 'UUID'),
                ], [
                    'accountId' => \PDO::PARAM_INT,
                    'uuids' => Connection::PARAM_STR_ARRAY,
                ]);
            }

            $output->writeln('removed: ' . count($removeRows) . ' rows');
        } else {
            $output->writeln('found duplicate: ' . count($removeRows) . ' rows');
        }

        $output->writeln('done.');

        return 0;
    }
}
