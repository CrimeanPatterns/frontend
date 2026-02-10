<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20200817080000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `QsTransaction` ADD UNIQUE(`Hash`)');
        $this->addSql("ALTER TABLE `QsTransaction` CHANGE `Hash` `Hash` VARCHAR(40) NULL DEFAULT NULL COMMENT 'sha1(ClickDate,RawAccount,RawVar1,Card)'");
    }

    public function down(Schema $schema): void
    {
    }
}
