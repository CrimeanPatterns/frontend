<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250128155537 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'carlson - disable bad accounts';
    }

    public function up(Schema $schema): void
    {
        // DisableReason - DISABLE_REASON_PREVENT_LOCKOUT
        $this->addSql("UPDATE Account SET DisableReason = 2, Disabled = 1, DisableDate = now()
                       WHERE ProviderID = 383
                       AND ErrorCode = 2
                       AND ErrorMessage LIKE '%The password has expired.%'
                       AND Disabled = 0");
        // DisableReason - DISABLE_REASON_PROVIDER_ERROR
        $this->addSql("UPDATE Account SET DisableReason = 3, Disabled = 1, DisableDate = now()
                       WHERE ProviderID = 383
                       AND ErrorCode = 4
                       AND ErrorMessage LIKE '%You must activate your account before logging in.%'
                       AND Disabled = 0");
        // DisableReason - DISABLE_REASON_PREVENT_LOCKOUT
        $this->addSql("UPDATE Account SET DisableReason = 2, Disabled = 1, DisableDate = now()
                       WHERE ProviderID = 383
                       AND ErrorCode = 2
                       AND ErrorMessage LIKE '%The email address/Radisson Rewards number or the password is not correct.%'
                       AND Disabled = 0");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
