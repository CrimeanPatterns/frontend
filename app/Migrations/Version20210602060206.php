<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210602060206 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql(
            "
            ALTER TABLE `QsTransaction`
                ADD `SubAccountFicoState` JSON NULL COMMENT 'Список *Fico субакканутов на момент подачи заявки(ClickDate)',
                ADD `CreditCardState` JSON NULL COMMENT 'Список налияия кредитных карт на момент подачи заявки(ClickDate)'
        ");
    }

    public function down(Schema $schema): void
    {
    }
}
