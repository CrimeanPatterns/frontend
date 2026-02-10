<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140418070342 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Provider ADD AbAccounts INT  UNSIGNED  NOT NULL  DEFAULT '0'  COMMENT 'Кол-во аккаунтов провайдера в букинге'  AFTER Accounts;");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Provider DROP AbAccounts;");
    }
}
