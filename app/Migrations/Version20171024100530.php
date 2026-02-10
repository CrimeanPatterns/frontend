<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171024100530 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `ShoppingCategory`
            ADD `MatchingOrder` int(11) NOT NULL COMMENT 'Порядок для матчинга при заполнении поля ShoppingCategoryID в таблице AccountHistory'
        ");
        $this->addSql("UPDATE `ShoppingCategory` SET `MatchingOrder` = `ShoppingCategoryID` * 10");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `ShoppingCategory` DROP `MatchingOrder`");
    }
}
