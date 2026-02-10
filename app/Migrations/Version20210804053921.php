<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210804053921 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE LoungePage ADD MergeData JSON NULL COMMENT 'Информация о том, какие поля были использованы для создания/обновления Lounge' AFTER LoungeID;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE LoungePage DROP MergeData;
        ");
    }
}
