<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140117134245 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // DELETE Amextravel accounts(duplicated by amex accounts already)
        $this->addSql("delete from Account where ProviderID = 873");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
