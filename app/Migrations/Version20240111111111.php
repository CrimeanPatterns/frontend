<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240111111111 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `UserCreditCard`
                ADD `LastSeenOnQsDate` DATETIME NULL DEFAULT NULL COMMENT 'Последняя дата из QsTransaction' AFTER `LastSeenDate`,
            ALGORITHM INSTANT
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `UserCreditCard` DROP `LastSeenOnQsDate`');
    }
}
