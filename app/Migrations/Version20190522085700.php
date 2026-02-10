<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190522085700 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("create table SiteAdUser (
            SiteAdUserID int not null auto_increment,
            SiteAdID int not null,
            UserID int not null,
            primary key (SiteAdUserID),
            unique key(SiteAdID, UserID),
            foreign key (SiteAdID) references SiteAd(SiteAdID) on delete cascade,
            foreign key (UserID) references Usr(UserID) on delete cascade
        ) engine=InnoDb comment 'Эти пользователи имеют READ_ONLY доступ к букингу, для просмотра букинг запросов сделаннах через этого реферала'");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
