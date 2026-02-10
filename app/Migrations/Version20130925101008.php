<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20130925101008 extends AbstractMigration implements DependencyInjection\ContainerAwareInterface
{
    private $container;

    public function setContainer(DependencyInjection\ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function up(Schema $schema): void
    {
        /** @var $doctrine \Doctrine\Bundle\DoctrineBundle\Registry */
        $doctrine = $this->container->get('doctrine');
        $conn = $doctrine->getConnection();

        // Remove AbMessage.Internal
        $conn->executeUpdate('UPDATE AbMessage SET Type = ? WHERE Internal = ?', [8, 1]);
        $table = $schema->getTable('AbMessage');
        $table->dropColumn('Internal');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `AbMessage` ADD `Internal` INT  NOT NULL  AFTER `Post`;');
        $this->addSql('UPDATE AbMessage SET Type = 0, Internal = 1 WHERE Type = 8');
    }
}
