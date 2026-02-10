<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231029174250 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(/** @lang MySQL */ "
            ALTER TABLE RAFlightRouteSearchVolume
            ADD COLUMN `Excluded` INT DEFAULT 0 COMMENT 'increase by 1 when we found some result > 0, AND zero results were saved to RAFlight' AFTER `TimesSearched`, 
            ADD COLUMN `Saved` INT DEFAULT 0 COMMENT 'increase by 1 when at least 1 result found (>0) and >0 results qualified to be saved to RAFlight' AFTER `TimesSearched`;
            
            TRUNCATE RAFlightRouteSearchVolume;
        ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(/** @lang MySQL */ "
            ALTER TABLE RAFlightRouteSearchVolume
            DROP COLUMN `Excluded`, 
            DROP COLUMN `Saved`
        ");
    }
}
