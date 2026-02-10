<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200421112610 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("create table SentEmail(
            ID binary(16) not null comment 'Уникальный UUID емэйла',
            SentDate datetime not null default current_timestamp(),
            Context json not null comment 'Контекст письма, смотри Mailer\Message::getContext',
            FirstClickDate datetime comment 'Дата первого клика по сообщению из емэйла',
            LastClickDate datetime comment 'Дата последнего клика по сообщению из емэйла',
            ClickCount tinyint not null default 0 comment 'Число кликов',
            FirstOpenDate datetime comment 'Дата первого открытия письма (загрузка лого)',
            LastOpenDate datetime comment 'Дата последнего открытия письма (загрузка лого)',
            OpenCount tinyint not null default 0 comment 'Число открытий письма',
            index idxSentDate(SentDate),
            primary key (ID)   
        ) engine InnoDB comment 'Отправленные емэйлы и клики по ним'");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
