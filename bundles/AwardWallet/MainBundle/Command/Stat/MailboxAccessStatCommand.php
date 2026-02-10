<?php

namespace AwardWallet\MainBundle\Command\Stat;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\Scanner\UserMailboxCounter;
use Doctrine\DBAL\Connection;
use GeoIp2\Database\Reader;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MailboxAccessStatCommand extends Command
{
    protected static $defaultName = 'aw:stat:mailbox-access';

    private LoggerInterface $logger;

    private Connection $connection;

    private UserMailboxCounter $mailboxCounter;

    private Reader $geoIpCountry;

    public function __construct(
        LoggerInterface $logger,
        Connection $replicaUnbufferedConnection,
        UserMailboxCounter $mailboxCounter,
        Reader $geoIpCountry
    ) {
        parent::__construct();

        $this->logger = $logger;
        $this->connection = $replicaUnbufferedConnection;
        $this->mailboxCounter = $mailboxCounter;
        $this->geoIpCountry = $geoIpCountry;
    }

    protected function configure()
    {
        $this
            ->setDescription('Calculate mailbox access concent by country')
            ->addOption('start', 's', InputOption::VALUE_REQUIRED, 'Start date')
            ->addOption('end', 'd', InputOption::VALUE_REQUIRED, 'End date')
            ->addOption('year', 'y', InputOption::VALUE_REQUIRED, 'Year', date('Y') - 1)
            ->addOption('countries', 'c', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Countries', [
                'FR',
                'IT',
                'ES',
                'DE',
                'US',
                'FI',
                'IE',
                'IN',
            ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('start')) {
            $startDate = date_create($input->getOption('start'));
        }

        if ($input->getOption('end')) {
            $endDate = date_create($input->getOption('end'));
        }

        if (
            !isset($startDate)
            && !isset($endDate)
            && $input->getOption('year')
            && is_numeric($input->getOption('year'))
        ) {
            $year = $input->getOption('year');
            $startDate = date_create($year . '-01-01 00:00:00');
            $endDate = date_create($year . '-12-31 23:59:59');
        }

        if (!isset($startDate)) {
            $this->logger->error('start date is not set');

            return 1;
        }

        if (!isset($endDate)) {
            $this->logger->error('end date is not set');

            return 1;
        }

        if ($startDate === false) {
            $this->logger->error('start date is invalid');

            return 1;
        }

        if ($endDate === false) {
            $this->logger->error('end date is invalid');

            return 1;
        }

        if ($startDate > $endDate) {
            $this->logger->error('start date is greater than end date');

            return 1;
        }

        $countries = $input->getOption('countries');

        if ($countries) {
            $this->logger->info(sprintf('countries: %s', implode(', ', $countries)));
            $countries = array_map('strtoupper', $countries);
        } else {
            $this->logger->error('countries are not set');

            return 1;
        }

        $this->logger->info(sprintf('start date: %s, end date: %s', $startDate->format('Y-m-d'), $endDate->format('Y-m-d')));

        $startTime = microtime(true);
        $stmt = $this->connection->executeQuery("
            SELECT
                UserID,
                RegistrationIP
            FROM
                Usr
            WHERE
                CreationDateTime >= :startDate
                AND CreationDateTime <= :endDate
                AND RegistrationIP IS NOT NULL
            ORDER BY UserID
        ", [
            'startDate' => $startDate->format('Y-m-d H:i:s'),
            'endDate' => $endDate->format('Y-m-d H:i:s'),
        ]);

        $stats = [];
        $rows = 0;

        while ($row = $stmt->fetchAssociative()) {
            $rows++;

            try {
                $country = $this->geoIpCountry->country($row['RegistrationIP']);
                $countryCode = $country->country->isoCode;
                $countryName = $country->country->name;

                if (empty($countryCode) || !in_array(strtoupper($countryCode), $countries)) {
                    continue;
                }

                $countryCode = strtoupper($countryCode);

                if (!isset($stats[$countryCode])) {
                    $stats[$countryCode] = [
                        'name' => $countryName ?? $countryCode,
                        'total' => 0,
                        'hasMailbox' => 0,
                    ];
                }

                $stats[$countryCode]['total']++;

                if ($this->mailboxCounter->total($row['UserID']) > 0) {
                    $stats[$countryCode]['hasMailbox']++;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        $endTime = microtime(true);
        $totalTime = round($endTime - $startTime, 2);

        $this->logger->info(sprintf('aw:stat:mailbox-access: done in %s seconds, rows: %d', $totalTime, $rows), [
            'stats' => array_map(function ($stat) {
                return [
                    'country' => $stat['name'],
                    'total' => $stat['total'],
                    'hasMailbox' => sprintf('%s (%s%%)', $stat['hasMailbox'], round($stat['hasMailbox'] / $stat['total'] * 100, 2)),
                ];
            }, $stats),
        ]);

        ksort($stats);

        if (count($stats) === 0) {
            $this->logger->warning('no data');
        } else {
            $io = new SymfonyStyle($input, $output);
            $io->table(['Country', 'Registered', 'Has mailbox'], array_map(function ($stat) {
                return [
                    $stat['name'],
                    $stat['total'],
                    sprintf('%s (%s%%)', $stat['hasMailbox'], round($stat['hasMailbox'] / $stat['total'] * 100, 2)),
                ];
            }, $stats));
        }

        return 0;
    }
}
