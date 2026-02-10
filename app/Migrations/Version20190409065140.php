<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use AwardWallet\MainBundle\Entity\Repositories\ParameterRepository;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190409065140 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO Param (Name, Val) VALUE (?, ?)", [ParameterRepository::CLICKHOUSE_DB_VERSION, 1]);
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM Param WHERE Name = ?", [ParameterRepository::CLICKHOUSE_DB_VERSION]);
    }
}
