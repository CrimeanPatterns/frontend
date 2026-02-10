<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161121083507 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("UPDATE Account SET DisableReason = 3, Disabled = 1, DisableDate = now()
                       WHERE ProviderID = 20
                       AND ErrorCode = 4
                       AND ErrorMessage LIKE '%website is asking you to update your profile%'
                       AND Disabled = 0");
        $this->addSql("UPDATE Account SET DisableReason = 2, Disabled = 1, DisableDate = now()
                       WHERE ProviderID = 20
                       AND ErrorCode = 2
                       AND (ErrorMessage LIKE '%Username and password do not match%'
                           OR
                           ErrorMessage LIKE '%We cannot find an account with that Username or HawaiianMiles Number%'
                           OR
                           ErrorMessage LIKE '%Please enter a valid username%'
                           OR
                           ErrorMessage LIKE '%Username and password do not match%'
                           OR
                           ErrorMessage LIKE '%HawaiianMiles number does not match our records%')
                       AND Disabled = 0");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
