<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170906044252 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        /*
         * ichotelsgroup - disable bad accounts
         *
         * DisableReason - DISABLE_REASON_PROVIDER_ERROR
         */
        $this->addSql("UPDATE Account SET DisableReason = 3, Disabled = 1, DisableDate = now()
                       WHERE ProviderID = 12
                       AND (ErrorCode = 4 or ErrorCode = 2)
                       AND ErrorMessage LIKE '%We have temporarily disabled your account due to a possible conflict with the IHG® Rewards Club Terms and Conditions%'
                       AND Disabled = 0");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
