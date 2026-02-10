<?php

namespace AwardWallet\MainBundle\Service\Billing;

use AwardWallet\MainBundle\Service\InAppPurchase\AppleAppStore\Provider;
use AwardWallet\MainBundle\Service\InAppPurchase\Billing;
use AwardWallet\MainBundle\Service\InAppPurchase\Exception\VerificationException;
use AwardWallet\MainBundle\Service\TaskScheduler\ConsumerInterface;
use AwardWallet\MainBundle\Service\TaskScheduler\TaskInterface;
use AwardWallet\MainBundle\Service\TaskScheduler\TaskNeedsRetryException;
use Psr\Log\LoggerInterface;

class AppleStoreCallbackConsumer implements ConsumerInterface
{
    private LoggerInterface $logger;
    private Provider $provider;
    private Billing $billing;

    public function __construct(
        LoggerInterface $paymentLogger,
        Provider $provider,
        Billing $billing
    ) {
        $this->logger = $paymentLogger;
        $this->provider = $provider;
        $this->billing = $billing;
    }

    /**
     * @param AppleStoreCallbackTask $task
     */
    public function consume(TaskInterface $task): void
    {
        $this->provider->setUseLatestMobileVersion(true);

        try {
            $purchases = $this->provider->validate([
                'type' => 'ios-appstore',
                'transactionReceipt' => $task->getReceipt(),
            ]);

            $this->logger->info(\sprintf("purchases: %d", \count($purchases)));

            foreach ($purchases as $purchase) {
                $this->billing->processing($purchase);
            }
        } catch (VerificationException $e) {
            $this->logger->warning(\sprintf("In-App Purchase verification exception: %s", $e->getMessage()), $e->getContext());

            if ($e->isTemporary()) {
                $this->logger->info("temporary verification error, skip");

                throw new TaskNeedsRetryException(random_int(30, 180));
            }

            throw $e;
        }
    }
}
