<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160118130336 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table BusinessInfo add PaidUntilDate date comment 'Бизнес оплачен до этой даты, включительно'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table BusinessInfo drop PaidUntilDate");
    }
}
