<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180208142934 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
//        $this->addSql('UPDATE Account SET DisableClientPasswordAccess = DisableAutologin WHERE DisableAutologin = 1');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
