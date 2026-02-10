<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241009090909 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE `EmailCustomParam` (
                `EmailCustomParamID` int NOT NULL AUTO_INCREMENT,
                `Type` tinyint unsigned NOT NULL COMMENT 'EmailCustomParam::TYPE_',
                `EventDate` date DEFAULT NULL COMMENT 'Когда должно примениться событие',
                `Message` text COMMENT 'Произвольный текст с HTML',
                `UpdateDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`EmailCustomParamID`)
            ) ENGINE=InnoDB COMMENT='Дополнительные параметры для писем'
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE `EmailCustomParam`');
    }
}
