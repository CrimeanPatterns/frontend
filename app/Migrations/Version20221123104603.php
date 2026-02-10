<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221123104603 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("ALTER TABLE Provider ADD COLUMN CanCheckRaHotel TINYINT(4) NOT NULL DEFAULT 0 COMMENT 'Возможность сбора отелей за поинты/мили';");
        $this->addSql("UPDATE Provider SET CanCheckRaHotel=1 WHERE Code = 'testprovider';
;");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("ALTER TABLE Provider DROP COLUMN CanCheckRaHotel;");
    }
}
