<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230523061744 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("
        DELETE FROM RAFlightStat WHERE RAFlightStatID IN (4,6,36,40,49,69,103,104,110,116,132,144,147,155,173,195,262,272,276,277,280,283,301,303,309,452,562,580,591,641,645,648,655,671,697,758,3669,27,247,453,455,461,464,465,467,469,471,472,473,477,478,479,482,483,487,488,490,496,498,499,500,501,502,504,541,545,863,864,866,873)
        ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
