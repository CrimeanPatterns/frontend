<?php

namespace AwardWallet\MainBundle\Migrations;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\Repositories\CartRepository;
use AwardWallet\MainBundle\Globals\Cart\Manager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180208071529 extends AbstractMigration implements ContainerAwareInterface
{
    /**
     * @var CartRepository
     */
    private $cartRep;

    /**
     * @var Manager
     */
    private $cartManager;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->cartRep = $container->get('doctrine')->getRepository(\AwardWallet\MainBundle\Entity\Cart::class);
        $this->cartManager = $container->get('aw.manager.cart');
    }

    public function up(Schema $schema): void
    {
        $this->connection->transactional(function () {
            $this->connection->executeQuery("
                UPDATE Cart SET CartAttrHash = CONCAT_WS('|', UserID, PaymentType, PayDate)
                WHERE UserID IS NOT NULL AND PaymentType IS NOT NULL AND PayDate IS NOT NULL AND 
                  PaymentType = 8
            ");

            $stmt = $this->connection->executeQuery("
                SELECT
                    CartID,
                    CartAttrHash
                FROM 
                    Cart
                WHERE
                    CartAttrHash IS NOT NULL
                GROUP BY CartAttrHash
                HAVING COUNT(*) > 1
            ");

            while ($row = $stmt->fetch()) {
                foreach ($this->cartRep->findBy(['cartAttrHash' => $row['CartAttrHash']]) as $cart) {
                    /** @var Cart $cart */
                    if ($cart->getCartid() != $row['CartID']) {
                        $this->cartManager->refund($cart);
                    }
                }
            }
        });
    }

    public function down(Schema $schema): void
    {
    }
}
