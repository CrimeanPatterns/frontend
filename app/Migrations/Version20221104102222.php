<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221104102222 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'national - disable bad accounts';
    }

    public function up(Schema $schema): void
    {
        // DisableReason - DISABLE_REASON_PREVENT_LOCKOUT
        $this->addSql("UPDATE Account SET DisableReason = 2, Disabled = 1, DisableDate = now()
                       WHERE ProviderID = 47
                       AND ErrorCode = 2
                       AND (
                           ErrorMessage LIKE 'The password reset link has expired. Please reset again.'
                           OR ErrorMessage LIKE '%s something wrong with your email, member number or password.%'
                       )
                       AND Disabled = 0");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
