<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171018121540 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table Usr
            add ChangePasswordDate datetime comment 'Дата последнего изменения пароля',
            add ChangePasswordMethod tinyint comment 'Способ последней смены пароля, смотри Usr::CHANGE_PASSWORD_METHOD_'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table Usr
            drop ChangePasswordDate,
            drop ChangePasswordMethod");
    }
}
