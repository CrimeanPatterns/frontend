<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220624111111 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `Hotel`
                ADD `GoogleUpdateDate` DATETIME NULL DEFAULT NULL
                    COMMENT 'Дата последнего обновления website,phones из гугла при получении GooglePlaceDetails',
                ADD `GoogleUpdateAttempts` TINYINT NOT NULL DEFAULT '0'
                    COMMENT 'Кол-во попыток обновления для лимита'
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE `Hotel`
                DROP `GoogleUpdateDate`,
                DROP `GoogleUpdateAttempts`
        ');
    }
}
