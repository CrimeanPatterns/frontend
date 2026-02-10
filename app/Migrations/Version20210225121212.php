<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210225121212 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql(
            'UPDATE UserCreditCard
                 SET EarliestSeenDate = LastSeenDate
                 WHERE EarliestSeenDate > LastSeenDate'
        );
    }

    public function down(Schema $schema): void
    {
    }
}
