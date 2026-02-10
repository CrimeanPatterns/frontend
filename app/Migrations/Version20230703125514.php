<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use AwardWallet\Common\Doctrine\BatchUpdater;
use AwardWallet\MainBundle\FrameworkExtension\Migrations\ContainerAwareMigrationInterface;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use function AwardWallet\MainBundle\Globals\Utils\stmt\stmtAssoc;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230703125514 extends AbstractMigration implements ContainerAwareMigrationInterface
{

    use ContainerAwareTrait;

    /** @var LoggerInterface */
    private $logger;

    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->logger = $this->container->get('logger');

        // this up() migration is auto-generated, please modify it to your needs
        $unbuffConn = $this->container->get('doctrine.dbal.read_replica_unbuffered_connection');

        $batcher = new BatchUpdater($this->connection);
        $q = $unbuffConn->executeQuery(/** @lang MySQL */ "SELECT RAFlightID, MileCost, Route, CostPerHour, TravelTime FROM RAFlight");
        $sql = /** @lang MySQL */
            "UPDATE RAFlight SET CostPerHour = ? WHERE RAFlightID = ?";
        foreach (stmtAssoc($q)->chunk(10000) as $chunkRows) {
            foreach ($chunkRows as $row) {
                if ($row['CostPerHour']) {
                    continue;
                }
                $params[] = [
                    $this->calcCostPerHour($row['MileCost'], $row['Route'], $row['TravelTime']),
                    $row['RAFlightID'],
                ];
            }
            $this->logger->info('run update ' . count($params) . ' rows');
            $batcher->batchUpdate($params, $sql, 0);
        }
    }

    private function calcCostPerHour(string $mileCost, string $route, string $travelTime): int
    {
        $layoverTime = 0; // minutes
        if (strpos($route, "o:") != false) {
            $parts = explode('o:', $route);
            array_shift($parts);
            foreach ($parts as $part) {
                $d = null;
                $time = strstr($part, ',', true);
                if (strpos($time, 'd') !== false) {
                    [$d, $time] = explode('d', $time);
                }
                if (strpos($time, 'h') === false) {
                    [$h, $m] = ['0', $time];
                } else {
                    [$h, $m] = explode('h', $time);
                }
                $m = str_replace('m', '', $m);
                $h = empty($h) ? 0 : (int)$h;
                $m = empty($m) ? 0 : (int)$m;
                $layoverTime += $h * 60 + $m;
                if (isset($d)) {
                    $d = (int)$d;
                    $layoverTime += $d * 60 * 24;
                }
            }
        }
        if ($layoverTime > $travelTime) {
            return 0;
        }
        $flightTime = ($travelTime - $layoverTime) / 60; // hours

        return (int)round($mileCost / $flightTime);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }

}
