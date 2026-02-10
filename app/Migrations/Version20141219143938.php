<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20141219143938 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("
            UPDATE
                Account
            SET
                InvalidPassCount = 0,
                ErrorCode = ?
            WHERE
                UpdateDate > '2014-12-16 00:00:00' AND
                (InvalidPassCount >= 2 OR ErrorCode = ?);",
            [ACCOUNT_INVALID_PASSWORD, ACCOUNT_PREVENT_LOCKOUT],
            [\PDO::PARAM_INT,          \PDO::PARAM_INT]
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
