<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190702060735 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `Merchant` 
                CHANGE `IsManuallyCategory` `IgnorePatternsGrouping` TINYINT(4)  NOT NULL  DEFAULT '0' COMMENT 'Исключить паттерны из детекта по категории';
            UPDATE `Merchant` SET `IgnorePatternsGrouping` = 0;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `Merchant` 
                CHANGE `IgnorePatternsGrouping` `IsManuallyCategory` TINYINT(4)  NOT NULL  DEFAULT '0' COMMENT '';
        ");
    }
}
