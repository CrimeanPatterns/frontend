<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211123113520 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $adminUserId = $this->connection->executeQuery("SELECT AgentID FROM UserAgent WHERE ClientID = 116000 AND AccessLevel = 4")->fetchOne();

        if ($adminUserId === false) {
            return;
        }

        $ids = [];
        $q = $this->connection->executeQuery("SELECT UserAgentID FROM UserAgent WHERE AgentID = ? OR ClientID = ?", [$adminUserId, $adminUserId]);

        while ($id = $q->fetchOne()) {
            $ids[] = $id;
        }

        if (count($ids) === 0) {
            return;
        }

        $this->addSql("
            DELETE FROM UserAgent
            WHERE 
                (AgentID = 116000 OR ClientID = 116000)
                AND UserAgentID NOT IN (" . implode(', ', $ids) . ");
        ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
