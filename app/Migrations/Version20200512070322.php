<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200512070322 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `CreditCardShoppingCategoryGroup` ADD `EndDate` date NULL DEFAULT NULL COMMENT 'Дата окончания расчетного периода' AFTER `StartDate`;
            ALTER TABLE `CreditCardMerchantGroup` ADD `EndDate` date NULL DEFAULT NULL COMMENT 'Дата окончания расчетного периода' AFTER `StartDate`;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE `CreditCardShoppingCategoryGroup` DROP `EndDate`;
            ALTER TABLE `CreditCardMerchantGroup` DROP `EndDate`;
        ');
    }
}
