<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130709191002 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
			delete idel.* from Invites idel join (
				select
					InvitesID,
					count(InviterID) as cnt,
					InviteeID,
					min(InviteDate) as FirstInviteDate,
					InviterID as FirstInviterID,
					group_concat(InviterID SEPARATOR ',')
				from Invites
				where
					Approved = 1 and
					InviteeID is not null
				group by InviteeID
				having cnt > 1
			) istats on
				idel.InviteeID = istats.InviteeID and
				idel.InvitesID <> istats.InvitesID
			where idel.Approved = 1 and
			idel.InviteeID is not null");

        $this->addSql('ALTER TABLE Invites ADD UNIQUE KEY uk_invitee (InviteeID)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE Invites DROP INDEX uk_invitee');
    }
}
