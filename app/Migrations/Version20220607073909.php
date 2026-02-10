<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220607073909 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE LoungeSource
                ADD OpeningHoursData JSON NULL COMMENT 'Структурированная информация о часах работы' AFTER OpeningHours;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE LoungeSource DROP OpeningHoursData;
        ");
    }
}
