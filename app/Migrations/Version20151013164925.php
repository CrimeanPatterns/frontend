<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151013164925 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("ALTER TABLE ExtensionStat ADD COLUMN ErrorCode int(11) DEFAULT NULL COMMENT 'Код ошибки, возвращаемый при проверке аккаунта' AFTER `ErrorText`");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("ALTER TABLE ExtensionStat DROP COLUMN ErrorCode");
    }
}
