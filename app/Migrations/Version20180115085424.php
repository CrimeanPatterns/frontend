<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180115085424 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            alter table `CardImage` 
                add unique key `CardImage_UUID` (`UUID`),
                add key `CardImage_ProviderID` (`ProviderID`)
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            alter table `CardImage`
                drop key `CardImage_ProviderID`,
                drop key `CardImage_UUID`
        ");
    }
}
