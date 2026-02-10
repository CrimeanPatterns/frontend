<?php

namespace AwardWallet\MainBundle\Migrations;

use AwardWallet\MainBundle\Entity\Useremail;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140609125240 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("update UserEmail set Status = ? where Status = ?", [Useremail::STATUS_PROCESSING, 1], [\PDO::PARAM_INT, \PDO::PARAM_INT]);
        $this->addSql("alter table ScanHistory add column EmailSubject varchar(128)");
        $this->addSql("alter table UserEmail add column StartedDate datetime");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table ScanHisotry drop column EmailSubject");
        $this->addSql("alter table UserEmail drop column StartedDate");
    }
}
