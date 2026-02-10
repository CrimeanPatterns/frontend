<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230423114938 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(/** @lang MySQL */"
            ALTER TABLE `AirClassDictionary`
                ADD COLUMN `ProviderIDs` VARCHAR(250) DEFAULT NULL COMMENT 'В каких провайдерах c RA появлялся такой CabinClass';");

    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(/** @lang MySQL */"
            ALTER TABLE `AirClassDictionary`
                DROP COLUMN `ProviderIDs`;");

    }
}
