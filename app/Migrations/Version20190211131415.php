<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190211131415 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE `Provider` SET `LoginCaption` = 'Login (Type of test)' WHERE `ProviderID` = 636");
    }

    public function down(Schema $schema): void
    {
    }
}
