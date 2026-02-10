<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use AwardWallet\MainBundle\FrameworkExtension\Migrations\ContainerAwareMigrationInterface;
use AwardWallet\MainBundle\Service\AirHelp\AirHelpImporterAwareTrait;
use AwardWallet\MainBundle\Service\AirHelp\Model\CsvSource;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210610053259 extends AbstractMigration implements ContainerAwareMigrationInterface
{
    use AirHelpImporterAwareTrait;

    public function getDescription(): string
    {
        return 'import awardwallet-brazil-second_reminder-20210608.csv to db';
    }

    public function up(Schema $schema): void
    {
        $this->airHelpImporter->saveCsvToDbFromMigration(
            [new CsvSource('awardwallet-brazil-second_reminder-20210608.csv', 'br_06_08')],
            $this
        );

    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
