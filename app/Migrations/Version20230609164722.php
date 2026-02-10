<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230609164722 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        if (!$schema->getTable('RAFlight')->hasIndex('idxForAwPriceRep')) {
            $this->addSql(/** @lang MySQL */ "
            ALTER TABLE `RAFlight` ADD INDEX `idxForAwPriceRep` (`Provider`, `SearchDate`, `FromAirport`, `ToAirport`, `DaysBeforeDeparture`);
            ");
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
