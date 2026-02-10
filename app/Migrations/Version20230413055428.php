<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230413055428 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE Usr ADD CurrencyID INT UNSIGNED DEFAULT 3 COMMENT 'Валюта пользователя' AFTER Region,
                ADD CONSTRAINT fk_UserCurrencyID FOREIGN KEY (CurrencyID) REFERENCES Currency(CurrencyID) ON DELETE SET NULL
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Usr DROP FOREIGN KEY fk_UserCurrencyID, DROP COLUMN CurrencyID");
    }
}
