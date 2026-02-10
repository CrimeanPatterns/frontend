<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

class Version20210402121212 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE `UserCreditCard` SET EarliestSeenDate = LastSeenDate WHERE EarliestSeenDate IS NOT NULL AND LastSeenDate IS NOT NULL AND EarliestSeenDate > LastSeenDate');
    }

    public function down(Schema $schema): void
    {
    }
}
