<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201006112149 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        /*
         * petrocanada - disable bad accounts
         *
         * DisableReason - DISABLE_REASON_PROVIDER_ERROR
         */
        $this->addSql("UPDATE Account SET DisableReason = 3, Disabled = 1, DisableDate = now()
                       WHERE ProviderID = 108
                       AND ErrorCode = 4
                       AND (
                            ErrorMessage LIKE 'Petro-Canada (Petro-Points) website is asking you to create a new password%'
                       )
                       AND Disabled = 0");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
