<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241213103200 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql(/** @lang MySQL */ "
            ALTER TABLE `Usr`
                ADD COLUMN `TripitLastSync` DATETIME NULL COMMENT 'Date of last successful synchronization with TripIt' AFTER `TripitOauthToken`;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql(/** @lang MySQL */ "
            ALTER TABLE `Usr`
                DROP COLUMN `TripitLastSync`;
        ");
    }
}
