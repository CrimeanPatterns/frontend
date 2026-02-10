<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240502160528 extends AbstractMigration
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
                       AND AccountID IN (5821978, 4504754, 1333346, 2944546, 4799948, 3018821, 6017534, 6664889, 7537383, 6363186, 5962107, 2941098, 6421628, 6904685, 4995390, 2635037, 2141220, 6345437, 4446054, 6760397, 3752096, 2710886, 1600358, 1986396, 2421912, 1997949, 3210814, 3435060, 3852819, 4698213, 2808650, 4380938, 4881545, 4042804, 4436147, 4986817, 7190605, 4689875, 4584849, 7455297, 5333992, 738193, 3409113, 1994890, 738193)
                       AND Disabled = 0");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
