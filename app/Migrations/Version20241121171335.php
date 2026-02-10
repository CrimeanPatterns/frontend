<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241121171335 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE ABTest (
                TestID VARCHAR(255) NOT NULL,
                Variant VARCHAR(255) NOT NULL,
                ExposureCount INT NOT NULL DEFAULT 0,
                CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UpdatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (TestID, Variant)
            ) ENGINE=InnoDB COMMENT 'AB тестирование';
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE ABTest');
    }
}
