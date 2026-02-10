<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190811113525 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `RewardsTransfer`
                ADD COLUMN `TransferDurationStat` int(11) DEFAULT NULL COMMENT 'Среднее время выполнение операций по данным таблицы Balance Watch',
                ADD COLUMN `PointsSource` TINYINT(1) DEFAULT NULL COMMENT 'Метод получения - transfer / purchase',
                ADD COLUMN `OperationsCount` int(11) DEFAULT NULL COMMENT 'Количество операций, по которым вычислялось среднее время передачи',
                ADD COLUMN `TimesStandartDeviation` int(11) DEFAULT NULL COMMENT 'Среднее отклонение время выполнение операций по данным таблицы Balance Watch'
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `RewardsTransfer`
                DROP `TransferDurationStat`,
                DROP `PointsSource`,
                DROP `OperationsCount`,
                DROP `TimesStandartDeviation`
        ");
    }
}
