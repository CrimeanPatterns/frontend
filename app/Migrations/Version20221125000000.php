<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20221125000000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `BalanceWatch`
                ADD `SourceProgramRegion` VARCHAR(80) NULL DEFAULT NULL COMMENT 'Если Login2 провайдера - регион, запишем потенциальный регион/страну',
                ADD `TargetProgramRegion` VARCHAR(80) NULL DEFAULT NULL COMMENT 'Если Login2 провайдера - регион, запишем потенциальный регион/страну'             
        ");

        $this->addSql("
            ALTER TABLE `TransferStat`
                ADD `SourceProgramRegion` VARCHAR(80) NULL DEFAULT NULL COMMENT 'Регион/страна источник',
                ADD `TargetProgramRegion` VARCHAR(80) NULL DEFAULT NULL COMMENT 'Регион/страна назначение'
        ");
        $this->addSql('
            ALTER TABLE `TransferStat`
                DROP INDEX `SourceTargetTS_unique`,
                ADD UNIQUE `SourceTargetTS_unique` (`SourceProviderID`, `TargetProviderID`, `SourceProgramRegion`, `TargetProgramRegion`) 
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE `BalanceWatch`
                DROP `SourceProgramRegion`,
                DROP `TargetProgramRegion` 
        ');
        $this->addSql('
            ALTER TABLE `TransferStat`
                DROP `SourceProgramRegion`,
                DROP `TargetProgramRegion`
        ');
    }
}
