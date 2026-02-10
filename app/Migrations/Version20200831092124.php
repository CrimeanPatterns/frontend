<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200831092124 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        /*
         * yes2you - disable bad accounts
         *
         * DisableReason - DISABLE_REASON_PREVENT_LOCKOUT
         */
        $this->addSql("UPDATE Account SET DisableReason = 2, Disabled = 1, DisableDate = now()
                       WHERE ProviderID = 1122
                       AND ErrorCode = 2
                       AND ErrorMessage = 'To sign in to your account, please reset your password.'
                       AND Disabled = 0");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
