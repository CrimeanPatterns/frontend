<?php

namespace AwardWallet\MainBundle\Service\Billing;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Event\CartMarkPaidEvent;
use AwardWallet\MainBundle\Globals\LoggerContext\ContextAwareLoggerWrapper;
use Doctrine\ORM\EntityManagerInterface;
use PayPal\EBLBaseComponents\ManageRecurringPaymentsProfileStatusRequestDetailsType;
use PayPal\PayPalAPI\GetRecurringPaymentsProfileDetailsReq;
use PayPal\PayPalAPI\GetRecurringPaymentsProfileDetailsRequestType;
use PayPal\PayPalAPI\ManageRecurringPaymentsProfileStatusReq;
use PayPal\PayPalAPI\ManageRecurringPaymentsProfileStatusRequestType;
use PayPal\Service\PayPalAPIInterfaceServiceService;
use Psr\Log\LoggerInterface;

class PaypalCartPaidListener
{
    private LoggerInterface $logger;
    private PayPalAPIInterfaceServiceService $paypal;
    private EntityManagerInterface $entityManager;

    public function __construct(LoggerInterface $paymentLogger, PaypalSoapApi $paypalSoapApi, EntityManagerInterface $entityManager)
    {
        $this->logger = new ContextAwareLoggerWrapper($paymentLogger);
        $this->logger->pushContext(['worker' => 'PaypalCartPaidListener']);
        $this->paypal = $paypalSoapApi->getPaypalService();
        $this->entityManager = $entityManager;
    }

    public function onCartMarkPaid(CartMarkPaidEvent $event): void
    {
        $cart = $event->getCart();
        $user = $cart->getUser();

        if ($user->getSubscription() !== Usr::SUBSCRIPTION_PAYPAL) {
            return;
        }

        if ($user->getPaypalrecurringprofileid() === null) {
            $this->logger->critical("user has paypal subscription, but recurring profile id is not set", ["UserID" => $user->getId()]);

            return;
        }

        if ($user->getPaypalSuspendedUntilDate() !== null && $user->getPaypalSuspendedUntilDate()->getTimestamp() < time()) {
            $this->logger->critical("paypal subscription is suspended until {$user->getPaypalSuspendedUntilDate()->format("Y-m-d")}, but it should already unpaused", ["UserID" => $user->getId()]);

            return;
        }

        if ($user->getPaypalSuspendedUntilDate() !== null) {
            $this->logger->info("paypal subscription is already suspended until {$user->getPaypalSuspendedUntilDate()->format("Y-m-d")}", ["UserID" => $user->getId()]);

            return;
        }

        if ($user->getPlusExpirationDate() === null) {
            $this->logger->critical("plus expiration date is null, but he has paypal subscription", ["UserID" => $user->getId()]);

            return;
        }

        if ($user->getAccountlevel() === ACCOUNT_LEVEL_FREE) {
            $this->logger->critical("user is free, but he has paypal subscription", ["UserID" => $user->getId()]);

            return;
        }

        //        if ($cart->getPaymenttype() === Cart::PAYMENTTYPE_PAYPAL) {
        //            // this listener should suspend PayPal profile only when other payment type occurred
        //            // when PayPal subscription is active
        //            // there was bug, when freshly made PayPal payment suspended itself
        //            // could not reproduce it in test, trying to fix it that way
        //            $this->logger->info("ignoring paypal payment", ["UserID" => $user->getId()]);
        //
        //            return;
        //        }

        $this->logger->info("getting next billing date for paypal profile {$user->getPaypalrecurringprofileid()}", ["UserID" => $user->getId()]);
        $req = new GetRecurringPaymentsProfileDetailsReq();
        $req->GetRecurringPaymentsProfileDetailsRequest = new GetRecurringPaymentsProfileDetailsRequestType();
        $req->GetRecurringPaymentsProfileDetailsRequest->ProfileID = $user->getPaypalrecurringprofileid();

        try {
            $response = $this->paypal->GetRecurringPaymentsProfileDetails($req);
        } catch (\Exception $exception) {
            $this->logger->critical("error reading paypal profile: {$exception->getMessage()}", ["UserID" => $user->getId()]);

            return;
        }

        if ($response->Ack !== 'Success') {
            $this->logger->critical("error reading paypal profile: " . json_encode($response), ["UserID" => $user->getId()]);

            return;
        }

        if (!in_array($response->GetRecurringPaymentsProfileDetailsResponseDetails->ProfileStatus, ["ActiveProfile", "Suspended", "Pending"])) {
            $this->logger->critical("paypal profile {$user->getPaypalrecurringprofileid()} has not known state: {$response->GetRecurringPaymentsProfileDetailsResponseDetails->ProfileStatus}", ["UserID" => $user->getId()]);

            return;
        }

        // TODO: handle agreements with future start date, we should correct start date

        $nextBillingDate = new \DateTime($response->GetRecurringPaymentsProfileDetailsResponseDetails->RecurringPaymentsSummary->NextBillingDate);

        if ($user->getNextBillingDate() === null || $user->getNextBillingDate()->format("Y-m-d") !== $nextBillingDate->format("Y-m-d")) {
            $this->logger->info("setting next billing date for paypal profile {$user->getPaypalrecurringprofileid()} to {$nextBillingDate->format("Y-m-d")}", ["UserID" => $user->getId()]);
            $user->setNextBillingDate($nextBillingDate);
            $this->entityManager->flush();
        } else {
            $this->logger->info("user already has correct next billing date {$user->getNextBillingDate()->format("Y-m-d")}", ["UserID" => $user->getId()]);
        }

        $daysBetweenNextChargeAndPlusExpiration = round(($user->getPlusExpirationDate()->getTimestamp() - $nextBillingDate->getTimestamp()) / 86400);
        $this->logger->info("plus expiration date: {$user->getPlusExpirationDate()->format("Y-m-d")}, account level: {$user->getAccountlevel()}, days between: {$daysBetweenNextChargeAndPlusExpiration}", ["UserID" => $user->getId()]);

        $daysBeforeNextBillingDate = round(($nextBillingDate->getTimestamp() - strtotime("today")) / 86400);

        if ($daysBeforeNextBillingDate < 10) {
            $this->logger->info("next billing date is too near", ["UserID" => $user->getId()]);

            return;
        }

        if ($daysBetweenNextChargeAndPlusExpiration < 300) {
            $this->logger->info("next billing date seems ok", ["UserID" => $user->getId()]);

            return;
        }

        $suspendUntil = (clone $user->getPlusExpirationDate())->modify("-10 day");

        if ($user->getPaypalSuspendedUntilDate() && $user->getPaypalSuspendedUntilDate()->format("Y-m-d") === $suspendUntil->format("Y-m-d")) {
            $this->logger->info("user already suspended until {$suspendUntil->format("Y-m-d")}");

            return;
        }

        $this->logger->info("suspending paypal subscription until {$suspendUntil->format("Y-m-d")}, old suspend date: " . ($user->getPaypalSuspendedUntilDate() ? $user->getPaypalSuspendedUntilDate()->format("Y-m-d") : "null"), ["UserID" => $user->getId()]);

        if ($response->GetRecurringPaymentsProfileDetailsResponseDetails->ProfileStatus === "Suspended") {
            $this->logger->info("paypal profile already suspended", ["UserID" => $user->getId()]);
            $user->setPaypalSuspendedUntilDate($suspendUntil);
            $this->entityManager->flush();

            return;
        }

        if (!$this->suspendProfile($user->getPaypalrecurringprofileid(), $user->getId())) {
            return;
        }

        $user->setPaypalSuspendedUntilDate($suspendUntil);
        $this->entityManager->flush();
        $this->logger->info("paypal suspended", ["UserID" => $user->getId()]);
    }

    private function suspendProfile(string $profileId, int $userId): bool
    {
        $req = new ManageRecurringPaymentsProfileStatusReq();
        $req->ManageRecurringPaymentsProfileStatusRequest = new ManageRecurringPaymentsProfileStatusRequestType();
        $req->ManageRecurringPaymentsProfileStatusRequest->ManageRecurringPaymentsProfileStatusRequestDetails = new ManageRecurringPaymentsProfileStatusRequestDetailsType();
        $req->ManageRecurringPaymentsProfileStatusRequest->ManageRecurringPaymentsProfileStatusRequestDetails->ProfileID = $profileId;
        $req->ManageRecurringPaymentsProfileStatusRequest->ManageRecurringPaymentsProfileStatusRequestDetails->Action = "Suspend";

        try {
            $response = $this->paypal->ManageRecurringPaymentsProfileStatus($req);
        } catch (\Exception $exception) {
            $this->logger->critical("error suspending paypal profile: {$exception->getMessage()}", ["UserID" => $userId]);

            return false;
        }

        if ($response->Ack !== 'Success') {
            $this->logger->critical("error suspending paypal profile: " . json_encode($response), ["UserID" => $userId]);

            return false;
        }

        return true;
    }
}
