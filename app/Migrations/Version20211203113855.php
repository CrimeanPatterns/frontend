<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211203113855 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE Lounge ADD CONSTRAINT Lounge_CheckedByUserID FOREIGN KEY (CheckedBy) REFERENCES Usr(UserID) ON DELETE SET NULL ON UPDATE CASCADE;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE Lounge DROP FOREIGN KEY Lounge_CheckedByUserID;');
    }
}
