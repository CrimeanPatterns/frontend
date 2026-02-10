<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180830054622 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `ShoppingCategoryGroup` ADD `Priority` INT  NOT NULL  DEFAULT '1' AFTER `ClickURL`;");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `ShoppingCategoryGroup` DROP `Priority`;");
    }
}
