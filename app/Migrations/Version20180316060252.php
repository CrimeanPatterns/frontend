<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180316060252 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE Provider MODIFY COLUMN Site varchar(80) NULL DEFAULT \'\' COMMENT \'главная страница сайта\n*Value*:\nhttp://www.united.com\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE Provider MODIFY COLUMN Site VARCHAR(80) NOT NULL DEFAULT \'\' COMMENT \'главная страница сайта\n*Value*:\nhttp://www.united.com\'');
    }
}
