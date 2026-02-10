<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use AwardWallet\MainBundle\FrameworkExtension\Migrations\ContainerAwareMigrationInterface;
use AwardWallet\MainBundle\Globals\Geo;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230110072810 extends AbstractMigration implements ContainerAwareMigrationInterface
{
    use ContainerAwareTrait;

    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $table = $schema->getTable('RAFlight');

        if (!$table->hasColumn('ODDistance')) {
            $this->connection->executeQuery("
                ALTER TABLE `RAFlight` ADD `ODDistance` float NOT NULL DEFAULT 0 COMMENT 'расстояние в милях от пункта вылета, до пункта назначения';
            ");
        }
        
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("ALTER TABLE `RAFlight` DROP `ODDistance`");
    }
}
