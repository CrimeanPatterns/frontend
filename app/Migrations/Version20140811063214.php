<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140811063214 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            UPDATE `AbBookerInfo` SET `PricingDetails` = '<p>Thank you for your interest in our booking service! First of all we wanted to make sure you understand what we do, so please read this carefully:</p><ul>\r\n						<li>The cost of this service is <strong>$150</strong> per person if BookYourAward can meet our mutually agreed upon award parameters.</li>\r\n						<li>The booking service is only intended for <strong>international Business</strong> and <strong>First Class</strong> itineraries.</li>\r\n					</ul>' WHERE `AbBookerInfoID` = '10';
        ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
