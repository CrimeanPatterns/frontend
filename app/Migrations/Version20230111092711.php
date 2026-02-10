<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use AwardWallet\Common\Doctrine\BatchUpdater;
use AwardWallet\MainBundle\FrameworkExtension\Migrations\ContainerAwareMigrationInterface;
use AwardWallet\MainBundle\Globals\Geo;
use function AwardWallet\MainBundle\Globals\Utils\stmt\stmtAssoc;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230111092711 extends AbstractMigration implements ContainerAwareMigrationInterface
{
    use ContainerAwareTrait;

    private $airCode;
    private $stationCode;

    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $unbuffConn = $this->container->get('doctrine.dbal.read_replica_unbuffered_connection');

        $this->airCode = $this->getCodes('AirCode', $unbuffConn);
        $this->stationCode = $this->getCodes('StationCode', $unbuffConn);
        $batcher = new BatchUpdater($this->connection);
        $q = $unbuffConn->executeQuery("SELECT RAFlightID, FromAirport, ToAirport FROM RAFlight");
        $sql = "UPDATE RAFlight SET ODDistance = ? WHERE RAFlightID = ?";
        foreach (stmtAssoc($q)->chunk(10000) as $chunkRows) {
            foreach ($chunkRows as $row) {
                $params[] = [$this->calcDistance($row['FromAirport'], $row['ToAirport']), $row['RAFlightID']];
            }
            $batcher->batchUpdate($params, $sql, 0);
        }
    }

    private function getCodes($tableName, $unbuffConn): array
    {
        $codes = [];
        $rows = $unbuffConn->fetchAllAssociative("SELECT Lat, Lng, {$tableName} AS AirCode FROM {$tableName};");
        foreach ($rows as $row) {
            $codes[$row['AirCode']] = ['Lat' => $row['Lat'], 'Lng' => $row['Lng']];
        }
        return $codes;
    }

    private function calcDistance(string $depart, string $arrive)
    {
        $dep = $this->airCode[$depart] ?? $this->stationCode[$depart] ?? null;
        $arr = $this->airCode[$arrive] ?? $this->stationCode[$arrive] ?? null;

        if (!$dep || is_null($dep['Lat']) || is_null($dep['Lng'])) {
            $hasError = true;
        }
        if (!$arr || is_null($arr['Lat']) || is_null($arr['Lng'])) {
            $hasError = true;
        }
        if (isset($hasError)) {
            return 0;
        }

        return Geo::distance($dep['Lat'], $dep['Lng'], $arr['Lat'], $arr['Lng']);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
