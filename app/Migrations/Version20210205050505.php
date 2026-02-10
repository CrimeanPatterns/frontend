<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210205050505 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `MileValue` CHANGE `CustomAlternativeCost` `CustomAlternativeCost` DECIMAL(10,2) NOT NULL DEFAULT '0' COMMENT 'AlternativeCost edited by user'");
        $this->addSql(
            "
            ALTER TABLE `MileValue`
                ADD `CustomPick` TINYINT(1) NULL DEFAULT 0 COMMENT 'Entity/MileValue::CUSTOM_PICK_' AFTER `CustomAlternativeCost`,
                ADD `CustomMileValue` DECIMAL(10,2) NULL DEFAULT NULL AFTER `CustomPick`
        "
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            "
            ALTER TABLE `MileValue`
                DROP `CustomPick`,
                DROP `CustomMileValue`
        "
        );
    }
}
