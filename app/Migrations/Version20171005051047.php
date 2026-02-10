<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171005051047 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table Usr add Fraud tinyint not null default 0 comment 'Злоумышленник. Перебирает через нас пароли.'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table Usr drop Fraud");
    }
}
