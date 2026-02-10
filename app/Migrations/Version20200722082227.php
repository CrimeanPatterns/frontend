<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200722082227 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `MobileDevice` ADD `RememberMeTokenID` INT(11) UNSIGNED NULL DEFAULT NULL AFTER `UserID`,
            add foreign key(RememberMeTokenID) references RememberMeToken(RememberMeTokenID) on delete set null;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `MobileDevice` DROP `RememberMeTokenID`');
    }
}
