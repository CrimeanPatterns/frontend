<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230617101649 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE LoungeAction 
                ADD DeleteDate DATETIME NULL COMMENT 'Автоматическое удаление' AFTER UpdateDate
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE LoungeAction DROP DeleteDate");
    }
}
