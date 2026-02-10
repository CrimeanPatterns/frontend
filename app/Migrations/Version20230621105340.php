<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230621105340 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            UPDATE LoungeSource
            SET OpeningHours = CASE
                WHEN OpeningHours IS NULL THEN NULL
                ELSE JSON_OBJECT(
                    'data', OpeningHours,
                    '_jms_type', 'AwardWallet\\\MainBundle\\\Service\\\Lounge\\\OpeningHours\\\AbstractOpeningHours'
                )
            END;
        ");
        $this->addSql("
            UPDATE Lounge AS l
            JOIN LoungeSource AS ls ON ls.LoungeID = l.LoungeID AND ls.SourceCode = 'loungebuddy'
            SET l.OpeningHours = ls.OpeningHours;
        ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
