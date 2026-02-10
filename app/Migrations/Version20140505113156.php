<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140505113156 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            UPDATE
                AbMessage am
                JOIN AbRequest ar
                    ON ar.AbRequestID = am.RequestID
            SET
                am.FromBooker = 1
            WHERE
                am.Type IN (1,2,6)
                AND am.UserID <> ar.UserID
                AND am.FromBooker = 0
	    ");
    }

    public function down(Schema $schema): void
    {
    }
}
