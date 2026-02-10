<?php

namespace AwardWallet\Tests\Unit\AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Command\CancelFailedSubscriptionsCommand;
use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\Billing\ExpirationCalculator;
use AwardWallet\MainBundle\Service\InAppPurchase\AbstractSubscription;
use AwardWallet\MainBundle\Service\InAppPurchase\AppleAppStore\Provider as AppleStore;
use AwardWallet\MainBundle\Service\InAppPurchase\Exception\VerificationException;
use AwardWallet\MainBundle\Service\InAppPurchase\GooglePlay\Provider as GooglePlay;
use AwardWallet\MainBundle\Service\InAppPurchase\Subscription\AwPlus as SubscriptionAwPlus;
use AwardWallet\Tests\Unit\CommandTester;

/**
 * @group frontend-unit
 * @group billing
 * @group mobile/billing
 */
class CancelFailedSubscriptionsCommandTest extends CommandTester
{
    public function _before()
    {
        parent::_before();

        $this->user->setAccountlevel(ACCOUNT_LEVEL_FREE);
        $this->em->flush();
    }

    public function _after()
    {
        $this->container->get("monolog.logger.payment")->popHandler();
        $this->cleanCommand();
        parent::_after();
    }

    public function testPlusUserWithMobileSubscriptionShouldNotBeProcessed()
    {
        $this->user->setAccountlevel(ACCOUNT_LEVEL_AWPLUS);
        $this->user->setSubscription(Usr::SUBSCRIPTION_MOBILE);
        $this->em->flush();
        $this->initCommand($this->container->get(CancelFailedSubscriptionsCommand::class));
        $this->container->get("monolog.logger.payment")->pushHandler($this->logs);
        $this->executeCommand();
        $this->logContains("done, processed: 0, cancelled: 0, removed: 0, warnings: 0");
    }

    public function testPlusUserWithUnknownSubscriptionShouldNotBeProcessed()
    {
        $this->user->setAccountlevel(ACCOUNT_LEVEL_AWPLUS);
        $this->user->setSubscription(Usr::SUBSCRIPTION_BITCOIN);
        $this->em->flush();
        $this->initCommand($this->container->get(CancelFailedSubscriptionsCommand::class));
        $this->container->get("monolog.logger.payment")->pushHandler($this->logs);
        $this->executeCommand();
        $this->logContains("done, processed: 0, cancelled: 0, removed: 0, warnings: 0");
    }

    public function testFreeUserButWithExpirationInFutureShouldBeSkipped()
    {
        $mock = $this->mockServiceWithBuilder(ExpirationCalculator::class);
        $mock->method('getAccountExpiration')->willReturn(['date' => strtotime("+1 month"), "lastPrice" => 5]);
        $this->user->setSubscription(Usr::SUBSCRIPTION_MOBILE);
        $this->em->flush();
        $this->initCommand($this->container->get(CancelFailedSubscriptionsCommand::class));
        $this->container->get("monolog.logger.payment")->pushHandler($this->logs);
        $this->executeCommand();
        $this->logContains("user expiration in future, but he is not plus");
        $this->logContains("done, processed: 1, cancelled: 0, removed: 0, warnings: 0");
    }

    public function testFreeUserWithoutSubscriptionShouldNotBeProcessed()
    {
        $this->user->setSubscription(null);
        $this->em->flush();
        $this->initCommand($this->container->get(CancelFailedSubscriptionsCommand::class));
        $this->container->get("monolog.logger.payment")->pushHandler($this->logs);
        $this->executeCommand();
        $this->logContains("done, processed: 0, cancelled: 0, removed: 0, warnings: 0");
    }

    public function testFreeUserWithUnknownSubscriptionShouldSendCriticalMessage()
    {
        $this->user->setSubscription(Usr::SUBSCRIPTION_BITCOIN);
        $this->em->flush();
        $this->initCommand($this->container->get(CancelFailedSubscriptionsCommand::class));
        $this->container->get("monolog.logger.payment")->pushHandler($this->logs);
        $this->executeCommand();
        $this->logContains("unknown subscription type");
        $this->logContains("done, processed: 1, cancelled: 0, removed: 0, warnings: 0");
    }

    /**
     * @dataProvider platformProvider
     */
    public function testFreeUserWithMobileSubscriptionAndWithoutProviderSubscriptionShouldBeProcessed($serviceName)
    {
        $mock = $this->mockServiceWithBuilder($serviceName);
        $mock->method('findSubscriptions')->willReturn([]);
        $this->aw->addUserPayment($this->user->getId(), Cart::PAYMENTTYPE_APPSTORE, new AwPlusSubscription(), null, new \DateTime("-3 year"));
        $this->user->setSubscription(Usr::SUBSCRIPTION_MOBILE);
        $this->em->flush();
        $this->initCommand($this->container->get(CancelFailedSubscriptionsCommand::class));
        $this->container->get("monolog.logger.payment")->pushHandler($this->logs);
        $this->executeCommand();
        $this->logContains("checking apple subscriptions");
        $this->logContains("checking google play subscriptions");
        $this->logContains("done, processed: 1, cancelled: 0, removed: 1, warnings: 0");
    }

    /**
     * @dataProvider platformProvider
     */
    public function testFreeUserWithMobileSubscriptionAndWithProviderSubscriptionShouldSendCriticalMessage($serviceName)
    {
        $mock = $this->mockServiceWithBuilder($serviceName);
        $mock->method('findSubscriptions')->willReturn([$this->getPurchase(
            new \DateTime('+1 year'),
            $serviceName,
            false
        )]);
        $this->aw->addUserPayment($this->user->getId(), Cart::PAYMENTTYPE_APPSTORE, new AwPlusSubscription(), null, new \DateTime("-3 year"));
        $this->user->setSubscription(Usr::SUBSCRIPTION_MOBILE);
        $this->em->flush();
        $this->initCommand($this->container->get(CancelFailedSubscriptionsCommand::class));
        $this->container->get("monolog.logger.payment")->pushHandler($this->logs);
        $this->executeCommand();
        $this->logContains("subscription found");
        $this->logContains("done, processed: 1, cancelled: 0, removed: 1, warnings: 0");
    }

    /**
     * @dataProvider platformProvider
     */
    public function testFreeUserWithMobileSubscriptionAndWithCancelledProviderSubscriptionShouldBeProcessed($serviceName)
    {
        $mock = $this->mockServiceWithBuilder($serviceName);
        $mock->method('findSubscriptions')->willReturn([$this->getPurchase(
            new \DateTime('+1 year'),
            $serviceName,
            true
        )]);
        $this->aw->addUserPayment($this->user->getId(), Cart::PAYMENTTYPE_APPSTORE, new AwPlusSubscription(), null, new \DateTime("-3 year"));
        $this->user->setSubscription(Usr::SUBSCRIPTION_MOBILE);
        $this->em->flush();
        $this->initCommand($this->container->get(CancelFailedSubscriptionsCommand::class));
        $this->container->get("monolog.logger.payment")->pushHandler($this->logs);
        $this->executeCommand();
        $this->logNotContains("subscription found");
        $this->logContains("done, processed: 1, cancelled: 0, removed: 1, warnings: 0");
    }

    /**
     * @dataProvider platformProvider
     */
    public function testFreeUserWithMobileSubscriptionAndWithExpiredProviderSubscriptionShouldBeProcessed($serviceName)
    {
        $mock = $this->mockServiceWithBuilder($serviceName);
        $mock->method('findSubscriptions')->willReturn([$this->getPurchase(
            new \DateTime('-1 month'),
            $serviceName,
            false
        )]);
        $this->aw->addUserPayment($this->user->getId(), Cart::PAYMENTTYPE_APPSTORE, new AwPlusSubscription(), null, new \DateTime("-3 year"));
        $this->user->setSubscription(Usr::SUBSCRIPTION_MOBILE);
        $this->em->flush();
        $this->initCommand($this->container->get(CancelFailedSubscriptionsCommand::class));
        $this->container->get("monolog.logger.payment")->pushHandler($this->logs);
        $this->executeCommand();
        $this->logNotContains("subscription found");
        $this->logContains("done, processed: 1, cancelled: 0, removed: 1, warnings: 0");
    }

    /**
     * @dataProvider platformProvider
     */
    public function testFreeUserWithMobileSubscriptionThrowTemporaryVerificationExceptionShouldBeSkipped($serviceName)
    {
        $mock = $this->mockServiceWithBuilder($serviceName);
        $mock->method('findSubscriptions')->willThrowException((new VerificationException($this->user, [], $mock, 'Error'))->setTemporary(true));
        $this->aw->addUserPayment($this->user->getId(), Cart::PAYMENTTYPE_APPSTORE, new AwPlusSubscription(), null, new \DateTime("-3 year"));
        $this->user->setSubscription(Usr::SUBSCRIPTION_MOBILE);
        $this->em->flush();
        $this->initCommand($this->container->get(CancelFailedSubscriptionsCommand::class));
        $this->container->get("monolog.logger.payment")->pushHandler($this->logs);
        $this->executeCommand();
        $this->logContains("In-App Purchase verification exception");
        $this->logContains("temporary verification error, skip");
        $this->logContains("done, processed: 1, cancelled: 0, removed: 0, warnings: 0");
    }

    /**
     * @dataProvider platformProvider
     */
    public function testFreeUserWithMobileSubscriptionThrowNotTemporaryVerificationExceptionShouldBeSkipped($serviceName)
    {
        $mock = $this->mockServiceWithBuilder($serviceName);
        $mock->method('findSubscriptions')->willThrowException((new VerificationException($this->user, [], $mock, 'Error'))->setTemporary(false));
        $this->aw->addUserPayment($this->user->getId(), Cart::PAYMENTTYPE_APPSTORE, new AwPlusSubscription(), null, new \DateTime("-3 year"));
        $this->user->setSubscription(Usr::SUBSCRIPTION_MOBILE);
        $this->em->flush();
        $this->initCommand($this->container->get(CancelFailedSubscriptionsCommand::class));
        $this->container->get("monolog.logger.payment")->pushHandler($this->logs);
        $this->executeCommand();
        $this->logContains("In-App Purchase verification exception");
        $this->logNotContains("temporary verification error, skip");
        $this->logContains("done, processed: 1, cancelled: 0, removed: 1, warnings: 0");
    }

    public function platformProvider()
    {
        return [
            [AppleStore::class],
            [GooglePlay::class],
        ];
    }

    protected function executeCommand(array $args = [])
    {
        parent::executeCommand(array_merge([
            '--userId' => $this->user->getUserid(),
        ], $args));
    }

    private function getPurchase(\DateTime $expiresDate, string $platform, bool $canceled = false): AbstractSubscription
    {
        /** @var AbstractSubscription $purchase */
        $purchase = AbstractSubscription::create(
            SubscriptionAwPlus::class,
            $this->user,
            $platform === AppleStore::class ? Cart::PAYMENTTYPE_APPSTORE : Cart::PAYMENTTYPE_ANDROIDMARKET,
            time(),
            new \DateTime()
        )
            ->setCanceled($canceled);
        $purchase->setExpiresDate($expiresDate);

        return $purchase;
    }
}
