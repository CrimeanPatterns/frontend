<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use AwardWallet\MainBundle\Entity\BalanceWatchCreditsTransaction;
use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription;
use AwardWallet\MainBundle\Entity\CartItem\BalanceWatchCredit;
use AwardWallet\MainBundle\Globals\Cart\Manager;
use AwardWallet\MainBundle\Service\BalanceWatch\BalanceWatchManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\ORM\EntityManager;
use Facile\DoctrineMySQLComeBack\Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Version20190220022002 extends AbstractMigration implements ContainerAwareInterface
{
    /** @var EntityManager */
    private $entityManager;

    /** @var BalanceWatchManager */
    private $balanceWatchManager;

    /** @var Connection */
    private $unbuffConn;

    /** @var LoggerInterface */
    private $loggerPayment;

    /** @var Manager */
    private $cartManager;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->entityManager = $container->get('doctrine.orm.default_entity_manager');
        $this->balanceWatchManager = $container->get(BalanceWatchManager::class);
        $this->unbuffConn = $container->get('doctrine.dbal.read_replica_unbuffered_connection');
        $this->loggerPayment = $container->get('monolog.logger.payment');
        $this->cartManager = $container->get('aw.manager.cart');
    }

    public function up(Schema $schema): void
    {
        $cartRepository = $this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\Cart::class);
        $sql = '
            FROM
                Usr u, Cart c, CartItem ci
            WHERE
                    u.UserID = c.UserID
                AND c.CartID = ci.CartID
                AND u.AccountLevel   = ' . ACCOUNT_LEVEL_AWPLUS . '
                AND ci.TypeID        IN (' . AwPlusSubscription::TYPE . ')
                AND ci.ScheduledDate IS NULL
                AND u.BalanceWatchCredits = 0
                AND c.PayDate > DATE_SUB(NOW(), INTERVAL 13 MONTH)
                AND c.CouponID IS NULL
            ORDER BY c.CartID DESC
        ';
        $count = $this->entityManager->getConnection()->fetchColumn('SELECT COUNT(DISTINCT u.UserID)' . $sql);
        $usrCart = $this->unbuffConn->executeQuery('SELECT DISTINCT u.UserID, c.CartID' . $sql);
        $index = 0;
        $this->write('Found ' . $count . ' users for BalanceWatchCredit gift');

        $giftCount = 1;
        $price = 0;

        while ($uc = $usrCart->fetch()) {
            $cart = $cartRepository->find($uc['CartID']);

            if ($cart->isAwPlusSubscription()
                && !$cart->hasItemsByType([BalanceWatchCredit::TYPE])
                && empty($cart->getScheduledTotal())
                && Cart::PAYMENTTYPE_BUSINESS_BALANCE !== $cart->getPaymenttype()
            ) {
                $cart->addItem((new BalanceWatchCredit())->setCnt($giftCount)->setPrice($price));
                $this->cartManager->save($cart);
            } else {
                continue;
            }

            $this->loggerPayment->info('BalanceWatchManager markAsPaid migration', [
                'userId' => $cart->getUser()->getUserid(),
                'cartId' => $cart->getCartid(),
                'count' => $giftCount,
                'price' => $price,
            ]);

            // Gift Credit
            $this->loggerPayment->info('BalanceWatchManager markAsPaid migration - start', ['UserID' => $cart->getUser()->getUserid(), 'userBalanceWatchCredits' => $cart->getUser()->getBalanceWatchCredits(), 'type' => BalanceWatchCreditsTransaction::TYPE_GIFT]);

            $summaryCredits = $cart->getUser()->getBalanceWatchCredits() + $giftCount;
            $cart->getUser()->setBalanceWatchCredits($summaryCredits);
            $this->entityManager->persist($cart->getUser());

            $this->loggerPayment->info('BalanceWatchManager markAsPaid migration - update userCredit', ['UserID' => $cart->getUser()->getUserid(), 'userBalanceWatchCredits' => $summaryCredits]);
            $balanceWatchCreditTransaction = (new BalanceWatchCreditsTransaction($cart->getUser(), BalanceWatchCreditsTransaction::TYPE_GIFT, $giftCount))->setBalance($summaryCredits);
            $this->entityManager->persist($balanceWatchCreditTransaction);

            $this->loggerPayment->info('BalanceWatchManager markAsPaid migration - end', ['UserID' => $cart->getUser()->getUserid(), 'count' => $giftCount, 'price' => $price, 'balance' => $summaryCredits, 'type' => BalanceWatchCreditsTransaction::TYPE_GIFT]);

            if (++$index % 100 === 0) {
                $this->entityManager->flush();
                $this->entityManager->clear();

                $this->write('Processed ' . $index . ' of ' . $count . ' ...');
            }
        }

        $this->entityManager->flush();
        $this->write('BalanceWatchManager markAsPaid migration - DONE');
    }

    public function down(Schema $schema): void
    {
    }
}
