<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170421095002 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('DELETE a FROM AirCode a, AirCode b WHERE a.AirCodeID < b.AirCodeID AND a.AirCode = b.AirCode');
        $schema->getTable('AirCode')->addUniqueIndex(['AirCode'], 'aircode_aircode_unique');
    }

    public function down(Schema $schema): void
    {
        $schema->getTable('AirCode')->dropIndex('aircode_aircode_unique');
    }
}
