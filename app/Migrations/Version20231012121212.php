<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231012121212 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("DELETE FROM `QsTransaction` WHERE ClickDate >= '2023-09-01' OR Version = 3");
        $this->addSql("DELETE FROM `QsCreditCardHistory` WHERE QsCreditCardHistoryID IN (33,292,34,301,312,1139,559,101,104,1145,1146,317,112,283,1164)");
    }

    public function down(Schema $schema): void
    {
    }
}
