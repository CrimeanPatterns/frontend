<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231017121212 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("DELETE FROM `AccountBalance` WHERE AccountBalanceID = 86187244 AND AccountID = 5863478");
    }

    public function down(Schema $schema): void
    {
    }
}
