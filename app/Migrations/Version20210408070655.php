<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use AwardWallet\MainBundle\Globals\StringHandler;
use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use AwardWallet\Common\Doctrine\BatchUpdater;
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210408070655 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $updater = new BatchUpdater($this->connection);
        $q = $this->connection->executeQuery("select UserID from Usr");

        $total = it($q->fetchAll(FetchMode::ASSOCIATIVE))
            ->onNthMillis(10000, function ($time, $ticksCounter, $value, $key) {
                $this->write("processed " . number_format($ticksCounter, 0) . " records in " . number_format($time / 1000, 0) . " seconds..");
            })
            ->map(function (array $row) {
                $row["Secret"] = StringHandler::getRandomCode(32, true);

                return $row;
            })
            ->chunk(50)
            ->map(function (array $rows) use ($updater) {
                $updater->batchUpdate($rows, "update Usr set Secret = :Secret where UserID = :UserID", 0);

                return count($rows);
            })
            ->sum()
        ;

        $this->write("total users: $total");
    }

    public function down(Schema $schema): void
    {
    }
}
