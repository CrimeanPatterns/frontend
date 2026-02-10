<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230719033048 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE EliteLevelProgress
            ADD COLUMN Position INT NULL COMMENT 'Порядок для правильного формирования условий' AFTER GroupIndex,
            ADD COLUMN GroupID INT NULL COMMENT 'Идентификатор группы' AFTER Position,
            ADD COLUMN Operator TINYINT NULL COMMENT 'Оператор OR (1) или AND (2)' AFTER GroupID;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE EliteLevelProgress
            DROP COLUMN Position,
            DROP COLUMN GroupID,
            DROP COLUMN Operator;
        ");
    }
}
