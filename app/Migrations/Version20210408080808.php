<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210408080808 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("DELETE FROM Review WHERE UserID = 7 AND Review = 'Excellent!'");

        $this->addSql('UPDATE `UserCreditCard` SET EarliestSeenDate = LastSeenDate WHERE EarliestSeenDate IS NOT NULL AND LastSeenDate IS NOT NULL AND EarliestSeenDate > LastSeenDate');
        
        $this->addSql('ALTER TABLE `BlogUserReport` ADD `IsAuthorized` TINYINT(1) NOT NULL DEFAULT 1');
    }

    public function down(Schema $schema): void
    {
    }
}
