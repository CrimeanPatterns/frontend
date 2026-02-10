<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240530082617 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('
            DELETE ash
            FROM AccountShare ash
                 JOIN UserAgent ua ON ua.UserAgentID = ash.UserAgentID
                 JOIN Account a ON a.AccountID = ash.AccountID
            WHERE a.UserID = ua.AgentID;
        ');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
