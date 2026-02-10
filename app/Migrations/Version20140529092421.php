<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140529092421 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE Usr ADD CameFromBookerID int unsigned null');
        $this->addSql('update Usr set CameFromBookerID=116000 where camefrom in (125, 141)');
    }

    public function down(Schema $schema): void
    {
        $schema->getTable('Usr')->dropColumn('CameFromBookerID');
    }
}
