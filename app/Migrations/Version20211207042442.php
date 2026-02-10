<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211207042442 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'jetblue - disable invalid accounts';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        /*
         * DisableReason - DISABLE_REASON_PREVENT_LOCKOUT
         */
        $this->addSql("UPDATE Account SET DisableReason = 2, Disabled = 1, DisableDate = now()
                       WHERE ProviderID = 13
                       AND ErrorCode = 2
                       AND ErrorMessage LIKE '%If your password was created before 7/3/2020%'
                       AND Disabled = 0");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
