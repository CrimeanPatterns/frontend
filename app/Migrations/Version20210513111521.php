<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210513111521 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql(/** @lang MySQL */ "create table CardMatcherReport(
            CardMatcherReportID int not null auto_increment,
            ProviderID int not null,
            Name varchar(512) not null,
            Total int not null comment 'Сколько заматчили карт с таким названием',
            Undetected int not null comment 'Сколько из них не сматчилось на CreditCard',
            Detected int not null comment 'Сколько сматчилось на CreditCard',
            Rows json not null comment 'Строки данных, формат [{\"AccountID\": 1, \"ProviderID\": 2, \"DisplayName\": \"Visa 1234\", \"Name\": \"Visa XXX\", \"CreditCardID\": 3, \"Source\": \"SA:DC\" /* SubAccount / DetectedCard */}, ...]',
            primary key (CardMatcherReportID),
            foreign key (ProviderID) references Provider(ProviderID),
            unique key akName(ProviderID, Name)
        ) engine InnoDB comment 'Отчет по детекту карт, строится в BuildCardMatcherReportCommand'");
    }

    public function down(Schema $schema) : void
    {
        $this->addSql("drop table CardMatcherReport");
    }
}
