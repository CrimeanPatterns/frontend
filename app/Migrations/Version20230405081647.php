<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230405081647 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->getTable('UserIP')->hasIndex('UserIP_Point')) {
            $this->addSql("alter table UserIP ADD SPATIAL INDEX `UserIP_Point` (`Point`)");
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('alter table UserIP DROP INDEX `UserIP_Point`');
    }
}
