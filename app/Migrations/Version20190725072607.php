<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190725072607 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql(
            "ALTER TABLE Cart ADD COLUMN AppleTransactionID bigint(15) DEFAULT NULL COMMENT 'Идентификатор транзакции Apple, который не меняется при восстановлении покупок' AFTER IncomeTransactionID"
        );
        $this->addSql('CREATE UNIQUE INDEX AppleTransactionID_uq ON Cart(AppleTransactionID)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX AppleTransactionID_uq ON Cart');
        $this->addSql("ALTER TABLE Cart DROP AppleTransactionID");
    }
}
