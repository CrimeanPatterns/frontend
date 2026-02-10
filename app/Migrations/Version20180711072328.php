<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180711072328 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
        ALTER TABLE `CreditCardShoppingCategory` 
            ADD `StartLimit` FLOAT  NULL  DEFAULT NULL COMMENT 'set a limit in USD after which earning additional bonnus points will begin',
            ADD `StopLimit` FLOAT  NULL  DEFAULT NULL COMMENT 'set a limit in USD after which earning bonnus points will stop';
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `CreditCardShoppingCategory` DROP `StartLimit`, DROP `StopLimit`;");
    }
}
