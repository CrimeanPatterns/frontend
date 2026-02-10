<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190513094623 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `SkyScannerDeals`
                ADD `OutboundLegs` TINYINT(2) NULL AFTER `OutboundDepartureDt`,
                ADD `InboundLegs` TINYINT(2) NULL AFTER `InboundDepartureDt`,
                ADD `Market` VARCHAR(5) NULL AFTER `Currency`;
	    ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `SkyScannerDeals`
                DROP COLUMN `OutboundLegs`,
                DROP COLUMN `InboundLegs`,
                DROP COLUMN `Market`;
	    ");
    }
}
