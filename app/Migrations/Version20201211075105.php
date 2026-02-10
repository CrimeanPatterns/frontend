<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201211075105 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        /*
         * airnewzealand - disable bad accounts
         *
         * DisableReason - DISABLE_REASON_PREVENT_LOCKOUT
         */
        $this->addSql("UPDATE Account SET DisableReason = 2, Disabled = 1, DisableDate = now()
                       WHERE ProviderID = 258
                       AND ErrorCode = 2
                       AND ErrorMessage LIKE '%number/username or password do not match our records%'
                       AND Disabled = 0");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
