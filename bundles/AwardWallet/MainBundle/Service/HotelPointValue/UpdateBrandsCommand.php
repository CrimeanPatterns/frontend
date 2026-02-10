<?php

namespace AwardWallet\MainBundle\Service\HotelPointValue;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateBrandsCommand extends Command
{
    public static $defaultName = 'aw:detect-hotel-brands';

    private EntityManagerInterface $em;

    private LoggerInterface $logger;

    private BrandMatcher $brandMatcher;

    public function __construct(EntityManagerInterface $em, LoggerInterface $logger, BrandMatcher $brandMatcher)
    {
        parent::__construct();

        $this->em = $em;
        $this->logger = $logger;
        $this->brandMatcher = $brandMatcher;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Hotel brand detection')
            ->addOption('providerId', 'p', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'filter by providerId')
            ->addOption('hotelPointValueId', 'l', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'filter by hotelPointValueId')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'override of HotelPointValue.BrandID')
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'dry run');
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $logger = $this->logger;

        // filter by provider
        if ($providerIds = $input->getOption('providerId')) {
            $providerIds = array_map('intval', $providerIds);
            $logger->info(sprintf('filter by providerId: [%s]', implode(', ', $providerIds)));
        }

        // filter by hotelPointValue
        if ($hotelPointValueIds = $input->getOption('hotelPointValueId')) {
            $hotelPointValueIds = array_map('intval', $hotelPointValueIds);
            $logger->info(sprintf('filter by hotelPointValueId: [%s]', implode(', ', $hotelPointValueIds)));
        }

        $force = !empty($input->getOption('force'));
        $logger->info(sprintf('force: [%s]', $force ? 1 : 0));

        $dryRun = !empty($input->getOption('dry-run'));
        $logger->info(sprintf('dry run: [%s]', $dryRun ? 1 : 0));

        $connecton = $this->em->getConnection();
        $builder = $connecton->createQueryBuilder();
        $e = $builder->expr();

        $builder
            ->select('HotelPointValueID, ProviderID, HotelName')
            ->from('HotelPointValue');

        if (!$force) {
            $builder->andWhere($e->isNull('BrandID'));
        }

        if (count($providerIds) > 0) {
            $builder->andWhere($e->in('ProviderID', ':providerIds'));
            $builder->setParameter(':providerIds', $providerIds, Connection::PARAM_INT_ARRAY);
        }

        if (count($hotelPointValueIds) > 0) {
            $builder->andWhere($e->in('HotelPointValueID', ':hotelPointValueIds'));
            $builder->setParameter(':hotelPointValueIds', $hotelPointValueIds, Connection::PARAM_INT_ARRAY);
        }

        $selectStmt = $builder->execute();
        $processed = 0;

        while ($row = $selectStmt->fetch(\PDO::FETCH_ASSOC)) {
            $logger->info(sprintf('processing HotelPointValue #%d, %d, %s', $row['HotelPointValueID'], $row['ProviderID'], $row['HotelName']));

            if ($brand = $this->brandMatcher->match($row['HotelName'], $row['ProviderID'])) {
                $logger->info(sprintf('brand matched: %s (%d)', $brand->getName(), $brand->getId()));

                if ($dryRun) {
                    $logger->info('dry run, skip');
                } else {
                    $identifier = [
                        'HotelPointValueID' => $row['HotelPointValueID'],
                    ];

                    if (!$force) {
                        $identifier['BrandID'] = null;
                    }
                    $connecton->update('HotelPointValue', [
                        'BrandID' => $brand->getId(),
                    ], $identifier);
                }
            } elseif ($force) {
                if ($dryRun) {
                    $logger->info('dry run, skip');
                } else {
                    $logger->info('remove brand');
                    $connecton->update('HotelPointValue', [
                        'BrandID' => null,
                    ], ['HotelPointValueID' => $row['HotelPointValueID']]);
                }
            }

            if (($processed % 100) == 0) {
                $this->em->clear();
                $logger->info("processed {$processed} accounts, mem: " . Helper::formatMemory(memory_get_usage(true)));
            }
            $processed++;
        }

        $output->writeln(sprintf('processed %d, done.', $processed));
    }
}
