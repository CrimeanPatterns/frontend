<?php

namespace AwardWallet\MainBundle\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CheckSlaCommand extends Command
{
    // Constants moved from the original class
    private const R1 = 24;
    private const R2 = 48;
    private const R3 = 72;

    private const S0 = 100;
    private const UNK_ERRORS_COUNT = 30;
    protected static $defaultName = 'aw:check-sla';
    protected static $defaultDescription = 'Checks SLA status for providers and updates accordingly';

    private $connection;
    private $tier2;
    private $tier3;
    private Connection $replicaConnection;

    public function __construct(Connection $connection, Connection $replicaConnection)
    {
        $this->connection = $connection;
        parent::__construct();
        $this->replicaConnection = $replicaConnection;
    }

    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->setHelp('This command checks SLA status for providers, calculates metrics, and updates the database accordingly');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('SLA Check Process');

        $this->calcTiers();
        $io->section('Processing SLA checks');
        $this->processSlaChecks($io);
        $this->deleteOldSlaEvents();

        $io->success('SLA check completed successfully');

        return 0;
    }

    private function processSlaChecks(SymfonyStyle $io): void
    {
        $sql = $this->buildSlaQuery();

        $io->text('Query started at ' . date('Y-m-d H:i:s'));
        $stmt = $this->replicaConnection->executeQuery($sql);
        $io->text('Query ended at ' . date('Y-m-d H:i:s'));

        $position = 0;

        while ($row = $stmt->fetchAssociative()) {
            $newTier = $this->setTier($position);
            $event = 'variation';

            if (!empty($row['LastChecked']) && !empty($row['LastUnkErrors'])) {
                if (intval($row['LastChecked']) > 0) {
                    $newSeverity = round(intval($row['LastUnkErrors']) / intval($row['LastChecked']) * 100, 2);
                } else {
                    $newSeverity = 0;
                }
            } else {
                $newSeverity = 0;
            }

            $severityS = (empty($row['Severity'])) ? 100 : $row['Severity'];
            $newSeverityS = $this->setSeverity($newSeverity, intval($row['LastUnkErrors']));
            $eventSeverityS = empty($row['SlaEventSeverity']) ? 100 : $row['SlaEventSeverity'];

            $io->text(date('Y-m-d H:i:s') . " [{$row['Code']}] updated LastChecked:{$row['LastChecked']}, LastUE:{$row['LastUnkErrors']}");

            $newResponseTime = $this->calculateResponseTime($row, $severityS, $newSeverityS, $eventSeverityS, $event, $newTier, $io);

            $slaEventId = false;

            if ($severityS != $newSeverityS || ($event == 'late' && $row['SlaEventEvent'] != 'late')) {
                $slaEventId = $this->addSlaEvent(
                    $row['ProviderID'],
                    $severityS,
                    $newSeverityS,
                    intval($row['Tier']),
                    $newTier,
                    intval($row['LastChecked']),
                    intval($row['LastUnkErrors']),
                    $event
                );

                if ($event != 'start' && $event != 'up') {
                    $slaEventId = false;
                }
            }

            $this->updateSlaProvider($row['ProviderID'], $newTier, $newSeverityS, $newResponseTime, $slaEventId);
            $position++;
        }
    }

    private function calculateResponseTime(array $row, int $severityS, int $newSeverityS, int $eventSeverityS, string &$event, int $newTier, SymfonyStyle $io): ?int
    {
        $newResponseTime = null;

        if ($row['SlaEventEvent'] == 'assign') {
            if ($newSeverityS == self::S0) {
                $event = 'close';
                $io->text("\t response time has been closed");
            }
        } else {
            if ($newSeverityS < $severityS) {
                $io->text("\t changed severity from S$severityS to S$newSeverityS");

                if ($row['SlaEventEvent'] == 'start' || $row['SlaEventEvent'] == 'up') {
                    if ($newSeverityS < $eventSeverityS) {
                        $newResponseTime = self::setResponseTime($newTier, $newSeverityS);
                        $event = 'up';
                        $io->text("\t set new response time - " . $newResponseTime . " hours");
                    } else {
                        $newResponseTime = self::calcDiffTime(
                            $row['SlaEventTier'],
                            time(),
                            strtotime($row['SeverityDate']),
                            $eventSeverityS
                        );

                        if ($newResponseTime == 0) {
                            $event = 'late';
                            $io->text("\t response time has been ended (Late problem)");
                        } else {
                            $io->text("\t update response time to $newResponseTime");
                        }
                    }
                } elseif ($row['SlaEventEvent'] == 'close' || empty($row['SlaEventEvent'])) {
                    if ($severityS == self::S0) {
                        $event = 'start';
                        $newResponseTime = self::setResponseTime($newTier, $newSeverityS);
                        $io->text("\t start new response time to $newResponseTime");
                    } else {
                        $io->text("\t Lost event(close)");
                    }
                } else {
                    $newResponseTime = 0;
                }
            } else {
                if ($newSeverityS > $severityS) {
                    $io->text("\t changed severity from S$severityS to S$newSeverityS");
                }

                if ($newSeverityS == self::S0) {
                    $newResponseTime = null;

                    if ($newSeverityS != $severityS) {
                        $event = 'close';
                        $io->text("\t response time has been closed");
                    }
                } else {
                    if ($row['SlaEventEvent'] != 'late') {
                        $newResponseTime = self::calcDiffTime(
                            $row['SlaEventTier'],
                            time(),
                            strtotime($row['SeverityDate']),
                            $eventSeverityS
                        );

                        if ($newResponseTime == 0) {
                            $event = 'late';
                            $io->text("\t response time has been ended (Late problem)");
                        } else {
                            $io->text("\t update response time to $newResponseTime");
                        }
                    } else {
                        $newResponseTime = 0;
                    }
                }
            }
        }

        return $newResponseTime;
    }

    private function buildSlaQuery(): string
    {
        return "
        SELECT
            a.*,
            p.Code, p.Severity, p.ResponseTime, p.Tier,
            e.SeverityDate, e.SlaEventSeverity, e.SlaEventTier, e.SlaEventEvent
        FROM
            (SELECT
                        a.ProviderID,
                        COUNT(a.AccountID) AS Popularity,    
                        SUM(CASE WHEN a.ErrorCode = " . ACCOUNT_ENGINE_ERROR . " AND a.UpdateDate > DATE_SUB(NOW(), INTERVAL 4 HOUR) THEN 1 ELSE 0 END) LastUnkErrors,
                        SUM(CASE WHEN a.AccountID IS NOT NULL AND a.UpdateDate > DATE_SUB(NOW(), INTERVAL 4 HOUR) THEN 1 ELSE 0 END) LastChecked
                        
                    FROM
                        Account a
                    WHERE
                        a.UpdateDate > DATE_SUB(NOW(), INTERVAL 1 DAY)
                    GROUP BY
                        a.ProviderID
            ) AS a
            JOIN Provider p on a.ProviderID = p.ProviderID
            LEFT JOIN
            (
                SELECT 
                    sa.ProviderID, 
                    sa.EventDate AS SeverityDate,
                    sa.NewSeverity AS SlaEventSeverity,
                    sa.NewTier AS SlaEventTier,
                    sa.Event AS SlaEventEvent
                FROM SlaEvent sa
                JOIN 
                (
                    SELECT 
                        ProviderID, 
                        MAX(EventDate) edate
                    FROM SlaEvent
                    WHERE Event !='variation'
                    GROUP BY ProviderID
                ) tmpSla 
                ON (tmpSla.ProviderID = sa.ProviderID AND tmpSla.edate = sa.EventDate)
            ) e
            ON p.ProviderID = e.ProviderID            
        WHERE   
            p.State >= " . PROVIDER_ENABLED . "
            AND p.WSDL = 1
            AND p.Assignee IS NULL
        ORDER BY
            Popularity DESC
        ";
    }

    private static function calcDiffTime(int $tier, int $newTms, int $oldTms, int $severity): int
    {
        if (empty($tier)) {
            throw new \RuntimeException("Sla Event Tier is Empty");
        }

        $hours = ceil(self::setResponseTime($tier, $severity) - ($newTms - $oldTms) / 3600);

        return $hours > 0 ? $hours : 0;
    }

    private function updateSlaProvider(int $providerId, int $tier, int $severity, ?int $responseTime, $slaEventId): void
    {
        $severityValue = $severity == 100 ? 'NULL' : $severity;
        $responseTimeValue = ($responseTime === null) ? "NULL" : $responseTime;

        if ($responseTimeValue == 'NULL') {
            $slaEventId = 'NULL';
        }

        $sql = "
            UPDATE
                Provider
            SET
                Tier = :tier,
                Severity = " . $severityValue . ",
                ResponseTime = " . $responseTimeValue . "
                " . ((intval($slaEventId) > 0 || $slaEventId == 'NULL') ? ", RSlaEventID = " . $slaEventId : "") . "
            WHERE
                ProviderID = :providerId
        ";

        $this->connection->executeStatement($sql, [
            'tier' => $tier,
            'providerId' => $providerId,
        ]);
    }

    private function addSlaEvent(int $providerId, int $oldSeverity, int $newSeverity, int $oldTier, int $newTier, int $checked, int $errors, string $event): int
    {
        $sql = "
            INSERT INTO
                SlaEvent (ProviderID, EventDate, OldSeverity, NewSeverity, OldTier, NewTier, Checked, Errors, Event)
            VALUES (:providerId, NOW(), :oldSeverity, :newSeverity, :oldTier, :newTier, :checked, :errors, :event)
        ";

        $this->connection->executeStatement($sql, [
            'providerId' => $providerId,
            'oldSeverity' => $oldSeverity,
            'newSeverity' => $newSeverity,
            'oldTier' => $oldTier,
            'newTier' => $newTier,
            'checked' => $checked,
            'errors' => $errors,
            'event' => $event,
        ]);

        return (int) $this->connection->lastInsertId();
    }

    private function calcTiers(): void
    {
        $sql = "SELECT COUNT(*) as Cnt FROM Provider WHERE WSDL = 1 AND State >= " . PROVIDER_ENABLED;
        $count = $this->replicaConnection->fetchOne($sql);

        $this->tier2 = round($count * 0.2);
        $this->tier3 = round($count * 0.5);
    }

    private function setTier(int $position): int
    {
        $tier = 3;

        if ($position < $this->tier3) {
            $tier = 2;
        }

        if ($position < $this->tier2) {
            $tier = 1;
        }

        return $tier;
    }

    private function setSeverity(float $severity, $errors = false): int
    {
        $s = self::S0;

        if ($errors >= self::UNK_ERRORS_COUNT || !$errors) {
            if ($severity >= 3 && $severity < 10) {
                $s = 3;
            }

            if ($severity >= 10 && $severity < 50) {
                $s = 2;
            }

            if ($severity >= 50) {
                $s = 1;
            }
        }

        return $s;
    }

    private static function setResponseTime(int $tier, int $severity): ?int
    {
        $responseTime = null;

        switch ($tier) {
            case 1:
                switch ($severity) {
                    case 1:
                    case 2: $responseTime = self::R1;

                        break;

                    case 3: $responseTime = self::R2;

                        break;
                }

                break;

            case 2:
                switch ($severity) {
                    case 1: $responseTime = self::R1;

                        break;

                    case 2: $responseTime = self::R2;

                        break;

                    case 3: $responseTime = self::R3;

                        break;
                }

                break;

            case 3:
                switch ($severity) {
                    case 1: $responseTime = self::R2;

                        break;

                    case 2:
                    case 3: $responseTime = self::R3;

                        break;
                }

                break;
        }

        return $responseTime;
    }

    private function deleteOldSlaEvents(): void
    {
        $sql = "
        DELETE FROM
            SlaEvent
        WHERE
            EventDate < DATE_SUB(NOW(), INTERVAL 1 YEAR)
        ";

        $this->connection->executeStatement($sql);
    }
}
