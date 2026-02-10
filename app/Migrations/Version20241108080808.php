<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241108080808 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `EmailCustomParam`
                ADD `BlogDigestExcludeID` VARCHAR(255) NULL DEFAULT NULL COMMENT 'ID блогпостов которые не должны попасть в digest weekly рассылку' AFTER `Message`; 
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `EmailCustomParam` DROP `BlogDigestExcludeID`');
    }
}
