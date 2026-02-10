<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210205134055 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `Lounge`
              ADD UpdateTerminalDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата последнего обновления терминала или гейта' after Gate2
        ");

        $this->addSql('
            update `Lounge`
            set `UpdateTerminalDate` = `UpdateDate` 
            where `UpdateTerminalDate` is null');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `Lounge`
              DROP UpdateTerminalDate
        ");
    }
}
