<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200821160000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `Provider`
                ADD `MPValueCertifiedUserID` INT(11) NULL DEFAULT NULL COMMENT 'Mile/Point value кем было проверено в последний раз',
                ADD `MPValueCertifiedDate` DATETIME NULL DEFAULT NULL COMMENT 'Mile/Point value метка времени о последней проверке';
        ");
        $this->addSql("UPDATE `Provider` SET CreationDate = EnableDate WHERE CAST(`CreationDate` AS CHAR(19)) = '0000-00-00 00:00:00' AND `EnableDate` IS NOT NULL");
        $this->addSql("UPDATE `Provider` SET CreationDate = '2010-10-10 10:10:10' WHERE CAST(`CreationDate` AS CHAR(19)) = '0000-00-00 00:00:00' AND `EnableDate` IS NULL");
        $this->addSql('ALTER TABLE `Provider` ADD CONSTRAINT `MPValueCertifiedUserID` FOREIGN KEY (`MPValueCertifiedUserID`) REFERENCES `Usr`(`UserID`) ON DELETE SET NULL ON UPDATE CASCADE');

        $this->addSql('UPDATE `ProviderPhone` AS pp LEFT JOIN `Usr` AS u ON (u.`UserID` = pp.`CheckedBy`) SET pp.`CheckedBy` = NULL WHERE u.`UserID` IS NULL');
        $this->addSql('ALTER TABLE `ProviderPhone` ADD CONSTRAINT `ProviderPhone_CheckedByUserID` FOREIGN KEY (`CheckedBy`) REFERENCES `Usr`(`UserID`) ON DELETE SET NULL ON UPDATE CASCADE');
    }

    public function down(Schema $schema): void
    {
    }
}
