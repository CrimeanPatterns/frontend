<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20141127124948 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table UserEmailInfo add column BirthDate date");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table UserEmailInfo drop column BirthDate");
    }
}
