<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190214051530 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE ExtensionStat MODIFY ErrorText VARCHAR(255)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE ExtensionStat MODIFY ErrorText VARCHAR(200)");
    }
}
