<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151118042834 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('delete from Invites where InviterID = InviteeID and InviteeID is not null and InviterID is not null');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
