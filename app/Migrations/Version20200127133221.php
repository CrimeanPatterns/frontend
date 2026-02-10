<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200127133221 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
        CREATE TABLE `VerifiedEmail` (
            `VerifiedEmailID` int not null auto_increment primary key,
            `Email` varchar(80) not null,
            `VerificationDate` datetime not null default current_timestamp(),
            UNIQUE KEY `VerifiedEmail_Email_uindex` (`Email`)
        ) ENGINE = InnoDB
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE `VerifiedEmail`");
    }
}
