<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160209123838 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `Usr` MODIFY `Language` varchar(7) NOT NULL DEFAULT 'en' COMMENT 'Язык интерфейса'");

        $this->addSql("
            UPDATE Usr
            SET Language = 'zh-CHT'
            WHERE Language = 'zh-'
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `Usr` MODIFY `Language` varchar(3) NOT NULL DEFAULT 'en' COMMENT 'Язык интерфейса'");
    }
}
