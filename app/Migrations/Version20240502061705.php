<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240502061705 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            UPDATE Lounge
            SET OpeningHoursAi = NULL
            WHERE OpeningHoursAi IS NOT NULL
                AND JSON_LENGTH(OpeningHoursAi, '$.data.data') != 7
        ");
    }

    public function down(Schema $schema): void
    {
    }
}
