<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240506064319 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'hotels - disable bad accounts';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("UPDATE Account SET DisableReason = 3, Disabled = 1, DisableDate = now()
                       WHERE ProviderID = 105
                       AND AccountID IN (7056347,6769161,7457484,6903933,6386095,5711090,7525430,6871390,7554742,4948728,5892767,6418702,4941508,6498905,6465172,5632386,6164044,6293314,5758116,5599168,4830675,4595116,4699097,4922398,4442325,4694073,4871319,4593322,4618977,4189752,4889629,4563736,3936298,3517399,1236661,4050371,3814975,2904489,2758028,2750036,3465354,1784850,4031297,1316484,3402115,693086,746352,1884845,424222,7556142,7153173,7545792,7462187,7477746,6999139,6613851)
                       AND Disabled = 0");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
