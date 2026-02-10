<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230704135755 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        if (!$schema->getTable('RAFlight')->hasColumn('StandardItineraryCOS')) {
            $this->addSql(/** @lang MySQL */ "
            ALTER TABLE `RAFlight` 
                RENAME COLUMN `CabinType` TO `StandardItineraryCOS`, 
                RENAME COLUMN `Cabins` TO `StandardSegmentCOS`,
                RENAME COLUMN `SegmentClassOfService` TO `BrandedSegmentCOS`,
                RENAME COLUMN `ClassOfService` TO `BrandedItineraryCOS`, algorithm=instant
            ");
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
