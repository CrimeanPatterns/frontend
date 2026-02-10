<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221104102012 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'marriott - disable bad accounts';
    }

    public function up(Schema $schema): void
    {
        // DisableReason - DISABLE_REASON_PROVIDER_ERROR
        $this->addSql("UPDATE Account SET DisableReason = 3, Disabled = 1, DisableDate = now()
                       WHERE ProviderID = 17
                       AND ErrorCode = 4
                       AND ErrorMessage LIKE 'Marriott Bonvoy website is asking you to update your profile%'
                       AND Disabled = 0");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
