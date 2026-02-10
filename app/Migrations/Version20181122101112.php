<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use AwardWallet\MainBundle\Entity\CartItem\AAUCredit;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Version20181122101112 extends AbstractMigration implements ContainerAwareInterface
{
    /** @var EntityManager */
    private $entityManager;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->entityManager = $container->get('doctrine.orm.default_entity_manager');
    }

    public function up(Schema $schema): void
    {
        $this->addSql('TRUNCATE AAUCreditsTransaction');
        $this->addSql('UPDATE Usr SET AAUCredits = 0');

        $cartId = $this->entityManager->getConnection()->fetchAll('SELECT c.CartID FROM Cart c, CartItem ci WHERE c.CartID = ci.CartID and ci.TypeID = ' . AAUCredit::TYPE);

        if (!empty($cartId)) {
            $this->addSql('DELETE FROM Cart WHERE CartID IN(' . implode(',', array_column($cartId, 'CartID')) . ')');
        }
    }

    public function down(Schema $schema): void
    {
    }
}
