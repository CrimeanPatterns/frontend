<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240715084259 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE Usr 
                DROP InfusionsoftContactID,
                DROP KEY InfusionsoftContactID
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE Usr 
                ADD InfusionsoftContactID BIGINT(20) UNSIGNED NULL COMMENT "Идентификатор контакта в Infusionsoft, необходим для синхронизации",
                ADD UNIQUE KEY InfusionsoftContactID (InfusionsoftContactID)
        ');
    }
}
