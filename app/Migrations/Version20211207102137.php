<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211207102137 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE Account ADD LastCheckPastItsDate DATETIME DEFAULT NULL COMMENT 'Дата последней проверки прошлых резерваций' AFTER LastCheckItDate;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE Account DROP LastCheckPastItsDate;
        ");
    }
}
