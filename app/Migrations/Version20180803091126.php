<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180803091126 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `SubAccount`        ADD `LastStoreLocationUpdateDate` datetime default null comment 'Дата последнего обновления Store Locations'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table `SubAccount`        drop `LastStoreLocationUpdateDate`");
    }
}
