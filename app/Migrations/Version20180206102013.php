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
class Version20180206102013 extends AbstractMigration implements ContainerAwareInterface
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
            $this->connection->executeQuery("UPDATE Cart SET BillingTransactionID = NULL WHERE 
                BillingTransactionID = '' OR BillingTransactionID = ' ' OR BillingTransactionID = '0'
            ");
            $this->connection->executeQuery('UPDATE Cart SET Comments = BillingTransactionID, BillingTransactionID = NULL WHERE 
                BillingTransactionID LIKE "by %" OR BillingTransactionID LIKE "per %" OR BillingTransactionID LIKE "%per Howie%"
            ');
            $this->connection->executeQuery('UPDATE Cart SET BillingTransactionID = CONCAT(BillingTransactionID, CartID) WHERE 
                BillingTransactionID = "hidden"
            ');

            $stmt = $this->connection->executeQuery("
                SELECT
                  *
                FROM (
                  SELECT
                    CartID,
                    PaymentType,
                    BillingTransactionID
                  FROM Cart
                  WHERE
                    PaymentType IS NOT NULL AND
                    BillingTransactionID IS NOT NULL
                  ORDER BY LastUsedDate DESC, PurchaseToken IS NOT NULL
                ) t
                
                GROUP BY CONCAT(PaymentType, '-', BillingTransactionID)
                HAVING COUNT(*) > 1
            ");

            while ($row = $stmt->fetch()) {
                foreach ($this->cartRep->findBy(['paymenttype' => $row['PaymentType'], 'billingtransactionid' => $row['BillingTransactionID']]) as $cart) {
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
