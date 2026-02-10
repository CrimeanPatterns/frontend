<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190306044522 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("create table AirClassDictionary(
            AirClassDictionaryID int not null auto_increment,
            Source varchar(120) not null,
            Target varchar(40) not null,
            primary key(AirClassDictionaryID),
            unique key akSource (Source)
        ) engine=InnoDB");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
