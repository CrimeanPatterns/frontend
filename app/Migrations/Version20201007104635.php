<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201007104635 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE MobileDevice ADD Secret VARCHAR(64) DEFAULT NULL COMMENT 'Значение для подтверждения реаутентификации, которое устройство хранит в защищенном хранилище' AFTER Alias");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE MobileDevice DROP Secret");
    }
}
