<?php

namespace AwardWallet\MainBundle\Service\Billing;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Globals\Cart\Manager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Stripe\Charge;

class StripeCartServices
{
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    private Manager $cartManager;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $paymentLogger, Manager $cartManager)
    {
        $this->entityManager = $entityManager;
        $this->logger = $paymentLogger;
        $this->cartManager = $cartManager;
    }

    public function findCart(string $txId, string $description): ?Cart
    {
        /** @var Cart $cartByTx */
        $cartByTx = $this->entityManager->createQuery(
            "select c from AwardWalletMainBundle:Cart c where c.billingtransactionid = :txId
            and c.paydate is not null"
        )
            ->setParameter("txId", $txId)
            ->getOneOrNullResult()
        ;

        if ($cartByTx !== null && $cartByTx->getPaydate() !== null) {
            return $cartByTx;
        }

        $cartById = null;

        if (preg_match('/^Order #(\d+)$/ims', $description, $matches)) {
            $cartById = $this->entityManager->find(Cart::class, $matches[1]);
        }

        if ($cartByTx === $cartById) {
            return $cartByTx;
        }

        if ($cartByTx !== null && $cartById === null) {
            return $cartByTx;
        }

        if ($cartByTx === null && $cartById !== null) {
            return $cartById;
        }

        if ($cartByTx->isPaid() && !$cartById->isPaid()) {
            return $cartByTx;
        }

        if (!$cartByTx->isPaid() && $cartById->isPaid()) {
            return $cartById;
        }

        return $cartById;
    }

    public function deleteRefundedCart(Charge $charge, Cart $cart, bool $apply)
    {
        $context = ['UserID' => $cart->getUser() ? $cart->getUser()->getId() : null];

        $this->logger->warning("refunded cart found, should delete it: {$charge->payment_intent}, customer {$charge->customer}, cart {$cart->getCartid()}", $context);

        if ($apply) {
            $this->cartManager->refund($cart);
        } else {
            $this->logger->info("dry-run", $context);
        }
    }
}
