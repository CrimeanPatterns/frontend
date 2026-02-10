<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240710093703 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE Usr
                ADD COLUMN UsGreeting TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Показывать приветствие при входе в систему' AFTER InfusionsoftContactID
        ");

        $this->addSql('UPDATE Usr SET UsGreeting = 1 WHERE InfusionsoftContactID IS NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE Usr DROP UsGreeting');
    }
}
