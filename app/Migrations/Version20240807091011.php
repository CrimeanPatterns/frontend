<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240807091011 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `CreditCard` ADD `QsAffiliate` TINYINT NOT NULL DEFAULT '0' COMMENT 'Тип для проверки direct ссылки карты'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `CreditCard` DROP `QsAffiliate`');
    }
}
