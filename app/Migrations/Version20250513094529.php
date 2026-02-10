<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250513094529 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $internalNoteUpdate = "CONCAT(
            IF(InternalNote IS NULL OR TRIM(InternalNote) = '' OR TRIM(TRAILING '<br>' FROM TRIM(InternalNote)) = '', '', CONCAT(TRIM(TRAILING '<br>' FROM TRIM(InternalNote)), '<br />')),
            '[' , DATE_FORMAT(NOW(), '%d %b %Y'), ']: Removed PROVIDER_CHECKING_OFF state. State changed to enabled.<br />'
        )";

        // remove PROVIDER_CHECKING_AWPLUS_ONLY
        $this->addSql("
            UPDATE Provider
            SET State = " . PROVIDER_ENABLED . ", InternalNote = $internalNoteUpdate
            WHERE State = 5
        ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
