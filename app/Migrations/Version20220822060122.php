<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220822060122 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'petco - disable bad accounts';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        /*
         * petco - disable bad accounts
         *
         * DisableReason - DISABLE_REASON_PREVENT_LOCKOUT
         */
        $this->addSql("UPDATE Account SET DisableReason = 2, Disabled = 1, DisableDate = now()
                       WHERE ProviderID = 210
                       AND ErrorCode = 2
                       AND (ErrorMessage LIKE '%Your password has expired.%' or ErrorMessage = 'The password or email you entered is incorrect.')
                       AND Disabled = 0");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
