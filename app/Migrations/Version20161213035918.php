<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161213035918 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table AccountHistory 
            add SubAccountID int comment 'Связь с субаккаунтом для формирования его истории',
            add foreign key fkSubAccount(SubAccountID) references SubAccount(SubAccountID) on delete cascade");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table AccountHistory drop foreign key fkSubAccount, drop column SubAccountID");
    }
}
