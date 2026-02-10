<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201014111111 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `ProviderMileValue` CHANGE `HotelPointValue` `AvgPointValue` DECIMAL(8,2) NULL DEFAULT NULL COMMENT 'Средневзвешенное значение'");
    }

    public function down(Schema $schema): void
    {
    }
}
