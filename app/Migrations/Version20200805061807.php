<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200805061807 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        /*
         * jetairways - disable bad accounts
         *
         * DisableReason - DISABLE_REASON_PROVIDER_ERROR
         */
        $this->addSql("UPDATE Account SET DisableReason = 3, Disabled = 1, DisableDate = now()
                       WHERE ProviderID = 51
                       AND ErrorCode = 4
                       AND (ErrorMessage LIKE '%website is asking you to update your profile%' or ErrorMessage LIKE '%website is asking you to create a new password%')
                       AND Disabled = 0");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
