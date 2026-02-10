<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use AwardWallet\Common\Doctrine\BatchUpdater;
use AwardWallet\MainBundle\FrameworkExtension\Migrations\ContainerAwareMigrationInterface;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use function AwardWallet\MainBundle\Globals\Utils\stmt\stmtAssoc;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230703093926 extends AbstractMigration implements ContainerAwareMigrationInterface
{

    use ContainerAwareTrait;

    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        return;
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
            $batcher->batchUpdate($params, $sql, 0);
        }

    }

    private function calcCostPerHour(string $mileCost, string $route, string $travelTime): int
    {
        $layoverTime = 0; // minutes
        if (preg_match_all("/o:((?:\d+h)?(?:\d+m)?),/", $route, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $res) {
                [$h, $m] = explode('h', $res[1]);
                $m = str_replace('m', '', $m);
                $h = empty($h) ? 0 : (int)$h;
                $m = empty($m) ? 0 : (int)$m;
                $layoverTime += $h * 60 + $m;
            }
        }
        if ($layoverTime > $travelTime) {
            return 0;
        }
        $flightTime = ($travelTime - $layoverTime) / 60; // hours

        return (int) round($mileCost / $flightTime);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }

}
