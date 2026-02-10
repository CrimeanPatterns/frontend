<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210128134055 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `Lounge`
              CHANGE isAvailable IsAvailable TINYINT,
              ADD Gate2 VARCHAR(100) DEFAULT NULL COMMENT 'Второй Gate аэропорта, если указано больше 1' after Gate
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `Lounge`
              CHANGE IsAvailable isAvailable TINYINT,
              DROP Gate2
        ");
    }
}
