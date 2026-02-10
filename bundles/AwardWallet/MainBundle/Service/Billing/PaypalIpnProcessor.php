<?php

namespace AwardWallet\MainBundle\Service\Billing;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem\SubscriptionItems;
use AwardWallet\MainBundle\Entity\Repositories\CartRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\Cart\SubscriptionPeriod;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class PaypalIpnProcessor
{
    private Connection $connection;
    private LoggerInterface $logger;
    private CartRepository $cartRepository;
    private PlusManager $plusManager;

    public function __construct(Connection $connection, LoggerInterface $paymentLogger, CartRepository $cartRepository, PlusManager $plusManager)
    {
        $this->connection = $connection;
        $this->logger = $paymentLogger;
        $this->cartRepository = $cartRepository;
        $this->plusManager = $plusManager;
    }

    public function processTransaction(Usr $user, string $transactionId, string $profileId, ?string $paymentCycle, float $amount, ?int $subscriptionCartId = null): bool
    {
        $this->logger->info("searching for transaction $transactionId from profile $profileId, user: {$user->getId()}, payment cycle: {$paymentCycle}, amount: $amount");

        if ($this->transactionAlreadyProcessed($user, $transactionId)) {
            return true;
        }

        if ($this->updateFirstSubscription($user, $profileId, $transactionId)) {
            return true;
        }

        if ($subscriptionCartId) {
            $cart = $this->cartRepository->find($subscriptionCartId);
        } else {
            $cart = $this->cartRepository->getActiveAwSubscription($user);
        }

        if (empty($cart)) {
            $this->logger->critical("cart with subscription not found", ['recurring_payment_id' => $profileId, 'txn_id' => $transactionId, 'UserID' => $user->getUserid()]);

            return false;
        }

        if (!in_array($cart->getPaymenttype(), [Cart::PAYMENTTYPE_CREDITCARD, Cart::PAYMENTTYPE_PAYPAL])) {
            $this->logger->critical("cart found, but payment type is invalid", ['recurring_payment_id' => $profileId, 'txn_id' => $transactionId, 'UserID' => $user->getUserid(), 'cart' => $cart->getCartid()]);

            return false;
        }

        $subscriptionItem = $cart->getSubscriptionItem();

        if ($subscriptionItem === null) {
            // fallback for old carts
            $subscriptionItem = $cart->getPlusItem();
        }

        if (empty($subscriptionItem)) {
            $this->logger->critical("cart found, but does not contain subscription item", ['recurring_payment_id' => $profileId, 'txn_id' => $transactionId, 'cart' => $cart->getCartid()]);

            return false;
        }

        $period = $paymentCycle;

        if ($period == 'every 6 Months') {
            $duration = SubscriptionPeriod::DURATION_6_MONTHS;
        } elseif ($period == 'every 12 Months') {
            $duration = SubscriptionPeriod::DURATION_1_YEAR;
        } else {
            $this->logger->critical("invalid period", ['recurring_payment_id' => $profileId, 'txn_id' => $transactionId, 'cart' => $cart->getCartid(), 'ipn_period' => $period]);

            return false;
        }

        if ($duration != $subscriptionItem->getDuration()) {
            $this->logger->critical("ipn period does not match cart period", ['recurring_payment_id' => $profileId, 'txn_id' => $transactionId, 'cart' => $cart->getCartid(), 'ipn_period' => $period, 'cart_period' => $subscriptionItem->getDuration()]);

            return false;
        }

        $this->plusManager->repeatCartSubscription($cart, $transactionId, round($amount, 2), Cart::SOURCE_RECURRING, $duration);

        return true;
    }

    private function transactionAlreadyProcessed(Usr $user, string $transactionId): bool
    {
        $cartId = $this->connection->executeQuery("
            select c.CartID from Cart c
            where c.PayDate is not null 
            and c.UserID = :userId and c.BillingTransactionID = :tranId",
            ["userId" => $user->getUserid(), "tranId" => $transactionId]
        )->fetchColumn();

        if ($cartId !== false) {
            $this->logger->info("cart already processed, cart id: $cartId");

            return true;
        }

        return false;
    }

    private function updateFirstSubscription(Usr $user, $profileId, $transactionId)
    {
        $cartId = $this->connection->executeQuery("select c.CartID from Cart c
            join CartItem ci on c.CartID = ci.CartID
            where c.PayDate is not null and c.PaymentType = " . PAYMENTTYPE_PAYPAL . "
            and ci.ScheduledDate is null 
            and ci.TypeID in (" . implode(", ", SubscriptionItems::getTypes()) . ")
            and c.UserID = :userId and c.BillingTransactionID = :profileId", ["userId" => $user->getUserid(), "profileId" => $profileId]
        )->fetchColumn();

        if (!empty($cartId)) {
            $this->logger->info("updating transactionid for first recurring cart", ["UserID" => $user->getUserid(), "recurring_payment_id" => $profileId, "CartID" => $cartId, "BillingTransactionID" => $transactionId]);
            $this->connection->executeUpdate("update Cart set BillingTransactionID = :txId where CartID = :cartId",
                ["txId" => $transactionId, "cartId" => $cartId]);

            return true;
        }

        return false;
    }
}
