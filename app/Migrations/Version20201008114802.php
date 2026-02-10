<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201008114802 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("create table ProviderMileValue(
            ProviderMileValueID int not null auto_increment,
            ProviderID int not null,
            RegionalEconomyMileValue decimal(8,2) DEFAULT NULL COMMENT 'Ручное значение стоимости миль для региональных перелетов эконом класса',
            GlobalEconomyMileValue decimal(8,2) DEFAULT NULL COMMENT 'Ручное значение стоимости миль для интернациональных перелетов эконом класса',
            RegionalBusinessMileValue decimal(8,2) DEFAULT NULL COMMENT 'Ручное значение стоимости миль для региональных перелетов бизнес класса',
            GlobalBusinessMileValue decimal(8,2) DEFAULT NULL COMMENT 'Ручное значение стоимости миль для интернациональных перелетов бизнес класса',
            HotelPointValue decimal(8,2) DEFAULT NULL COMMENT 'Ручное значение стоимости поинтов для отелей',
            Status tinyint(1) NOT NULL DEFAULT '0' COMMENT 'EntityProviderMileValue::STATUS',
            CertifiedByUserID int(11) DEFAULT NULL COMMENT 'Mile/Point value кем было проверено в последний раз',
            CertificationDate datetime DEFAULT NULL COMMENT 'Mile/Point value метка времени о последней проверке',
            StartDate date comment 'С какой даты действует эта цена',
            EndDate date comment 'До какой даты действует эта цена',
            primary key (ProviderMileValueID),
            foreign key (ProviderID) references Provider(ProviderID) on delete cascade,
            key (ProviderID, StartDate),
            key (ProviderID, EndDate)
        ) engine=InnoDB comment 'введенная вручную цена миль, за разные периоды'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("drop table ProviderMileValue");
    }
}
