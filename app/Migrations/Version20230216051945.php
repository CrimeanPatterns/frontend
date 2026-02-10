<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230216051945 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE RewardsPriceMileCost
            ADD COLUMN AwardSeasonID INT NULL COMMENT 'Сезон, в котором действует цена' AFTER AwardTypeID,
            ADD CONSTRAINT fk_AwardSeasonID FOREIGN KEY (AwardSeasonID) REFERENCES AwardSeason(AwardSeasonID) ON DELETE CASCADE,
            DROP INDEX RewardsPriceID,
            ADD UNIQUE KEY uk_RewardsPriceID (RewardsPriceID, MileCost, TicketClass, AwardTypeID, AwardSeasonID, RoundTrip)
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE RewardsPriceMileCost
            DROP INDEX uk_RewardsPriceID,
            ADD UNIQUE KEY RewardsPriceID (RewardsPriceID, MileCost, TicketClass, AwardTypeID, RoundTrip),
            DROP FOREIGN KEY fk_AwardSeasonID,
            DROP COLUMN AwardSeasonID
        ");
    }
}
