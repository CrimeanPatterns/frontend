<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;


final class Version20211108141414 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `QsCreditCard`
                ADD `ForeignTransactionFee` VARCHAR(1000) NULL DEFAULT NULL
                COMMENT 'ForeignTransactionFee собираемый в блоге в таблицу wp_aw_quinstreet_cards из фида'
                AFTER `Slug`
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `QsCreditCard` DROP `ForeignTransactionFee`');
    }
}
