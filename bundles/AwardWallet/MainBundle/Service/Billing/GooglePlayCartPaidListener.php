<?php

namespace AwardWallet\MainBundle\Service\Billing;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Event\CartMarkPaidEvent;
use AwardWallet\MainBundle\Service\InAppPurchase\GooglePlay\Provider;
use Doctrine\ORM\EntityManagerInterface;
use Google\Service\AndroidPublisher\Resource\PurchasesSubscriptions;
use Psr\Log\LoggerInterface;

class GooglePlayCartPaidListener
{
    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;
    private PurchasesSubscriptions $purchasesSubscriptions;
    private Provider $googlePlayProvider;

    public function __construct(LoggerInterface $paymentLogger, PurchasesSubscriptions $googlePurchasesSubscriptions, EntityManagerInterface $entityManager, Provider $googlePlayProvider)
    {
        $this->logger = $paymentLogger;
        $this->entityManager = $entityManager;
        $this->purchasesSubscriptions = $googlePurchasesSubscriptions;
        $this->googlePlayProvider = $googlePlayProvider;
    }

    public function onCartMarkPaid(CartMarkPaidEvent $event): void
    {
        $cart = $event->getCart();
        $user = $cart->getUser();

        if ($user->getSubscription() !== Usr::SUBSCRIPTION_MOBILE || $user->getIosReceipt() !== null) {
            return;
        }

        if ($user->getLastSubscriptionCartItem() === null) {
            $this->logger->critical('LastSubscriptionCartItemID is null', ['UserID' => $user->getId()]);

            return;
        }

        if ($user->getLastSubscriptionCartItem()->getCart()->getPaymenttype() !== Cart::PAYMENTTYPE_ANDROIDMARKET) {
            return;
        }

        if ($user->getPaypalSuspendedUntilDate() !== null && $user->getPaypalSuspendedUntilDate()->getTimestamp() < time()) {
            $this->logger->critical("google play subscription is already suspended until {$user->getPaypalSuspendedUntilDate()->format("Y-m-d")}, but it should already be unpaused", ["UserID" => $user->getId()]);

            return;
        }

        if ($user->getPaypalSuspendedUntilDate() !== null) {
            $this->logger->info("google play subscription is already suspended until {$user->getPaypalSuspendedUntilDate()->format("Y-m-d")}", ["UserID" => $user->getId()]);

            return;
        }

        if ($user->getPlusExpirationDate() === null) {
            $this->logger->critical("plus expiration date is null, but he has google play subscription", ["UserID" => $user->getId()]);

            return;
        }

        if ($user->getAccountlevel() === ACCOUNT_LEVEL_FREE) {
            $this->logger->critical("user is free, but he has google play subscription", ["UserID" => $user->getId()]);

            return;
        }

        $subscriptionCart = $user->getLastSubscriptionCartItem()->getCart();
        $txId = $subscriptionCart->getBillingtransactionid();
        $platformProductId = $this->googlePlayProvider->getPlatformProductIdByCart($subscriptionCart);

        $this->logger->info("getting status for google subscription {$txId}", ["UserID" => $user->getId()]);

        try {
            $subscription = $this->purchasesSubscriptions->get(Provider::BUNDLE_ID, $platformProductId, $subscriptionCart->getPurchaseToken());
        } catch (\Exception $exception) {
            $this->logger->critical("error reading google subscription: {$exception->getMessage()}", ["UserID" => $user->getId()]);

            return;
        }

        if (!$subscription->autoRenewing) {
            $this->logger->info("google subscription is not auto renewing, ignoring", ["UserID" => $user->getId()]);

            return;
        }

        $nextBillingDate = new \DateTime(date("Y-m-d", $subscription->expiryTimeMillis / 1000));

        if ($user->getNextBillingDate() === null || $user->getNextBillingDate()->format("Y-m-d") !== $nextBillingDate->format("Y-m-d")) {
            $this->logger->info("setting next billing date for google play subscription {$txId} to {$nextBillingDate->format("Y-m-d")}", ["UserID" => $user->getId()]);
            $user->setNextBillingDate($nextBillingDate);
            $this->entityManager->flush();
        } else {
            $this->logger->info("user already has correct next billing date {$user->getNextBillingDate()->format("Y-m-d")}", ["UserID" => $user->getId()]);
        }

        $daysBetweenNextChargeAndPlusExpiration = round(($user->getPlusExpirationDate()->getTimestamp() - $nextBillingDate->getTimestamp()) / 86400);
        $this->logger->info("plus expiration date: {$user->getPlusExpirationDate()->format("Y-m-d")}, account level: {$user->getAccountlevel()}, days between: {$daysBetweenNextChargeAndPlusExpiration}", ["UserID" => $user->getId()]);

        if ($daysBetweenNextChargeAndPlusExpiration < 90) {
            $this->logger->info("next billing date seems ok", ["UserID" => $user->getId()]);

            return;
        }

        $suspendUntil = (clone $user->getPlusExpirationDate());

        if ($user->getPaypalSuspendedUntilDate() && $user->getPaypalSuspendedUntilDate()->format("Y-m-d") === $suspendUntil->format("Y-m-d")) {
            $this->logger->info("user already suspended until {$suspendUntil->format("Y-m-d")}");

            return;
        }

        $this->logger->info("suspending google play subscription until {$suspendUntil->format("Y-m-d")}, old suspend date: " . ($user->getPaypalSuspendedUntilDate() ? $user->getPaypalSuspendedUntilDate()->format("Y-m-d") : "null"), ["UserID" => $user->getId()]);

        if ($subscription->autoResumeTimeMillis !== null && ($user->getPaypalSuspendedUntilDate() === null || $user->getPaypalSuspendedUntilDate()->format("Y-m-d") !== date("Y-m-d", $subscription->autoResumeTimeMillis / 1000))) {
            $this->logger->info("google subscription is already paused until " . date("Y-m-d", $subscription->autoResumeTimeMillis / 1000) . ", updating PaypalSuspendedUntilDate field", ["UserID" => $user->getId()]);
            $user->setPaypalSuspendedUntilDate(new \DateTime(date("Y-m-d", $subscription->autoResumeTimeMillis / 1000)));
            $this->entityManager->flush();
        }

        if ($subscription->autoResumeTimeMillis !== null) {
            $this->logger->info("google subscription is already paused until " . date("Y-m-d", $subscription->autoResumeTimeMillis / 1000), ["UserID" => $user->getId()]);

            return;
        }

        $currentExpiryTimeMs = (int) $subscription->expiryTimeMillis;

        while (date("Y-m-d", $currentExpiryTimeMs / 1000) < $suspendUntil->format("Y-m-d")) {
            $newExpiryTime = $suspendUntil->getTimestamp();
            $increaseInDays = round(($newExpiryTime - $currentExpiryTimeMs / 1000) / 86400);

            if ($increaseInDays > 180) {
                $this->logger->info("expiry increase is too big: $increaseInDays days, will increase on 180 days step", ["UserID" => $user->getId()]);
                $newExpiryTime = strtotime("+180 day", $currentExpiryTimeMs / 1000);
            }
            $request = new \Google_Service_AndroidPublisher_SubscriptionPurchasesDeferRequest();
            $deferralInfo = new \Google_Service_AndroidPublisher_SubscriptionDeferralInfo();
            $deferralInfo->setDesiredExpiryTimeMillis($newExpiryTime * 1000);
            $deferralInfo->setExpectedExpiryTimeMillis((int) $currentExpiryTimeMs);
            $request->setDeferralInfo($deferralInfo);

            try {
                $this->purchasesSubscriptions->defer(Provider::BUNDLE_ID, $platformProductId, $subscriptionCart->getPurchaseToken(), $request);
                $this->logger->info("subscription deferred until " . date("Y-m-d", $newExpiryTime), ["UserID" => $user->getId()]);
                $currentExpiryTimeMs = $newExpiryTime * 1000;
            } catch (\Exception $exception) {
                $this->logger->critical("failed to defer google subscription: {$exception->getMessage()}", ["UserID" => $user->getId()]);

                return;
            }
        }

        $user->setPaypalSuspendedUntilDate($suspendUntil);
        $this->entityManager->flush();
        $this->logger->info("google play suspended", ["UserID" => $user->getId()]);
    }
}
