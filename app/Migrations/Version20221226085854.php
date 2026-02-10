<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221226085854 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("ALTER TABLE Provider ADD EmailFormatKind tinyint(4) COMMENT 'Если в поле Kind указан тип провайдера 5 (это значит Other), то в этом поле должна быть указана константа точного типа (Ferry, Bus ... etc)' AFTER Kind;");

    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("ALTER TABLE Provider DROP COLUMN EmailFormatKind;");
    }
}
