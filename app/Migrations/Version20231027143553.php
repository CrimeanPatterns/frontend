<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231027143553 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        return;
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(/** @lang MySQL */ "
            ALTER TABLE RAFlight
            ADD COLUMN `Partner` varchar(100) NOT NULL DEFAULT 'juicymiles' COMMENT 'для кого был поиск на reward availability' , 
            DROP INDEX `idxRequestID`,
            ADD INDEX `idxRequestIDPartner` (`RequestID`, `Partner`)
        ");
        // reset default value
        $this->addSql(/** @lang MySQL */ "
            ALTER TABLE RAFlight
            MODIFY COLUMN `Partner` varchar(100) NOT NULL COMMENT 'для кого был поиск на reward availability'
        ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(/** @lang MySQL */ "
            ALTER TABLE RAFlight
            DROP COLUMN `Partner`, 
            Add INDEX `idxRequestID` (`RequestID`),
            DROP INDEX `idxRequestIDPartner`
        ");
    }
}
