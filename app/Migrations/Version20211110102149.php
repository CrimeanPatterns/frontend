<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211110102149 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            UPDATE Usr SET DefaultBookerID = NULL, OwnedByBusinessID = NULL 
            WHERE DefaultBookerID IN (221732, 116000, 240662, 273412, 327644, 465514, 403343) OR OwnedByBusinessID IN (221732, 116000, 240662, 273412, 327644, 465514, 403343);

            DELETE FROM AbShare
            WHERE BookerID = 116000;

            DELETE FROM AccountShare
            WHERE UserAgentID = (
                SELECT UserAgentID FROM UserAgent WHERE AgentID = 116000
            );

            DELETE FROM ProviderCouponShare
            WHERE UserAgentID = (
                SELECT UserAgentID FROM UserAgent WHERE AgentID = 116000
            );

            DELETE FROM TimelineShare
            WHERE UserAgentID = (
                SELECT UserAgentID FROM UserAgent WHERE AgentID = 116000
            );

            DELETE FROM TravelPlanShare
            WHERE UserAgentID = (
                SELECT UserAgentID FROM UserAgent WHERE AgentID = 116000
            );
        ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
