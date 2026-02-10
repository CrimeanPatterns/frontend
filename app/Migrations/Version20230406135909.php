<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use function Sodium\add;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20230406135909 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            create table UsrLastLogonPoint (
                UserID int not null comment 'ID пользователя из таблицы Usr',
                `Point` point srid 4326 not null comment 'Координаты от Last Logon IP',
                IsSet bool not null default true comment '1 — координатам в Point можно верить, 0 - координатам в Point верить нельзя. (в mysql 8.0.28 нельзя сделать spatial index на nullable поле, поэтому делаем так). Необходимо проверять IsSet при джойне с этой таблицей',
                Version bigint unsigned not null comment 'Версия строки в базе, нужно для поддержания eventual consistency данных и обновления из нескольких воркеров/потоков',
                primary key (UserID),
                spatial index (`Point`)
            ) comment 'Таблица с координатами последнего логина пользователя. Из-за особенностей/опасностей (блокировка запросов на 1 час при alter table) работы со spatial index в mysql, колонка LastLogonPoint вынесена из Usr в эту таблицу и в ней отсутствует foreign key на таблицу Usr (может вызвать замедление при удалении записей из Usr)'
        ");
        $this->addSql("
            create table UserIPPoint (
                UserIPID int not null comment 'ID записи из таблицы UserIP',
                `Point` point srid 4326 not null comment 'Координаты от User IP',
                IsSet bool not null default true comment '1 — координатам в Point можно верить, 0 - координатам в Point верить нельзя. (в mysql 8.0.28 нельзя сделать spatial index на nullable поле, поэтому делаем так). Необходимо проверять IsSet при джойне с этой таблицей',
                Version bigint unsigned not null comment 'Версия строки в базе, нужно для поддержания eventual consistency данных и обновления из нескольких воркеров/потоков',
                primary key (UserIPID),
                spatial index (`Point`)
            ) comment 'Таблица с координатами записей из UserIP. Из-за особенностей/опасностей (блокировка запросов на 1 час при alter table) работы со spatial index в mysql, колонка Point вынесена из UserIP в эту таблицу и в ней отсутствует foreign key на таблицу UserIP (может вызвать замедление при удалении записей из UserIP)'
        ");
        $this->addSql("
            create table Outbox (
                OutboxID bigint unsigned not null auto_increment comment 'ID сообщения/ивента',
                TypeID int unsigned not null comment 'Тип сообщения/ивента',
                Payload JSON not null comment 'payload сообщения/ивента',
                primary key (OutboxID)
            )
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('drop table Outbox');
        $this->addSql('drop table UserIPPoint');
        $this->addSql('drop table UsrLastLogonPoint');
    }
}
