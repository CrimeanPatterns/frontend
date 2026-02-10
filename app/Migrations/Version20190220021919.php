<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use AwardWallet\MainBundle\Entity\CartItem\BalanceWatchCredit;
use AwardWallet\MainBundle\Globals\Cart\Manager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\ORM\EntityManager;
use Facile\DoctrineMySQLComeBack\Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Version20190220021919 extends AbstractMigration implements ContainerAwareInterface
{
    /** @var Connection */
    private $unbuffConn;

    /** @var EntityManager */
    private $entityManager;

    /** @var Manager */
    private $cartManager;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->entityManager = $container->get('doctrine.orm.default_entity_manager');
        $this->cartManager = $container->get('aw.manager.cart');
        $this->unbuffConn = $container->get('doctrine.dbal.read_replica_unbuffered_connection');
    }

    public function up(Schema $schema): void
    {
        $sql = 'FROM Cart c, CartItem ci WHERE c.CartID = ci.CartID and ci.TypeID = ' . BalanceWatchCredit::TYPE;
        $count = $this->unbuffConn->fetchColumn('SELECT COUNT(c.CartID) ' . $sql, [], 0);
        $this->write('Found ' . $count . ' carts with BalanceWatchCredit for CLEANING');

        $cartRepository = $this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\Cart::class);
        $cartIdRemoves = [];

        $index = 0;
        $usrCart = $this->unbuffConn->executeQuery('SELECT c.CartID ' . $sql);

        while ($uc = $usrCart->fetch()) {
            $cart = $cartRepository->find($uc['CartID']);

            if (1 === \count($cart->getItems())) { // only BalanceWatchCredit
                $cartIdRemoves[] = $cart->getCartid();
            } else {
                $cart->removeItemsByType([BalanceWatchCredit::TYPE]);
                $this->cartManager->save($cart);
            }

            if (++$index % 50 === 0) {
                $this->write('Processed ' . $index . ' of ' . $count . ' ...');
            }
        }

        if (!empty($cartIdRemoves)) {
            $this->addSql('DELETE FROM Cart WHERE CartID IN(' . implode(',', $cartIdRemoves) . ')');
            $this->addSql('DELETE FROM CartItem WHERE CartID IN(' . implode(',', $cartIdRemoves) . ')');
        }

        $this->addSql('UPDATE Usr SET BalanceWatchCredits = 0 WHERE BalanceWatchCredits > 0');
        $this->addSql('TRUNCATE BalanceWatch');
        $this->addSql('TRUNCATE BalanceWatchCreditsTransaction');

        $this->write('BalanceWatch markAsPaid migration : CLEANING - DONE');
    }

    public function down(Schema $schema): void
    {
    }
}
