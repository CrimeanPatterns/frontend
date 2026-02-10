<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170521142337 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE Usr 
              DROP COLUMN AdShows,
              DROP COLUMN AdClicks,
              DROP COLUMN AdShowDate,
              DROP COLUMN AdUserContent,
              DROP COLUMN AdShowName,
              DROP COLUMN AdDisabled,
              DROP COLUMN AdCompanyName,
              DROP COLUMN AdTargetURL,
              DROP COLUMN AdDescription,
              DROP COLUMN AdPictureVer,
              DROP COLUMN AdPictureExt,
              DROP COLUMN AdApproved,
              DROP COLUMN AdMode,
              DROP COLUMN AdRating
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE Usr 
              ADD AdShows INT DEFAULT '0' NOT NULL AFTER SocialAdID,
              ADD AdClicks INT DEFAULT '0' NOT NULL AFTER AdShows,
              ADD AdShowDate DATETIME NOT NULL AFTER AdClicks,
              ADD AdUserContent VARCHAR(4000) NULL AFTER AdShowDate,
              ADD AdShowName INT DEFAULT '3' NOT NULL AFTER EmailTCSubscribe,
              ADD AdDisabled INT DEFAULT '1' NOT NULL AFTER AdShowName,
              ADD AdCompanyName VARCHAR(80) NULL AFTER AdDisabled,
              ADD AdTargetURL VARCHAR(80) NULL AFTER AdCompanyName,
              ADD AdDescription VARCHAR(160) NULL AFTER AdTargetURL,
              ADD AdPictureVer INT NULL AFTER AdDescription,
              ADD AdPictureExt VARCHAR(5) NULL AFTER AdPictureVer,
              ADD AdApproved INT DEFAULT '0' NOT NULL AFTER AdPictureExt,
              ADD AdMode INT DEFAULT '0' NOT NULL AFTER AdApproved,
              ADD AdRating FLOAT NULL AFTER Skin
        ");
    }
}
