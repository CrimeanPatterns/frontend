<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230808105119 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE Usr 
                ADD HaveDragonPassCard TINYINT NOT NULL DEFAULT '0' COMMENT 'Есть ли у юзера карта Dragon Pass для доступа к лаунджам в аэропортах',
                ADD HaveLoungeKeyCard TINYINT NOT NULL DEFAULT '0' COMMENT 'Есть ли у юзера карта Lounge Key для доступа к лаунджам в аэропортах';
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE Usr 
                DROP HaveDragonPassCard,
                DROP HaveLoungeKeyCard;
        ");
    }
}
