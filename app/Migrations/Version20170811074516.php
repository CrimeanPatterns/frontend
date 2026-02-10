<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170811074516 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table `Provider` add column `LoginRequired` int(1) not null default 1 comment 'будет ли обязательным поле Login на форме добавления/редактировани аккаунта' after LoginCaption");

        $this->addSql("
            update Provider
            set
                LoginRequired = 0
            where 
                LoginCaption is null or
                LoginCaption = ''
        ");

        $this->addSql("
            update Provider
            set
                PasswordCaption = 'Password'
            where
                PasswordRequired = 1 and
                ( # preserve custom passowrd caption
                    PasswordCaption is null or 
                    PasswordCaption = ''
                )
        ");

        $this->addSql("
            update Provider
            set 
                PasswordRequired = 0,
                LoginRequired = 0
            where 
                    State = ?",
        [PROVIDER_RETAIL]
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('alter table `Provider` drop column `LoginRequired`');
    }
}
