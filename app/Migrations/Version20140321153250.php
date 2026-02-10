<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140321153250 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE
            Invites i
        JOIN Usr u ON
            i.InviteeID = u.UserID
        SET
            u.CameFrom = 4
        WHERE
            i.InviterID IS NOT NULL AND
            i.Approved = 1 AND
            u.CameFrom IS NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
