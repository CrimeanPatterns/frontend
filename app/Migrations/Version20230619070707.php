<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230619070707 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `UserCreditCard` ADD `DetectedViaEmail` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'EmailCallbackController refs #22089' AFTER `DetectedViaQS`, ALGORITHM=INSTANT 
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `UserCreditCard` DROP `DetectedViaEmail`');
    }
}
