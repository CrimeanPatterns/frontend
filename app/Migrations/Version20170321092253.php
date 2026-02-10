<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170321092253 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("update AdTypeMail set TypeMail = 'reservation_new' where TypeMail = 'new_travel_reservation'");
        $this->addSql("update AdTypeMail set TypeMail = 'balance_expiration' where TypeMail = 'award_program_point_expiration_notice'");
        $this->addSql("update AdTypeMail set TypeMail = 'check_in' where TypeMail = 'online_checkin_reminder'");
        $this->addSql("update AdTypeMail set TypeMail = 'reservation_changed' where TypeMail = 'travel_plan_changed'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("update AdTypeMail set TypeMail = 'new_travel_reservation' where TypeMail = 'reservation_new'");
        $this->addSql("update AdTypeMail set TypeMail = 'award_program_point_expiration_notice' where TypeMail = 'balance_expiration'");
        $this->addSql("update AdTypeMail set TypeMail = 'online_checkin_reminder' where TypeMail = 'check_in'");
        $this->addSql("update AdTypeMail set TypeMail = 'travel_plan_changed' where TypeMail = 'reservation_changed'");
    }
}
