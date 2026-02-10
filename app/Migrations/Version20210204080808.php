<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210204080808 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            UPDATE QsTransaction qt
            LEFT JOIN Usr u ON (u.UserID = qt.UserID)
            SET qt.UserID = NULL
            WHERE
                    u.UserID IS NULL
                AND qt.UserID IS NOT NULL
        ");
        $this->addSql('ALTER TABLE `QsTransaction` ADD CONSTRAINT `fkQsTransaction_UserID` FOREIGN KEY (`UserID`) REFERENCES `Usr`(`UserID`) ON DELETE SET NULL ON UPDATE CASCADE');
    }

    public function down(Schema $schema): void
    {
    }
}
