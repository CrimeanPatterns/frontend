<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160429052712 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `AbBookerInfo` ADD `IncludeCreditCardFee` TINYINT  UNSIGNED  NOT NULL  DEFAULT '1'  COMMENT 'Добавлять к сумме инвойса налог 2.9% или нет (при оплате кредиткой)'  AFTER `CreditCardPaymentType`");
        $this->addSql("UPDATE `AbBookerInfo` SET `IncludeCreditCardFee` = 0 WHERE `UserID` = 221732"); // abroaders
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `AbBookerInfo` DROP `IncludeCreditCardFee`");
    }
}
