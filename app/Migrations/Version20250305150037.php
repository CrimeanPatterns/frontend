<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20250305150037 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove ThisLounge parser data and hide associated lounges from users.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            UPDATE LoungeSource 
            SET DeleteDate = NOW()
            WHERE SourceCode = 'thislounge';
        ");

        $this->addSql("
            UPDATE Lounge l 
            SET Visible = false
            WHERE l.LoungeID IN (
                -- Find lounges that have only thislounge sources
                SELECT DISTINCT ls.LoungeID 
                FROM LoungeSource ls
                WHERE ls.SourceCode = 'thislounge'
                AND NOT EXISTS (
                    SELECT 1 
                    FROM LoungeSource ls2
                    WHERE ls2.LoungeID = ls.LoungeID 
                    AND ls2.SourceCode <> 'thislounge'
                )
            )
            AND l.Visible = true;
        ");
    }

    public function down(Schema $schema): void
    {
    }
}
