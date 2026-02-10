<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231220120500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(/** @lang MySQL */ "
            CREATE INDEX `idxTripitAccessToken`
            ON `Usr` ((CAST(`TripitOauthToken`->>'$.oauth_access_token' AS CHAR(32)) COLLATE utf8mb4_bin)) USING BTREE;");
    }

    public function down(Schema $schema): void
    {
        $this->addSql(/** @lang MySQL */ "
            DROP INDEX `idxTripitAccessToken` ON `Usr`;");
    }
}
