<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200423131313 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `Usr` ADD `UpgradeSkippedCount` TINYINT(3) NOT NULL DEFAULT '0' COMMENT 'Кол-во игнорирований попапа с предложением апгрейда до awplus' AFTER `LastSpendAnalysisEmailDate`");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `Usr` DROP `UpgradeSkippedCount`');
    }
}
