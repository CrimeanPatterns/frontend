<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20150120160730 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Usr ADD DefaultBookerID INT NULL DEFAULT NULL COMMENT 'Booker по умолчанию'");
        $this->addSql("ALTER TABLE Usr ADD OwnedByBusinessID INT NULL DEFAULT NULL COMMENT 'Пользователь принадлежит бизнес-аккаунту (зарегистрировался по реф-ссылке)'");
        $this->addSql("ALTER TABLE Usr ADD OwnedByManagerID INT NULL DEFAULT NULL COMMENT 'Пользователь принадлежит менеджеры бизнес-аккаунта (зарегистрировался по реф-ссылке менеджера)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Usr DROP DefaultBookerID");
        $this->addSql("ALTER TABLE Usr DROP OwnedByBusinessID");
        $this->addSql("ALTER TABLE Usr DROP OwnedByManagerID");
    }
}
