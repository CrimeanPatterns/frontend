<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160121125947 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `ExtensionStat` ADD COLUMN Platform VARCHAR(20)');
        $this->addSql("UPDATE `ExtensionStat` SET Platform = 'desktop' WHERE Platform IS NULL");
        $this->addSql("ALTER TABLE `ExtensionStat`
            DROP KEY `ProviderID`,
            ADD UNIQUE KEY `ProviderID` (`ProviderID`,`Success`,`ErrorText`, `Platform`)
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `ExtensionStat`
            DROP KEY `ProviderID`,
            ADD UNIQUE KEY `ProviderID` (`ProviderID`,`Success`,`ErrorText`)
        ");

        $this->addSql('ALTER TABLE ExtensionStat DROP COLUMN Platform');
    }
}
