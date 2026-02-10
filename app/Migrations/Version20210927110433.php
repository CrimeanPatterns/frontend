<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210927110433 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE LoungePage ADD LocationChanged TINYINT DEFAULT 0 NOT NULL COMMENT 'Терминал и/или gates были изменены с последним парсингом' AFTER MergeData");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE LoungePage DROP LocationChanged");
    }
}
