<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210621071008 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'remove broken records from TimelineShare';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            delete from TimelineShare
            where FamilyMemberID in (
                select UserAgentID
                from UserAgent
                where ClientID is not null
            );
        ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
