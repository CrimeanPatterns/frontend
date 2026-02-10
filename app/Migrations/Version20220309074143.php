<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220309074143 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE Provider 
                ADD BlogIdsMilesPurchase VARCHAR(250) NULL COMMENT 'Blog ID that talks about purchasing miles or points for this provider, if more than one, make them comma-separated but the first one will be used in most cases',
                ADD BlogIdsMilesTransfers VARCHAR(250) NULL COMMENT 'Blog ID that talks about transferring miles or points for this provider, if more than one, make them comma-separated but the first one will be used in most cases',
                ADD BlogIdsPromos VARCHAR(250) NULL COMMENT 'Comma separated list of blog IDs that talk about various promos for this provider',
                ADD BlogIdsMileExpiration VARCHAR(250) NULL COMMENT 'Blog ID that talks about point or mile expiration policy for this provider, if more than one, make them comma-separated but the first one will be used in most cases';
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE Provider 
            DROP BlogIdsMilesPurchase,
            DROP BlogIdsMilesTransfers,
            DROP BlogIdsPromos,
            DROP BlogIdsMileExpiration
        ");
    }
}
