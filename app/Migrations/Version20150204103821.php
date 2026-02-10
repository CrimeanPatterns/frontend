<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

// Retail Benefits support

class Version20150204103821 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Usr ADD RbxPassword VARCHAR(60) DEFAULT NULL COMMENT 'Пароль аккаунта пользователя в RetailBenefits'");
        $this->addSql("ALTER TABLE Provider ADD RbxMerchantID INT(11) DEFAULT NULL COMMENT 'ID провайдера в RetailBenefits'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Usr DROP RbxPassword");
        $this->addSql("ALTER TABLE Provider DROP RbxMerchantID");
    }
}
