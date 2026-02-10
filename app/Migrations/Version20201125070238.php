<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201125070238 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `Merchant` 
                CHANGE `Name` `Name` VARCHAR(250) CHARACTER SET utf8mb4  COLLATE utf8mb4_general_ci,
                CHANGE `DisplayName` `DisplayName` VARCHAR(250) CHARACTER SET utf8mb4  COLLATE utf8mb4_general_ci
            ;");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `Merchant` 
                CHANGE `Name` `Name` VARCHAR(250) CHARACTER SET utf8  COLLATE utf8_general_ci,
                CHANGE `DisplayName` `DisplayName` VARCHAR(250) CHARACTER SET utf8  COLLATE utf8_general_ci
            ;");
    }
}
