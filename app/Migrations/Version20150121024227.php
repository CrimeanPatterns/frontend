<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20150121024227 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE OA2Client ADD AccessTokenLifetime INT NOT NULL DEFAULT 3600 COMMENT 'Время жизни выдаваемых токенов, секунд'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE OA2Client DROP AccessTokenLifetime");
    }
}
