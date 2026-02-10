<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231018105051 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE Lounge
                ADD COLUMN LocationParaphrased VARCHAR(4096) NULL COMMENT 'Location paraphrased by OpenAI neural network' AFTER Location;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE Lounge
                DROP COLUMN LocationParaphrased;
        ");
    }
}
