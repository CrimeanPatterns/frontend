<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190301064923 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            alter table UserTripTargeting
              add column ReportName varchar(11) not null default 'from_us',
              drop index idx_UserTripTargeting_UserID_DestinationAirport,
              add unique index idx_UserTripTargeting_ReportName_UserID_DestinationAirport(ReportName, UserID, DestinationAirport)
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            alter table UserTripTargeting
              drop index idx_UserTripTargeting_ReportName_UserID_DestinationAirport,
              add unique index idx_UserTripTargeting_UserID_DestinationAirport(UserID, DestinationAirport),
              drop column ReportName
        ");
    }
}
