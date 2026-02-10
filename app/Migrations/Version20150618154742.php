<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150618154742 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            INSERT INTO
                Provider(
                    `Name`,
                    `DisplayName`,
                    `Code`,
                    `Kind`,
                    `State`,
                    `CheckInMobileBrowser`,
                    `PasswordRequired`
                ) VALUES (
                    'Test Provider Check In Mobile',
                    'Test Provider Check In Mobile',
                    'testprovidercheckmob',
                    ?,
                    ?,
                    1,
                    0
                )",
            [PROVIDER_KIND_AIRLINE, PROVIDER_TEST],
            [\PDO::PARAM_INT, \PDO::PARAM_INT]
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM Provider WHERE Code = 'testprovidercheckmob'");
    }
}
