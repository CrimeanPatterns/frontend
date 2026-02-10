<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180208140058 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        if ($schema->getTable('Provider')->hasColumn('iPhoneAutologin')) {
            $this->addSql('alter table `Provider` drop `iPhoneAutoLogin`');
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
