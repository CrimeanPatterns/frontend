<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use AwardWallet\MainBundle\Entity\MobileDevice;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220822123727 extends AbstractMigration
{
    private const USER_AGENT_FOR_EDGE = 'Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/104.0.5112.81 Safari/537.36 Edg/AwMigration 104.0.1293.47';
    private const WINDOWS_ENDPOINT = '.notify.windows.com';

    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            update `MobileDevice` 
            set UserAgent = ? 
            where DeviceType = ? AND DeviceKey like ?",
            [
                self::USER_AGENT_FOR_EDGE,
                MobileDevice::TYPE_CHROME,
                '%' . self::WINDOWS_ENDPOINT . '%',
            ]
        );
    }

    public function down(Schema $schema): void
    {
    }
}
