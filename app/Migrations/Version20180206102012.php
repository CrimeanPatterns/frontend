<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180206102012 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Cart ADD CartAttrHash VARCHAR(40) DEFAULT NULL COMMENT 'Уникальный хэш, созданный на основе UserID, PaymentType и PayDate. Прим. только для ios платежей, когда похожие транзакции имеют разные transaction id.' AFTER Source");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE Cart DROP CartAttrHash');
    }
}
