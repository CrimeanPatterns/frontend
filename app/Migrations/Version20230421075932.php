<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230421075932 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        // undo some last changes #
        $this->addSql(/** @lang MySQL */'
            DELETE FROM `AirClassDictionary` WHERE `ProviderID` IS NOT NULL;
            ALTER TABLE `AirClassDictionary` 
                ADD UNIQUE INDEX `akAirlineCodeSource` (`AirlineCode`, `Source`),
                DROP INDEX `akAirlineCodeSourceProviderID`,
                DROP COLUMN `ProviderID`
            ;
        ');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
