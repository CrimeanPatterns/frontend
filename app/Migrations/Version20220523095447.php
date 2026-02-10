<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220523095447 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            DELETE FROM LoungeSourceChange WHERE Property IN ('Alliances', 'Airlines');
        ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
