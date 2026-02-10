<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180704122348 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('
            INSERT INTO `ProviderProperty` (`ProviderPropertyID`, `ProviderID`, `Name`, `Code`, `SortIndex`, `Required`, `Kind`, `Visible`)
            VALUES
	        (NULL, NULL, \'Is Hidden\', \'IsHidden\', 1, 0, NULL, 0);
        ');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
