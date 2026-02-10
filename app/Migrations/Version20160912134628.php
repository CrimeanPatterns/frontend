<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160912134628 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $schema->dropTable('MegaDO7Promo');
    }

    public function down(Schema $schema): void
    {
    }
}
