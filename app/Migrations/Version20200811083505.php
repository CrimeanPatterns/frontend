<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200811083505 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
			CREATE TABLE UserOAuth (
				UserOAuthID int NOT NULL AUTO_INCREMENT,
				Provider varchar(40) NOT NULL COMMENT 'google, microsoft, yahoo, apple',
				UserID INT NOT NULL COMMENT 'Ссылка на aw профиль юзера',
				OAuthID varchar(512) comment 'Идентификатор пользователя у oauth провайдера, например у гугла: id_token.sub',
				CreateDate datetime not null default current_timestamp(),
				LastLoginDate datetime not null default current_timestamp(),
				PRIMARY KEY (UserOAuthID),
				FOREIGN KEY (UserID) REFERENCES Usr(UserID) ON DELETE CASCADE,
				UNIQUE KEY akUserProvider (Provider, OAuthID)
			) ENGINE=InnoDB;");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE UserOAuth');
    }
}
