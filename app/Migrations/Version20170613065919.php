<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170613065919 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ProviderCoupon MODIFY Pin INTEGER(11) unsigned');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ProviderCoupon MODIFY Pin SMALLINT(5) unsigned');
    }
}
