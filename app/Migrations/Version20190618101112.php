<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190618101112 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('
            UPDATE Account
            SET
                ExpirationAutoSet = ' . EXPIRATION_UNKNOWN . '
            WHERE
                    ProviderID        = 26
                AND ExpirationAutoSet = ' . EXPIRATION_AUTO . '
                AND ExpirationDate IS NULL
        ');
    }

    public function down(Schema $schema): void
    {
    }
}
