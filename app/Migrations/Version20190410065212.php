<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190410065212 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table BusinessInfo modify APIAllowIp text");
        $this->addSql("alter table UserAgent modify AccessLevel int comment 'Уровень доступа к аккаунту (1-4) соответствует глобальным константам *ACCESS_READ*, *ACCESS_WRITE*, *ACCESS_ADMIN*'");
        $this->addSql("alter table AbMessage modify CreateDate datetime not null default current_timestamp()");
        $this->addSql("alter table ProviderPhone modify PhoneFor tinyint not null default 1 comment 'see PHONE_FOR_ constants'");
        $this->addSql("alter table Trip modify ShareCode varchar(20) comment 'used for creating links to shared plans'");
        $this->addSql("alter table DoNotSend 
            modify AddTime datetime not null default current_timestamp() comment 'дота добавления записи',
            modify IP varchar(40)
        ");
        $this->addSql("alter table GeoTag modify UpdateDate datetime not null default current_timestamp()");
        $this->addSql("alter table Cart modify LastUsedDate datetime not null default current_timestamp()");
        $this->addSql("alter table CartItem modify Cnt int not null default 1");
        $this->addSql("alter table FlightInfo 
            modify CreateDate datetime not null default current_timestamp(),
            modify Schedule   text comment 'Расписание запросов'
        ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
