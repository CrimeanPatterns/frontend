<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20201027090909 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE `UserCreditCard` SET LastSeenDate = EarliestSeenDate WHERE EarliestSeenDate IS NOT NULL AND LastSeenDate IS NOT NULL AND EarliestSeenDate > LastSeenDate');
    }

    public function down(Schema $schema): void
    {
    }
}
