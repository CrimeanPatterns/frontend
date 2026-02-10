<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160121075017 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->connection->executeUpdate("update EmailNDRContent set MessageDate = now() where MessageDate is null or MessageDate < '2000-01-01'");
        $this->addSql("alter table EmailNDRContent modify MessageDate timestamp not null DEFAULT CURRENT_TIMESTAMP");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table EmailNDRContent modify MessageDate timestamp");
    }
}
