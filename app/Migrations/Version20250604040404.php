<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250604040404 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `EmailCustomParam`
                ADD `Subject` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Тема письма' AFTER `EventDate`, 
                ADD `Preview` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Первый текст в письме для превью' AFTER `Subject`
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE `EmailCustomParam`
                DROP `Subject`,
                DROP `Preview` 
        ');
    }
}
