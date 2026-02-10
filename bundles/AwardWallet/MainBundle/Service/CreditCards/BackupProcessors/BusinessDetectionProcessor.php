<?php

namespace AwardWallet\MainBundle\Service\CreditCards\BackupProcessors;

use AwardWallet\MainBundle\Entity\Sitegroup;
use AwardWallet\MainBundle\Service\Backup\BackupProcessorInterface;
use AwardWallet\MainBundle\Service\Backup\ProcessorInterestInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Psr\Log\LoggerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class BusinessDetectionProcessor implements BackupProcessorInterface
{
    /**
     * @var array<int => bool>
     */
    private array $detectedBusinessUserIdsMap = [];
    private Connection $connection;
    private LoggerInterface $logger;

    public function __construct(Connection $connection, LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->logger = $logger;
    }

    public function register(ProcessorInterestInterface $processorInterest): void
    {
        if (!$processorInterest->isFullDump() || $processorInterest->getInput()->getOption('clickhouse-dump-path') === null) {
            return;
        }

        $processorInterest
            ->addOnExportRow('AccountHistory', function (array $row) {
                if (\stripos($row['Description'], 'business') !== false) {
                    $this->detectedBusinessUserIdsMap[$row['UserID']] = true;
                }

                return $row;
            })
            ->addExtraColumns('AccountHistory', 'Account.UserID')
            ->addExtraColumns('AccountProperty', 'Account.UserID')
            ->addOnExportRow('AccountProperty', function (array $row) {
                if ('DetectedCards' === $row['Code']) {
                    $cards = @\unserialize($row['Val'], ['allowed_classes' => false]);

                    if (\is_array($cards)) {
                        foreach ($cards as $card) {
                            if (
                                isset($card['DisplayName'])
                                && \stripos($card['DisplayName'], 'business') !== false
                            ) {
                                $this->detectedBusinessUserIdsMap[$row['UserID']] = true;

                                return $row;
                            }
                        }
                    }
                }

                return $row;
            })
            ->addPostProcessor([$this, "postProcess"])
        ;
    }

    public function postProcess(): void
    {
        if (count($this->detectedBusinessUserIdsMap) === 0) {
            return;
        }

        $groupId = (int) $this->connection->executeQuery(
            'select SiteGroupID from SiteGroup where GroupName = ?',
            [Sitegroup::GROUP_BUSINESS_DETECTED]
        )->fetchColumn();

        if (!$groupId) {
            throw new \RuntimeException('No group found!');
        }

        $existingUserIdsMap = \array_flip(
            $this->connection->executeQuery(
                'select UserID from GroupUserLink where SiteGroupID = ?',
                [$groupId]
            )->fetchAll(FetchMode::COLUMN)
        );

        // sanitizing empty userids
        unset($this->detectedBusinessUserIdsMap['']);
        unset($this->detectedBusinessUserIdsMap[0]);
        unset($this->detectedBusinessUserIdsMap[null]);

        $addUsersIdsMap = \array_diff_key($this->detectedBusinessUserIdsMap, $existingUserIdsMap);
        $removeUsersIdsMap = \array_diff_key($existingUserIdsMap, $this->detectedBusinessUserIdsMap);
        $this->logger->info('Business detection: existing - ' . \count($existingUserIdsMap) . ', remove - ' . \count($removeUsersIdsMap) . ', add - ' . \count($addUsersIdsMap));

        try {
            $this->connection->transactional(function () use ($removeUsersIdsMap, $addUsersIdsMap, $groupId) {
                foreach (
                    it($removeUsersIdsMap)
                    ->keys()
                    ->chunk(500) as $removeChunk
                ) {
                    $this->connection->executeUpdate(
                        'delete from GroupUserLink where SiteGroupID = ? and UserID in (?)',
                        [$groupId, $removeChunk],
                        [\PDO::PARAM_INT, Connection::PARAM_INT_ARRAY]
                    );
                }

                foreach (
                    it($addUsersIdsMap)
                    ->keys()
                    ->chunk(500) as $addChunk
                ) {
                    $this->connection->executeUpdate(
                        'insert into GroupUserLink (SiteGroupID, UserID) values ' .
                        it($addChunk)
                            ->map(function () { return '(?, ?)'; })
                            ->joinToString(', ') .
                        ' on duplicate key update UserID = values(UserID)',
                        it($addChunk)
                            ->flatMap(function ($userId) use ($groupId) { return [$groupId, $userId]; })
                            ->toArray()
                    );
                }

                $this->logger->info('Business detection: update\remove trx completed!');
            });
        } catch (\Throwable $e) {
            $this->logger->info('Business detection: update\remove trx failed, message: ' . $e->getMessage());
        }
    }
}
