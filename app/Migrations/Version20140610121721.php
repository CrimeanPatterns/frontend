<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20140610121721 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE AbRequest ADD CameFrom int unsigned null');
        $this->addSql('update AbRequest r set CameFrom = (select u.CameFrom from Usr u where u.UserID = r.UserID)');
    }

    public function down(Schema $schema): void
    {
        $schema->getTable('AbRequest')->dropColumn('CameFrom');
    }
}
