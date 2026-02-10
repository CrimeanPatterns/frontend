<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231221111555 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'qmiles - disable bad accounts';
    }

    public function up(Schema $schema): void
    {
        // DisableReason - DISABLE_REASON_PREVENT_LOCKOUT
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("UPDATE Account SET DisableReason = 2, Disabled = 1, DisableDate = now()
                       WHERE ProviderID = 83
                       AND ErrorCode = 2
                       AND ErrorMessage LIKE 'To sign in to your account, please reset your password.%'
                       AND Disabled = 0");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
