<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230321162642 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("ALTER TABLE `RAFlightStat` 
            MODIFY `FirstSeen` datetime COMMENT 'время когда впервые увидели перелет соответствующей авиалинии у провайдера online (RA)',
            MODIFY `LastSeen` datetime COMMENT 'время когда последний раз видели перелет соответствующей авиалинии у провайдера online (RA)',
            ADD COLUMN `FirstBook` datetime COMMENT 'время когда впервые увидели перелет соответствующей авиалинии у провайдера в MileValue',
            ADD COLUMN `LastBook` datetime COMMENT 'время когда последний раз видели перелет соответствующей авиалинии у провайдера в MileValue',
            ADD INDEX `FirstSeenIdx` (`FirstSeen`),
            ADD INDEX `LastSeenIdx` (`LastSeen`),
            ADD INDEX `FirstBookIdx` (`FirstBook`),
            ADD INDEX `LastBookIdx` (`LastBook`);");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("ALTER TABLE `RAFlightStat`
            DROP INDEX `FirstSeenIdx`,
            DROP INDEX `LastSeenIdx`,
            DROP INDEX `FirstBookIdx`,
            DROP INDEX `LastBookIdx`,
            DROP COLUMN `FirstBook`,
            DROP COLUMN `LastBook`
        ;");
    }
}
