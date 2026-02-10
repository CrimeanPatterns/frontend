<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211118155422 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            DELETE FROM AccountShare
            WHERE UserAgentID IN (
                SELECT UserAgentID FROM UserAgent WHERE AgentID = 116000
            );

            DELETE FROM ProviderCouponShare
            WHERE UserAgentID IN (
                SELECT UserAgentID FROM UserAgent WHERE AgentID = 116000
            );

            DELETE FROM TimelineShare
            WHERE UserAgentID IN (
                SELECT UserAgentID FROM UserAgent WHERE AgentID = 116000
            );

            DELETE FROM TravelPlanShare
            WHERE UserAgentID IN (
                SELECT UserAgentID FROM UserAgent WHERE AgentID = 116000
            );
        ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
