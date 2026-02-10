<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240916080808 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `CreditCard` ADD `SuccessCheckDate` DATETIME NULL DEFAULT NULL COMMENT 'Дата последней успешной проверки ссылки DirectClickURL'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `CreditCard` DROP `SuccessCheckDate`");
    }
}
