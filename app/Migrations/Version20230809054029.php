<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230809054029 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'alamo - disable bad accounts';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        /*
         * DisableReason - DISABLE_REASON_PREVENT_LOCKOUT
         */
        $this->addSql("UPDATE Account SET DisableReason = 2, Disabled = 1, DisableDate = now()
                       WHERE ProviderID = 170
                       AND ErrorCode = 2
                       AND ErrorMessage LIKE '%your password has expired. Please reset your password and try again.%'
                       AND Disabled = 0");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
