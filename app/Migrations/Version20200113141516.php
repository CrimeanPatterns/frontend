<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200113141516 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
        ALTER TABLE `Provider`
            ADD `RegionalEconomyMileValue` DECIMAL(8,2) NULL DEFAULT NULL COMMENT 'Ручное значение стоимости миль для региональных перелетов эконом класса',
            ADD `GlobalEconomyMileValue` DECIMAL(8,2) NULL DEFAULT NULL COMMENT 'Ручное значение стоимости миль для интернациональных перелетов эконом класса',
            ADD `RegionalBusinessMileValue` DECIMAL(8,2) NULL DEFAULT NULL COMMENT 'Ручное значение стоимости миль для региональных перелетов бизнес класса',
            ADD `GlobalBusinessMileValue` DECIMAL(8,2) NULL DEFAULT NULL COMMENT 'Ручное значение стоимости миль для интернациональных перелетов бизнес класса',
            ADD `HotelPointValue` DECIMAL(8,2) NULL DEFAULT NULL COMMENT 'Ручное значение стоимости поинтов для отелей',
            ADD `MileValueStatus` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Entity\ProviderMileValue::STATUS';
        ");
    }

    public function down(Schema $schema): void
    {
    }
}
