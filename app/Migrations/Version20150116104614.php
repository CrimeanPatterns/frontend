<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20150116104614 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
        CREATE TABLE DiffChange(
            DiffChangeID INT NOT NULL AUTO_INCREMENT,
            SourceID VARCHAR(40) NOT NULL COMMENT 'ID источника, например TS123 для TripSegment c ID=123',
            Property VARCHAR(40) NOT NULL COMMENT 'Измененное свойство, например DepDate',
            OldVal VARCHAR(250) COMMENT 'Старое значение, например 2013-03-31 13:00',
            NewVal VARCHAR(250) COMMENT 'Новое значение, например 2013-03-31 14:00',
            ChangeDate DATETIME NOT NULL COMMENT 'Дата изменения',
            ExpirationDate DATETIME NOT NULL COMMENT 'До какого времени хранить эту запись. Например DepDate + 3 месяца. Для чистки базы.',
            PRIMARY KEY(DiffChangeID),
            UNIQUE KEY(SourceID, Property, ChangeDate)
        ) ENGINE=InnoDB COMMENT 'История изменения свойств. Используется для показа измененных сегментов в таймлайне.'
        ");
        $this->addSql("ALTER TABLE TripSegment ADD ChangeDate DATETIME COMMENT 'Одно из свойств этого сегмента было изменено на сайте провайдера'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE DiffChange");
        $this->addSql("ALTER TABLE TripSegment DROP ChangeDate");
    }
}
