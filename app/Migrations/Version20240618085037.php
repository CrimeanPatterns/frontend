<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240618085037 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE RAFlightSearchQuery
            ADD ExcludeParsers TEXT DEFAULT NULL COMMENT 'Дополнительная фильтрация результатов поиска. Результаты с этих парсеров будут исключены из результата.'
                AFTER AutoSelectParsers;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE RAFlightSearchQuery
            DROP COLUMN ExcludeParsers;
        ');
    }
}
