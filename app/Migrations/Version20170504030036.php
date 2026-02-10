<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170504030036 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $schema->getTable('Provider')->dropColumn('RbxMerchantID');
        $schema->getTable('Provider')->dropColumn('RbxTargetHost');
        $schema->getTable('Usr')->dropColumn('RbxPassword');
        $schema->getTable('Usr')->dropColumn('RbxUserID');
        $schema->dropTable('RetailBenefitsCashback');
        $schema->dropTable('RetailBenefitsLink');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
