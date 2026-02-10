<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200413113000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $checkTable = $this->connection->fetchAll('SHOW COLUMNS FROM CreditCard');
        $columns = array_column($checkTable, 'Field');

        if (false === in_array('ExcludeCardsId', $columns)) {
            $this->addSql("ALTER TABLE `CreditCard` ADD `ExcludeCardsId` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Список ID карт, для исключения показа при обнаружении карты у пользователя' AFTER `VisibleOnLanding`");
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `CreditCard` DROP `ExcludeCardsId`');
    }
}
