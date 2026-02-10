<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200910111102 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        /*
         * aarp - disable bad accounts
         *
         * DisableReason - DISABLE_REASON_PROVIDER_ERROR
         */
        $this->addSql("UPDATE Account SET DisableReason = 3, Disabled = 1, DisableDate = now()
                       WHERE ProviderID = 249
                       AND ErrorCode = 4
                       AND (
                            ErrorMessage LIKE '%website is asking you to update your profile%'
                            or ErrorMessage = 'Renew your expired membership'
                            or ErrorMessage = 'You are not a member of this loyalty program.'
                            or ErrorMessage = 'Reactivate your cancelled membership	'
                       )
                       AND Disabled = 0");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
