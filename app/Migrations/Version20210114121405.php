<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\Common\Doctrine\BatchUpdater;
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210114121405 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        foreach (Itinerary::$table as $table) {
            $this->write("correcting null CreateDate");
            $count = $this->connection->executeUpdate("update {$table} set CreateDate = '2000-01-01' where CreateDate is null");
            $this->connection->commit();
            $this->connection->beginTransaction();
            $this->write("corrected $count rows");
            $this->write("correcting zero CreateDate");
            $count = $this->connection->executeUpdate("update {$table} set CreateDate = '2000-01-01' where CreateDate < '2001-01-01'");
            $this->write("corrected $count rows");
            $this->connection->commit();
            $this->connection->beginTransaction();
            $this->write("correcting FirstSeenDate");
            $q = $this->connection->executeQuery("select {$table}ID from {$table} where CreateDate <> FirstSeenDate");
            $ids = $q->fetchAll(FetchMode::COLUMN);
            $this->connection->commit();
            $this->connection->beginTransaction();
            $lastReportTime = microtime(true);
            $updater = new BatchUpdater($this->connection);
            $this->write("to correct: " . count($ids) . " records");

            while (count($ids) > 0) {
                if ((microtime(true) - $lastReportTime) > 30) {
                    $lastReportTime = microtime(true);
                    $this->write("updating FirstSeenDate, {$table}, to process: " . count($ids));
                }
                $slice = array_map(function (int $id) { return [$id]; }, array_splice($ids, 0, 5000));
                $updater->batchUpdate($slice, "update $table set FirstSeenDate = CreateDate where {$table}ID = ?", 0);
                $this->connection->commit();
                $this->connection->beginTransaction();
            }
            $this->write("done");
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
