<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180724051617 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `CreditCard`
                ADD `IsBusiness` TINYINT  NOT NULL  DEFAULT '0'  AFTER `Name`,
                ADD `Description` MEDIUMTEXT  NULL  DEFAULT NULL  AFTER `ClickURL`;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `CreditCard` 
                DROP `IsBusiness`,
                DROP `Description`;
        ");
    }
}
