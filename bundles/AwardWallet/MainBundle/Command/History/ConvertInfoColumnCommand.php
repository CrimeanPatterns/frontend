<?php

namespace AwardWallet\MainBundle\Command\History;

use AwardWallet\Common\Doctrine\BatchUpdater;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Repositories\ParameterRepository;
use AwardWallet\MainBundle\Entity\Repositories\ProviderRepository;
use AwardWallet\MainBundle\Globals\Updater\Engine\UpdaterEngineInterface;
use AwardWallet\MainBundle\Loyalty\Resources\HistoryColumn;
use AwardWallet\MainBundle\Service\ProgressLogger;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ConvertInfoColumnCommand extends Command
{
    /** @var LoggerInterface */
    private $logger;
    /** @var Connection */
    private $mainConnection;
    /** @var Connection */
    private $replicaConnection;
    /** @var ParameterRepository */
    private $paramRepository;
    /** @var ProviderRepository */
    private $providerRepository;
    /** @var ProgressLogger */
    private $progressLogger;
    /** @var UpdaterEngineInterface */
    private $engine;

    public function __construct(
        LoggerInterface $logger,
        Connection $mainConnection,
        Connection $replicaConnection,
        UpdaterEngineInterface $engine,
        ParameterRepository $paramRepository,
        ProviderRepository $providerRepository
    ) {
        $this->logger = $logger;
        $this->mainConnection = $mainConnection;
        $this->replicaConnection = $replicaConnection;
        $this->engine = $engine;
        $this->paramRepository = $paramRepository;
        $this->providerRepository = $providerRepository;
        $this->progressLogger = new ProgressLogger($this->logger, 100, 5);
        parent::__construct();
    }

    protected function configure()
    {
        $this->addOption('provider', null, InputOption::VALUE_REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $providerCode = $input->getOption('provider');
        $provider = $this->providerRepository->findOneBy(['code' => $providerCode]);

        if (!$provider instanceof Provider) {
            $this->logger->notice("Unknown provider");

            exit(1);
        }

        $providerInfo = $this->engine->getProviderInfo($provider->getCode());
        $columns = $providerInfo->getHistorycolumns();
        $batchUpdater = new BatchUpdater($this->mainConnection);

        $updateSql = "
            UPDATE AccountHistory SET Info = ? 
            WHERE UUID = ?
        ";

        $sql = "
            SELECT h.UUID, h.Info 
            FROM AccountHistory h 
            JOIN Account a ON a.AccountID = h.AccountID
            WHERE a.ProviderID = " . $provider->getProviderid();

        $this->logger->info("Select rows AccountHistory. Provider - " . $provider->getDisplayname());
        $updates = [];
        $query = $this->replicaConnection->executeQuery($sql);
        $count = 0;
        $updated = 0;

        while ($row = $query->fetch()) {
            $this->progressLogger->showProgress("AccountHistory rows processing", $count);
            $count++;

            $fields = @unserialize($row['Info']);

            if (!is_array($fields)) {
                continue;
            }

            try {
                $filteredFields = $this->filterValues($fields, $columns);
            } catch (\Exception $e) {
                $this->logger->warning('Exception: ' . $e->getMessage(),
                    ['UUID' => $row['UUID'], 'class' => get_class($e)]);

                continue;
            }
            $newInfo = @serialize($filteredFields);

            if ($newInfo === $row['Info']) {
                continue;
            }

            $updates[] = [$newInfo, $row['UUID']];
            $batchUpdater->batchUpdate($updates, $updateSql, 50);
            $updated++;
        }

        $batchUpdater->batchUpdate($updates, $updateSql, 0);
        $this->logger->info("processed " . $count . " rows, updated: {$updated}");

        $convertedProvidersList = json_decode($this->paramRepository->getParam(ParameterRepository::CONVERTED_INFO_PROVIDERS_PARAM,
            "[]", true), true);

        if (!in_array($provider->getCode(), $convertedProvidersList)) {
            $convertedProvidersList[] = $provider->getCode();
            $this->paramRepository->setParam(ParameterRepository::CONVERTED_INFO_PROVIDERS_PARAM,
                json_encode($convertedProvidersList), true);
            $this->logger->info($provider->getDisplayname() . " added to converted providers list");
        }

        $this->logger->notice('Done!');

        return 0;
    }

    private function filterValues(array $fields, array $columns): array
    {
        $filtered = [];

        /** @var HistoryColumn $column */
        foreach ($columns as $column) {
            if (!in_array($column->getKind(),
                ["Info", "Bonus"]) || !isset($fields[$column->getName()]) || empty($fields[$column->getName()])) {
                continue;
            }

            $oldValue = $fields[$column->getName()];

            switch ($column->getType()) {
                case 'decimal':
                    $value = filterBalance($oldValue, true);

                    break;

                case 'integer':
                    $value = filterBalance($oldValue, false);

                    break;

                case 'date':
                    if ((int) $oldValue > 946684800) { // date > 2000/01/01
                        $date = (new \DateTime())->setTimestamp($oldValue);
                        $value = $date->format('Y-m-d');

                        break;
                    }

                    $oldValue = str_replace('.', '/', $oldValue);

                    if (preg_match('#^(\d{2})[\/|-]+(\d{2})[\/|-]+(\d{4})$#', $oldValue, $matches)) {
                        [$str, $d, $m, $y] = $matches;
                        $date = new \DateTime(sprintf('%s/%s/%s', $y, $m, $d));
                    } else {
                        $date = new \DateTime($oldValue);
                    }
                    $value = $date->format('Y-m-d');

                    break;

                default:
                    $value = $oldValue;
            }

            $filtered[$column->getName()] = $value;
        }

        return array_merge($fields, $filtered);
    }
}
