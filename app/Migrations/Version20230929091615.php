<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230929091615 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE Provider CHANGE LoginMinSize LoginMinSize INT NOT NULL DEFAULT '3';
        ");
        $this->addSql("
            UPDATE Provider SET LoginMinSize = 3 WHERE LoginMinSize = 1;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            UPDATE Provider SET LoginMinSize = 1 WHERE LoginMinSize = 3;
        ");
        $this->addSql("
            ALTER TABLE Provider CHANGE LoginMinSize LoginMinSize INT NOT NULL DEFAULT '1';
        ");
    }
}
