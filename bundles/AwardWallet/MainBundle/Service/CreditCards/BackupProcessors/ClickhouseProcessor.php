<?php

namespace AwardWallet\MainBundle\Service\CreditCards\BackupProcessors;

use AwardWallet\MainBundle\Service\Backup\BackupProcessorInterface;
use AwardWallet\MainBundle\Service\Backup\Csv;
use AwardWallet\MainBundle\Service\Backup\Model\ProcessorOptions;
use AwardWallet\MainBundle\Service\Backup\ProcessorInterestInterface;
use AwardWallet\MainBundle\Service\Backup\ProcessorOptionsInterface;
use AwardWallet\MainBundle\Service\CreditCards\CreditCardMatcher;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputOption;

class ClickhouseProcessor implements BackupProcessorInterface, ProcessorOptionsInterface
{
    private const CLICKHOUSE_TABLES = [
        'Account' => 'Account',
        'AccountHistory' => 'AccountHistory',
        'DetectedCards' => 'AccountProperty',
        'SubAccount' => 'SubAccount',
        'ShoppingCategory' => 'ShoppingCategory',
        'CreditCardShoppingCategoryGroup' => 'CreditCardShoppingCategoryGroup',
    ];

    private Connection $connection;
    private LoggerInterface $logger;
    private CreditCardMatcher $creditCardMatcher;
    private int $detectedCardSequence = 1;

    public function __construct(Connection $connection, LoggerInterface $logger, CreditCardMatcher $creditCardMatcher)
    {
        $this->connection = $connection;
        $this->logger = $logger;
        $this->creditCardMatcher = $creditCardMatcher;
    }

    public function register(ProcessorInterestInterface $processorInterest): void
    {
        $path = $processorInterest->getInput()->getOption('clickhouse-dump-path');

        if (!$processorInterest->isFullDump() || $path === null) {
            return;
        }

        if (!is_dir($path)) {
            throw new \Exception("Invalid option clickhouse-dump-path: " . $path);
        }

        if (substr($path, -1, 1) !== '/') {
            $path .= '/';
        }

        $cards = $this->connection->executeQuery(
            "SELECT CreditCardID, CobrandProviderID, HistoryPatterns 
             FROM CreditCard 
             WHERE CobrandProviderID IS NOT NULL 
             AND HistoryPatterns IS NOT NULL"
        );
        $patterns = [];

        foreach ($cards as $card) {
            $patterns[(int) $card['CobrandProviderID']][(int) $card['CreditCardID']] = $card['HistoryPatterns'];
        }

        foreach (self::CLICKHOUSE_TABLES as $clickhouseTable => $mysqlTable) {
            $exportFile = fopen(
                $path . $clickhouseTable . '.csv',
                "wb"
            );

            if ($exportFile === false) {
                throw new \Exception("Failed to open " . $path . $clickhouseTable . '.csv');
            }

            switch ($mysqlTable) {
                case 'AccountHistory':
                    $reportPath = $processorInterest->getInput()->getOption('cards-report-path');
                    $reportFile = fopen(
                        $reportPath . 'historyPatternsReport.csv',
                        "wb"
                    );
                    $this->logger->info('History patterns report created',
                        ['path' => $reportPath . 'historyPatternsReport.csv']);

                    Csv::fputcsv(
                        $reportFile,
                        ['CreditCardID', 'Pattern', 'Description', 'UserID'],
                        ','
                    );
                    $processorInterest->addOnExportRow($mysqlTable,
                        function (array $row) use ($exportFile, $patterns, $reportFile) {
                            $creditCardId = null;

                            if (isset($patterns[(int) $row['ProviderID']])) {
                                foreach ($patterns[(int) $row['ProviderID']] as $ccId => $pattern) {
                                    foreach (explode("\n", $pattern) as $patternItem) {
                                        if (false !== stripos($row['Description'], trim($patternItem))) {
                                            $creditCardId = (int) $ccId;
                                            Csv::fputcsv(
                                                $reportFile,
                                                [
                                                    $ccId,
                                                    trim($patternItem),
                                                    $row['Description'],
                                                    $row['UserID'],
                                                ],
                                                ','
                                            );

                                            break 2;
                                        }
                                    }
                                }
                            }

                            $cuttedRow = [
                                $row['UUID'],
                                $row['AccountID'],
                                $row['SubAccountID'],
                                $creditCardId,
                                $row['PostingDateShort'],
                                $row['Miles'],
                                $row['Amount'],
                                $row['Multiplier'],
                                $row['MerchantID'],
                                $row['ShoppingCategoryID'],
                            ];
                            Csv::fputcsv($exportFile, $cuttedRow, ',');

                            return $row;
                        })
                        ->addJoin($mysqlTable, 'left join Account on Account.AccountID = AccountHistory.AccountID')
                        ->addExtraColumns($mysqlTable, 'DATE_FORMAT(PostingDate, \'%Y-%m-%d\') AS PostingDateShort, Account.ProviderID')
                    ;

                    break;

                case 'Account':
                    $processorInterest->addOnExportRow($mysqlTable, function (array $row) use ($exportFile) {
                        $cuttedRow = [
                            $row['AccountID'],
                            $row['ProviderID'],
                            $row['UserID'],
                            $row['SuccessCheckDate'],
                        ];
                        Csv::fputcsv($exportFile, $cuttedRow, ',');

                        return $row;
                    });

                    break;

                case 'SubAccount':
                    $processorInterest->addOnExportRow($mysqlTable, function (array $row) use ($exportFile) {
                        $cuttedRow = [
                            $row['SubAccountID'],
                            $row['AccountID'],
                            $row['CreditCardID'],
                            str_replace(',', '.', $row['Code']),
                        ];
                        Csv::fputcsv($exportFile, $cuttedRow, ',');

                        return $row;
                    });

                    break;

                case 'AccountProperty':
                    $processorInterest->addOnExportRow($mysqlTable, function (array $row) use ($exportFile) {
                        if ($row['Code'] === 'DetectedCards') {
                            $cards = @unserialize($row['Val'], ['allowed_classes' => false]);

                            if (!is_array($cards)) {
                                $this->logger->warning("failed to unserialize AccountPropertyID {$row['AccountPropertyID']}");

                                return $row;
                            }

                            foreach ($cards as $card) {
                                $ccId = 0;

                                if ($card['DisplayName'] !== null) {
                                    $ccId = $this->creditCardMatcher->identify(
                                        $card['DisplayName'],
                                        (int) $row['ProviderID']
                                    ) ?? 0;
                                }
                                $cuttedRow = [
                                    $this->detectedCardSequence,
                                    (int) $row['AccountID'],
                                    $ccId > 0 ? $ccId : null,
                                    str_replace(',', '.', $card['Code']),
                                ];
                                Csv::fputcsv($exportFile, $cuttedRow, ',');
                                $this->detectedCardSequence++;
                            }
                        }

                        return $row;
                    });

                    break;

                case 'ShoppingCategory':
                    $processorInterest->addOnExportRow($mysqlTable, function (array $row) use ($exportFile) {
                        Csv::fputcsv(
                            $exportFile,
                            [$row['ShoppingCategoryID'], $row['ShoppingCategoryGroupID']],
                            ','
                        );

                        return $row;
                    });

                    break;

                case 'CreditCardShoppingCategoryGroup':
                    $processorInterest->addOnExportRow($mysqlTable,
                        function (array $row) use ($exportFile) {
                            Csv::fputcsv(
                                $exportFile,
                                [
                                    $row['CreditCardShoppingCategoryGroupID'],
                                    $row['CreditCardID'],
                                    $row['ShoppingCategoryGroupID'],
                                    $row['Multiplier'],
                                    $row['StartDate'],
                                    $row['EndDate'],
                                ],
                                ','
                            );

                            return $row;
                        });

                    break;

                default:
                    $processorInterest->addOnExportRow($mysqlTable, function (array $row) use ($exportFile) {
                        Csv::fputcsv($exportFile, $row, ',');

                        return $row;
                    });

                    break;
            }
        }
    }

    public function registerOptions(ProcessorOptions $options): void
    {
        $options
            ->addOption('clickhouse-dump-path', null, InputOption::VALUE_REQUIRED,
                'ended by /. Only with backupClean usage ')
            ->addOption('cards-report-path', null, InputOption::VALUE_REQUIRED, ' History patterns report path');
    }
}
