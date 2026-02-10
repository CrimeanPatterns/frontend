<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190417125941 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table TripSegment modify Duration varchar(40) COMMENT 'Duration as parsed (mostly something like \"5h 32m\")'");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
