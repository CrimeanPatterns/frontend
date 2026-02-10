<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240909090909 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `CreditCard` ADD `RankIndex` SMALLINT UNSIGNED NULL DEFAULT NULL COMMENT 'Ранг для сортировки на странице blog/credit-cards' AFTER `SortIndex`");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `CreditCard` DROP `RankIndex`");
    }
}
