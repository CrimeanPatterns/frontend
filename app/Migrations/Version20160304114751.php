<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160304114751 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table Account add AuthInfo varchar(2000) comment 'обычно сериализованный в  json oauth2 токен, у провайдеров которые поддерживают oauth'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table Account drop AuthInfo");
    }
}
