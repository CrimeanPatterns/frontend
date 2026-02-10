<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220311111111 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `CreditCardShoppingCategoryGroup` ADD `ShowExpiredCategories` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Даже если EndDate в прошлом, запись будет попадать в Bonus API JSON'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `CreditCardShoppingCategoryGroup` DROP `ShowExpiredCategories`');
    }
}
