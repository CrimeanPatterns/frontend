<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210810093944 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("
  UPDATE Provider SET RewardAvailabilityPriority=1 WHERE Code='delta';
  UPDATE Provider SET RewardAvailabilityPriority=2 WHERE Code='mileageplus';
  UPDATE Provider SET RewardAvailabilityPriority=3 WHERE Code='aeroplan';
  UPDATE Provider SET RewardAvailabilityPriority=4 WHERE Code='aviancataca';
  UPDATE Provider SET RewardAvailabilityPriority=5 WHERE Code='airfrance';
  UPDATE Provider SET RewardAvailabilityPriority=6 WHERE Code='virgin';
  UPDATE Provider SET RewardAvailabilityPriority=7 WHERE Code='iberia';
  UPDATE Provider SET RewardAvailabilityPriority=8 WHERE Code='british';
  UPDATE Provider SET RewardAvailabilityPriority=9 WHERE Code='alaskaair';
  UPDATE Provider SET RewardAvailabilityPriority=10 WHERE Code='qantas';
  UPDATE Provider SET RewardAvailabilityPriority=11 WHERE Code='skywards';
  UPDATE Provider SET RewardAvailabilityPriority=12 WHERE Code='jetblue';
  UPDATE Provider SET RewardAvailabilityPriority=13 WHERE Code='rapidrewards';
  UPDATE Provider SET RewardAvailabilityPriority=14 WHERE Code='turkish';
  UPDATE Provider SET RewardAvailabilityPriority=15 WHERE Code='singaporeair';
  UPDATE Provider SET RewardAvailabilityPriority=16 WHERE Code='etihad';
  UPDATE Provider SET RewardAvailabilityPriority=17 WHERE Code='asia';
  UPDATE Provider SET RewardAvailabilityPriority=18 WHERE Code='hawaiian';
  UPDATE Provider SET RewardAvailabilityPriority=19 WHERE Code='tapportugal';
  UPDATE Provider SET RewardAvailabilityPriority=20 WHERE Code='asiana';
  UPDATE Provider SET RewardAvailabilityPriority=21 WHERE Code='korean';
  UPDATE Provider SET RewardAvailabilityPriority=22 WHERE Code='velocity';
  UPDATE Provider SET RewardAvailabilityPriority=23 WHERE Code='aeromexico';
  UPDATE Provider SET RewardAvailabilityPriority=24 WHERE Code='eurobonus';
  UPDATE Provider SET RewardAvailabilityPriority=25 WHERE Code='israel';
  UPDATE Provider SET RewardAvailabilityPriority=26 WHERE Code='hainan';
        ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
