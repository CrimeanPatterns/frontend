<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180208055646 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table Account modify `AuthInfo` varchar(4000) DEFAULT NULL COMMENT 'обычно сериализованный в  json oauth2 токен, у провайдеров которые поддерживают oauth'");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
