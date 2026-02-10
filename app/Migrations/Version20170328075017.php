<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170328075017 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->connection->transactional(function () {
            $this->connection->executeQuery('ALTER TABLE `Param` ADD `BigData` MEDIUMTEXT NOT NULL DEFAULT \'\' AFTER `Val`');
        });
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `Param` DROP `BigData`');
    }
}
