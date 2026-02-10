<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20131023155216 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE AbAccountProgram COLLATE = utf8_general_ci');
        $this->addSql('ALTER TABLE AbBookerInfo COLLATE = utf8_general_ci');
        $this->addSql('ALTER TABLE AbCustomProgram COLLATE = utf8_general_ci');
        $this->addSql('ALTER TABLE AbInvoice COLLATE = utf8_general_ci');
        $this->addSql('ALTER TABLE AbInvoiceMiles COLLATE = utf8_general_ci');
        $this->addSql('ALTER TABLE AbMessage COLLATE = utf8_general_ci');
        $this->addSql('ALTER TABLE AbPassenger COLLATE = utf8_general_ci');
        $this->addSql('ALTER TABLE AbRequest COLLATE = utf8_general_ci');
        $this->addSql('ALTER TABLE AbRequestRead COLLATE = utf8_general_ci');
        $this->addSql('ALTER TABLE AbSegment COLLATE = utf8_general_ci');
        $this->addSql('ALTER TABLE AbTransaction COLLATE = utf8_general_ci');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE AbAccountProgram COLLATE = utf8_unicode_ci');
        $this->addSql('ALTER TABLE AbBookerInfo COLLATE = utf8_unicode_ci');
        $this->addSql('ALTER TABLE AbCustomProgram COLLATE = utf8_unicode_ci');
        $this->addSql('ALTER TABLE AbInvoice COLLATE = utf8_unicode_ci');
        $this->addSql('ALTER TABLE AbInvoiceMiles COLLATE = utf8_unicode_ci');
        $this->addSql('ALTER TABLE AbMessage COLLATE = utf8_unicode_ci');
        $this->addSql('ALTER TABLE AbPassenger COLLATE = utf8_unicode_ci');
        $this->addSql('ALTER TABLE AbRequest COLLATE = utf8_unicode_ci');
        $this->addSql('ALTER TABLE AbRequestRead COLLATE = utf8_unicode_ci');
        $this->addSql('ALTER TABLE AbSegment COLLATE = utf8_unicode_ci');
        $this->addSql('ALTER TABLE AbTransaction COLLATE = utf8_unicode_ci');
    }
}
