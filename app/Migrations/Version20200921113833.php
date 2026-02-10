<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200921113833 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("create table AppleUserInfo(
            AppleUserInfoID int not null auto_increment,
            Sub varchar(250) not null comment 'Уникальный идентификатор apple',
            FirstName varchar(250) not null,
            LastName varchar(250) not null,
            CreateDate datetime default current_timestamp(),
            UpdateDate datetime default current_timestamp(),
            primary key (AppleUserInfoID),
            unique key (Sub)
        ) engine=InnoDb comment 'Кэшируем информацию о OAuth пользователях apple sign in, apple дает ее только в первый раз. На случай удаления пользователя и повторной регистрации, или ошибок регистрации (емэйл занят).'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("drop table AppleUserInfo");
    }
}
