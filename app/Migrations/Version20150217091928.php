<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150217091928 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('alter table Provider add column CanRegisterAccount tinyint comment "Можем ли мы зарегистрировать новый аккаунт этого провайдера", add column CanBuyMiles tinyint comment "Можем ли мы покупать мили или поинты для аккаунтов этого провайдера"');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('alter table Provider drop column CanRegisterAccount, drop column CanBuyMiles');
    }
}
