<?php

namespace AwardWallet\MainBundle\Migrations;

use AwardWallet\MainBundle\Service\ReviewsUpdater;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160201162455 extends AbstractMigration implements ContainerAwareInterface
{
    /**
     * @var ReviewsUpdater
     */
    private $reviewsUpdater;

    public function up(Schema $schema): void
    {
        $this->reviewsUpdater->update();
    }

    public function down(Schema $schema): void
    {
        $this->reviewsUpdater->revert();
    }

    /**
     * Sets the Container.
     *
     * @param ContainerInterface|null $container A ContainerInterface instance or null
     *
     * @api
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->reviewsUpdater = $container->get('aw.review.updater');
    }
}
