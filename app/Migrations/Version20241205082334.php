<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241205082334 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("update Usr 
            set 
                EmailExpiration = 1, 
                EmailRewards = 1,
                EmailNewPlans = 1, 
                EmailPlansChanges = 1, 
                CheckinReminder = 1, 
                EmailProductUpdates = 1, 
                EmailOffers = 1, 
                EmailInviteeReg = 1, 
                EmailFamilyMemberAlert = 1 
            where 
                AccountLevel = " . ACCOUNT_LEVEL_FREE);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
