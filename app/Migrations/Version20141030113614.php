<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20141030113614 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table ExtensionStat add column Forget tinyint(1) not null default 0");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table ExtensionStat drop column Forget");
    }
}
