<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210809131313 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `TransferStat` CHANGE `CustomMessage` `CustomMessage` VARCHAR(2048) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
    }
}
