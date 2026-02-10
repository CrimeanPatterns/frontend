<?php

namespace AwardWallet\MainBundle\Migrations;

use AwardWallet\MainBundle\Entity\MobileDevice;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170713090523 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("update `MobileDevice` set DeviceType = ? where DeviceKey = ? and DeviceKey like '%mozilla.com%'", [MobileDevice::TYPE_FIREFOX, MobileDevice::TYPE_CHROME]);
    }

    public function down(Schema $schema): void
    {
        $this->addSql("update `MobileDevice` set DeviceType = ? where DeviceKey = ? and DeviceKey like '%mozilla.com%'", [MobileDevice::TYPE_CHROME, MobileDevice::TYPE_FIREFOX]);
    }
}
