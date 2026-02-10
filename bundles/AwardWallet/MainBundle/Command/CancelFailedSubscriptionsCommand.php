<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Entity\Repositories\CartRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\Billing\PaypalRestApi;
use AwardWallet\MainBundle\Service\InAppPurchase\AbstractSubscription;
use AwardWallet\MainBundle\Service\InAppPurchase\AppleAppStore\Provider as AppleProvider;
use AwardWallet\MainBundle\Service\InAppPurchase\Billing;
use AwardWallet\MainBundle\Service\InAppPurchase\Exception\QuietVerificationException;
use AwardWallet\MainBundle\Service\InAppPurchase\Exception\VerificationException;
use AwardWallet\MainBundle\Service\InAppPurchase\GooglePlay\Provider as GooglePlay;
use AwardWallet\MainBundle\Service\Paypal\AgreementHack;
use Doctrine\DBAL\Connection;
use PayPal\Api\Agreement;
use PayPal\Api\AgreementStateDescriptor;
use PayPal\Api\AgreementTransaction;
use PayPal\Exception\PayPalConnectionException;
use Psr\Log\LoggerInterface;
use Stripe\StripeClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CancelFailedSubscriptionsCommand extends Command
{
    protected static $defaultName = 'aw:cancel-failed-subscriptions';

    private Connection $connection;
    private LoggerInterface $logger;
    private \AwardWallet\MainBundle\Entity\Repositories\UsrRepository $usrRepository;
    private \Doctrine\ORM\EntityManagerInterface $entityManager;
    private \AwardWallet\MainBundle\Service\Billing\PaypalRestApi $paypalRestApi;
    private \AwardWallet\MainBundle\Service\Billing\ExpirationCalculator $expirationCalculator;
    private \AwardWallet\MainBundle\Service\InAppPurchase\GooglePlay\Provider $googlePlayProvider;
    private \AwardWallet\MainBundle\Service\InAppPurchase\AppleAppStore\Provider $appleProvider;
    private \AwardWallet\MainBundle\Service\InAppPurchase\Billing $billing;
    private StripeClient $stripe;

    public function __construct(
        Connection $connection,
        LoggerInterface $paymentLogger,
        \AwardWallet\MainBundle\Entity\Repositories\UsrRepository $usrRepository,
        \Doctrine\ORM\EntityManagerInterface $entityManager,
        PaypalRestApi $paypalRestApi,
        \AwardWallet\MainBundle\Service\Billing\ExpirationCalculator $expirationCalculator,
        GooglePlay $googlePlayProvider,
        AppleProvider $appleProvider,
        Billing $billing,
        StripeClient $stripe
    ) {
        $this->connection = $connection;
        parent::__construct();
        $this->logger = $paymentLogger;
        $this->usrRepository = $usrRepository;
        $this->entityManager = $entityManager;
        $this->paypalRestApi = $paypalRestApi;
        $this->expirationCalculator = $expirationCalculator;
        $this->googlePlayProvider = $googlePlayProvider;
        $this->appleProvider = $appleProvider;
        $this->billing = $billing;
        $this->stripe = $stripe;
    }

    protected function configure()
    {
        $this
            ->setDescription('Check and cancel paypal, mobile failed subscriptions')
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'dry run, do not modify anything')
            ->addOption('userId', 'u', InputOption::VALUE_REQUIRED, 'userId');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $connection = $this->connection;
        $logger = $this->logger;
        $userRep = $this->usrRepository;
        $em = $this->entityManager;
        /** @var CartRepository $cartRep */
        $cartRep = $em->getRepository(\AwardWallet\MainBundle\Entity\Cart::class);
        $paypal = $this->paypalRestApi;
        $expirationCalculator = $this->expirationCalculator;

        /** @var GooglePlay $googleProvider */
        $googleProvider = $this->googlePlayProvider;
        /** @var AppleProvider $appleProvider */
        $appleProvider = $this->appleProvider;
        $mobileBilling = $this->billing;

        $users = $connection->executeQuery("select UserID, PaypalRecurringProfileID, Subscription, FailedRecurringPayments 
        from Usr 
        where AccountLevel = " . ACCOUNT_LEVEL_FREE . " 
        and Subscription is not null
        " .
            ($input->getOption('userId') ? " and UserID = " . intval($input->getOption('userId')) : ""))->fetchAll(\PDO::FETCH_ASSOC);
        $logger->info("found " . count($users) . " without AWPlus but with active subscription");

        $cancelledProfiles = 0;
        $removedProfiles = 0;
        $warnings = 0;

        foreach ($users as $user) {
            /** @var Usr $userEntity */
            $userEntity = $userRep->find($user['UserID']);
            $expiration = $expirationCalculator->getAccountExpiration($user['UserID']);
            $user['ExpirationDate'] = date("Y-m-d", $expiration['date']);

            if ($expiration['date'] > time()) {
                $logger->critical("user expiration in future, but he is not plus", ["UserID" => $user['UserID']]);

                continue;
            }

            $logger->info("user {$user['UserID']}, expiration: {$user['ExpirationDate']}, subscription: {$user['Subscription']}, agreements: {$user['PaypalRecurringProfileID']}");

            switch ($user['Subscription']) {
                case Usr::SUBSCRIPTION_PAYPAL:
                    $apiContext = $paypal->getApiContext();

                    try {
                        $agreement = AgreementHack::get($user['PaypalRecurringProfileID'], $apiContext);
                        $allTr = Agreement::searchTransactions($agreement->getId(), ['start_date' => date('Y-m-d', strtotime('-15 years')), 'end_date' => date('Y-m-d', strtotime('+5 days'))], $apiContext);
                        $transactions = [];

                        foreach ($allTr->agreement_transaction_list as $tr) {
                            if ($tr->status == 'Completed') {
                                $transactions[] = $tr;
                            }
                        }
                        $user['ProfileState'] = $agreement->state;

                        if ($agreement->state == 'Active') {
                            $user['Failures'] = intval($agreement->agreement_details->failed_payment_count);
                            $cart = $cartRep->getActiveAwSubscription($userEntity);

                            if (empty($cart)) {
                                $logger->warning("no subscription cart detected, was deleted by support?", $user);
                                $warnings++;
                            } else {
                                $user['Duration'] = $cart->getPlusItem()->getDuration();
                                $user['SubscriptionCart'] = $cart->getCartid();

                                if (!empty($transactions)) {
                                    /** @var AgreementTransaction $lastTr */
                                    $lastTr = array_pop($transactions);
                                    $payDate = strtotime($lastTr->time_stamp);
                                    $user['LastPayDate'] = date("Y-m-d", strtotime($payDate));
                                } else {
                                    $payDate = null;
                                }

                                if (!empty($payDate) && strtotime($cart->getPlusItem()->getDuration(), strtotime($user['LastPayDate'])) > strtotime("+7 day")) {
                                    $logger->warning("agreement pay date was less than duration", $user);
                                    $warnings++;

                                    continue 2;
                                } elseif ($user['Failures'] == 0 && $user['FailedRecurringPayments'] < 2) {
                                    $logger->warning("no failures, why cancel?", $user);
                                    $warnings++;

                                    continue 2;
                                }
                            }
                            $agreementStateDescriptor = new AgreementStateDescriptor();
                            $agreementStateDescriptor->setNote("Deleting the agreement by cancel-failed-subscriptions");
                            $logger->info("cancelling agreement", $user);
                            $cancelledProfiles++;

                            if (!$input->getOption('dry-run')) {
                                $agreement->cancel($agreementStateDescriptor, $apiContext);
                            } else {
                                $logger->info("dry run, actually skipped action");
                            }
                        }
                    } catch (PayPalConnectionException $e) {
                        $data = @json_decode($e->getData(), true);

                        if (!empty($data['name']) && $data['name'] == 'INVALID_PROFILE_ID') {
                            $logger->info("profile not found", $user);
                        } else {
                            throw $e;
                        }
                    }

                    break;

                case Usr::SUBSCRIPTION_SAVED_CARD:
                    $logger->info("downgrading user with saved card, deleting card");

                    try {
                        if (!$input->getOption('dry-run')) {
                            $paypal->deleteSavedCard($user['PaypalRecurringProfileID']);
                        } else {
                            $logger->info("dry run, actually skipped action");
                        }
                    } catch (PayPalConnectionException $e) {
                        $data = @json_decode($e->getData(), true);

                        if (!empty($data['name']) && $data['name'] == 'INVALID_RESOURCE_ID') {
                            $logger->critical("card already deleted");
                        } else {
                            throw $e;
                        }
                    }

                    break;

                case Usr::SUBSCRIPTION_STRIPE:
                    $logger->info("downgrading user with stripe, deleting payment method {$user['PaypalRecurringProfileID']}", ["UserID" => $user['UserID']]);

                    if (!$input->getOption('dry-run')) {
                        $logger->info("should detach payment method");
                    // commented out due to possible bugs, to recover payment method in case of bugs
                    // $this->stripe->paymentMethods->detach($user['PaypalRecurringProfileID']);
                    } else {
                        $logger->info("dry run, actually skipped action");
                    }

                    break;

                case Usr::SUBSCRIPTION_MOBILE:
                    $logger->info("mobile subscription detected, checking");

                    $skip = false;
                    $found = false;

                    try {
                        $logger->info("checking apple subscriptions");
                        $appleProvider->scanSubscriptions($userEntity, $mobileBilling);
                        $em->refresh($userEntity);

                        if ($userEntity->isAwPlus()) {
                            continue 2;
                        }

                        /** @var AbstractSubscription[] $appleSubscriptions */
                        $appleSubscriptions = $this->getActiveMobileSubscription($appleProvider->findSubscriptions($userEntity));

                        if (count($appleSubscriptions) > 0) {
                            $subscr = array_shift($appleSubscriptions);
                            $logger->critical("Free user: apple subscription found", ['subscription' => (string) $subscr]);
                            $found = true;
                        }
                    } catch (VerificationException $e) {
                        if (!$this->handleVerificationException($logger, $e)) {
                            $skip = true;
                        }
                    }

                    try {
                        $logger->info("checking google play subscriptions");
                        $googleProvider->scanSubscriptions($userEntity, $mobileBilling);
                        $em->refresh($userEntity);

                        if ($userEntity->isAwPlus()) {
                            continue 2;
                        }

                        /** @var AbstractSubscription[] $googleSubscriptions */
                        $googleSubscriptions = $this->getActiveMobileSubscription($googleProvider->findSubscriptions($userEntity));

                        if (count($googleSubscriptions) > 0) {
                            $subscr = array_shift($googleSubscriptions);
                            $logger->critical("Free user: google play subscription found", ['subscription' => (string) $subscr]);
                            $found = true;
                        }
                    } catch (VerificationException $e) {
                        if (!$this->handleVerificationException($logger, $e)) {
                            $skip = true;
                        }
                    }

                    if ($skip && !$found) {
                        continue 2;
                    }

                    break;

                default:
                    $logger->critical("unknown subscription type", ["UserID" => $user['UserID'], "Subscription" => $user['Subscription']]);

                    continue 2;
            }

            $logger->info("removing user subscription", $user);

            if (!$input->getOption('dry-run')) {
                $connection->executeUpdate("update Usr set PaypalRecurringProfileID = null, Subscription = null, SubscriptionType = null 
                where UserID = :userId", ['userId' => $user['UserID']]);
            } else {
                $logger->info("dry run, actually skipped action");
            }
            $removedProfiles++;
        }
        $logger->info("done, processed: " . count($users) . ", cancelled: $cancelledProfiles, removed: $removedProfiles, warnings: $warnings");

        return 0;
    }

    /**
     * @param AbstractSubscription[] $purchases
     */
    private function getActiveMobileSubscription(array $purchases)
    {
        $date = new \DateTime('+1 day');

        return array_filter($purchases, function ($purchase) use ($date) {
            /** @var AbstractSubscription $purchase */
            return $purchase instanceof AbstractSubscription && !$purchase->isCanceled() && $purchase->getExpiresDate() > $date;
        });
    }

    private function handleVerificationException(LoggerInterface $logger, VerificationException $e)
    {
        if ($e instanceof QuietVerificationException) {
            $logger->warning(sprintf("In-App Purchase verification exception: %s", $e->getMessage()), $e->getContext());
        } else {
            $logger->critical(sprintf("In-App Purchase verification exception: %s", $e->getMessage()), $e->getContext());
        }

        if ($e->isTemporary()) {
            $logger->info("temporary verification error, skip");

            return false;
        }

        return true;
    }
}
