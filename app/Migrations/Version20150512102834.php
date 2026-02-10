<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150512102834 extends AbstractMigration
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
                    `ProviderGroup`
                ) VALUES (
                    'Test Provider Group',
                    'Test Provider Group',
                    'testprovidergroup',
                    ?,
                    ?,
                    'testprovidergroup'
                )",
            [PROVIDER_KIND_AIRLINE, PROVIDER_TEST],
            [\PDO::PARAM_INT, \PDO::PARAM_INT]
        );

        $this->addSql("UPDATE `Provider` SET `ProviderGroup` = 'testprovidergroup' WHERE ProviderID = 636");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM `Provider` WHERE `Code` = 'testprovidergroup'");
        $this->addSql('UPDATE `Provider` SET `ProviderGroup` = NULL WHERE ProviderID = 636');
    }
}
