<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161123110630 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('
        create table EmailParsingFormat (
		  EmailParsingFormatID int not null auto_increment,
		  ProviderID int not null,
		  Count int not null,
		  Languages varchar(256),
		  Updated tinyint,
		  primary key (EmailParsingFormatID),
		  unique key (ProviderID),
		  foreign key (ProviderID) references Provider(ProviderID)
		) engine=InnoDB
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('drop table if exists EmailParsingFormat');
    }
}
