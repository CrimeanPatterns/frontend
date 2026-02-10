<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220119094549 extends AbstractMigration
{
    public const UNDELETED_COMMENT = 'Восстановил ли удаленную резервацию юзер вручную. На тот случай, чтобы скрипт не 
        удалял вновь и вновь восстановленную резервацию по причине того, что парсер не вернул данную резервацию, 
        а в базе она есть. Если парсер вернул cancelled = true (или FlightStats), то она будет удалена несмотря на данное поле. Если юзер
        удаляет вручную резервацию, то поле обнуляется и автоматика может в определенных случаях восстановить сама (прим, FlightStats) и даже
        в дальнейшем снова удалить';

    public function up(Schema $schema): void
    {
        $comment = self::UNDELETED_COMMENT;
        $this->addSql("
            ALTER TABLE Parking ADD COLUMN Undeleted TINYINT(1) NOT NULL DEFAULT 0 COMMENT '$comment' AFTER Hidden;
            ALTER TABLE Rental ADD COLUMN Undeleted TINYINT(1) NOT NULL DEFAULT 0 COMMENT '$comment' AFTER Hidden;
            ALTER TABLE Reservation ADD COLUMN Undeleted TINYINT(1) NOT NULL DEFAULT 0 COMMENT '$comment' AFTER Hidden;
            ALTER TABLE Restaurant ADD COLUMN Undeleted TINYINT(1) NOT NULL DEFAULT 0 COMMENT '$comment' AFTER Hidden;
            ALTER TABLE Trip ADD COLUMN Undeleted TINYINT(1) NOT NULL DEFAULT 0 COMMENT '$comment' AFTER Hidden;
            ALTER TABLE TripSegment ADD COLUMN Undeleted TINYINT(1) NOT NULL DEFAULT 0 COMMENT '$comment' AFTER Hidden;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE Parking DROP COLUMN Undeleted;
            ALTER TABLE Rental DROP COLUMN Undeleted;
            ALTER TABLE Reservation DROP COLUMN Undeleted;
            ALTER TABLE Restaurant DROP COLUMN Undeleted;
            ALTER TABLE Trip DROP COLUMN Undeleted;
            ALTER TABLE TripSegment DROP COLUMN Undeleted;
        ');
    }
}
