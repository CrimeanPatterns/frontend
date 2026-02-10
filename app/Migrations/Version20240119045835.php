<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240119045835 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE Usr 
                ADD AutoDetectLoungeCards TINYINT NOT NULL DEFAULT '0' COMMENT 'Автоматически определять карты для доступа к лаунджам в аэропортах' AFTER AvailableCardsUpdateDate;
        ");
        // all users who have not selected cards for lounge access will have this option enabled by default
        $this->addSql("UPDATE Usr SET AutoDetectLoungeCards = 1 WHERE AvailableCardsUpdateDate IS NULL");
        // add The Business Platinum Card® from Amex (20) to AmexPlatinum category
        $this->addSql("INSERT INTO CreditCardLoungeCategory (CreditCardID, LoungeCategoryID) VALUES (20, 2)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Usr DROP AutoDetectLoungeCards");
        $this->addSql("DELETE FROM CreditCardLoungeCategory WHERE CreditCardID = 20");
    }
}
