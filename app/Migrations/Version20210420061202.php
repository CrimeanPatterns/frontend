<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use AwardWallet\MainBundle\FrameworkExtension\Migrations\ContainerAwareMigrationInterface;
use AwardWallet\MainBundle\Service\AirHelp\AirHelpImporterAwareTrait;
use AwardWallet\MainBundle\Service\AirHelp\Model\CsvSource;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210420061202 extends AbstractMigration implements ContainerAwareMigrationInterface
{
    use AirHelpImporterAwareTrait;

    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $this->airHelpImporter->saveCsvToDbFromMigration(
            [new CsvSource('awardwallet-ec261-second_reminder-2021-04-19.csv', 'ec_04_19')],
            $this
        );
    }

    public function down(Schema $schema) : void
    {
    }
}
