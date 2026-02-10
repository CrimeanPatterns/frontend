<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210224103013 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE HotelBrand (
                HotelBrandID INT(11) NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор',
                Name VARCHAR(250) NOT NULL COMMENT 'Название бренда',
                ProviderID INT(11) NOT NULL COMMENT 'Привязка к провайдеру',
                Patterns MEDIUMTEXT NOT NULL COMMENT 'Регулярные выражения, каждое с новой строки, для определения бренда',
                PRIMARY KEY (HotelBrandID),
                CONSTRAINT hotelBrandFkProviderID FOREIGN KEY (ProviderID) REFERENCES Provider (ProviderID) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB COMMENT 'Бренды отелей';
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE HotelBrand");
    }
}
