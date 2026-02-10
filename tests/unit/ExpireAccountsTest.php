<?php

namespace AwardWallet\Tests\Unit;

use AwardWallet\MainBundle\Command\ExpireAwPlusCommand;
use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem\AwPlus;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusTrial;
use AwardWallet\MainBundle\Entity\MobileDevice;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\DataProvider\ExpireAwPlusDataProvider;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\MailerCollection;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Message;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\AwPlusExpired;
use AwardWallet\MainBundle\Globals\Cart\Manager;
use AwardWallet\MainBundle\Service\Billing\ExpirationCalculator;
use AwardWallet\MainBundle\Service\InAppPurchase\AbstractSubscription;
use AwardWallet\MainBundle\Service\InAppPurchase\AppleAppStore\Provider as AppleAppStore;
use AwardWallet\MainBundle\Service\InAppPurchase\Billing;
use AwardWallet\MainBundle\Service\InAppPurchase\GooglePlay\Provider as GooglePlay;
use AwardWallet\MainBundle\Service\InAppPurchase\ProviderRegistry;
use AwardWallet\MainBundle\Service\InAppPurchase\Subscription\AwPlus as SubscriptionAwPlus;
use Codeception\Stub\Expected;
use Google\Service\AndroidPublisher\Resource\PurchasesSubscriptions;
use Google\Service\AndroidPublisher\SubscriptionPurchase;
use Monolog\Handler\TestHandler;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group frontend-unit
 * @group billing
 */
class ExpireAccountsTest extends BaseUserTest
{
    /** @var CommandTester */
    private $commandTester;

    /** @var string */
    private $commandName;

    /** @var TestHandler */
    private $logs;

    /** @var Manager */
    private $cartManager;

    /**
     * @var Billing
     */
    private $billing;

    /**
     * @var int
     */
    private $ts;

    private bool $isInited = false;

    public function _after()
    {
        if (!empty($this->container)) {
            $this->container->get(LoggerInterface::class)->popHandler();
            $this->container->get("monolog.logger.payment")->popHandler();
        }

        $this->billing =
        $this->cartManager =
        $this->logs =
        $this->commandTester =
        $this->command = null;

        parent::_after();
    }

    public function testFreeUser()
    {
        $this->reInitCommand();
        $this->user->setAccountlevel(ACCOUNT_LEVEL_FREE);
        $this->em->flush();

        $this->executeCommand();
        $this->logNotContains('was downgraded to free account');
        $this->db->seeInDatabase('Usr', ['UserID' => $this->user->getId(), 'AccountLevel' => ACCOUNT_LEVEL_FREE]);
    }

    public function testExpiresSoon()
    {
        $this->reInitCommand();
        $expirationDate = date_create('+1 month');
        $payDate = (clone $expirationDate)->modify('-6 month');
        $expectedExpirationDate = (clone $payDate)->modify('+6 month');

        if ($expectedExpirationDate->format('Y-m-d') !== $expirationDate->format('Y-m-d')) {
            $this->markTestSkipped('Current date is not suitable for test');
        }

        $mock = $this->mockServiceWithBuilder(ExpirationCalculator::class);
        $mock->method('getAccountExpiration')->willReturn(['date' => $expirationDate->getTimestamp(), 'lastPrice' => 5]);
        $this->aw->addUserPayment($this->user->getId(), Cart::PAYMENTTYPE_APPSTORE, null, [], $payDate);

        $this->executeCommand();
        $this->logContains('was noticed about expiration');
        $this->db->seeInDatabase('Usr', ['UserID' => $this->user->getId(), 'AccountLevel' => ACCOUNT_LEVEL_AWPLUS]);
    }

    public function testDowngradeViaPayment()
    {
        $this->reInitCommand();
        $cart = $this->aw->addUserPayment($this->user->getId(), Cart::PAYMENTTYPE_APPSTORE, null, [], new \DateTime("-7 month"));

        $this->executeCommand();
        $this->logContains('was downgraded to free account');
        $this->db->seeInDatabase('Usr', ['UserID' => $this->user->getId(), 'AccountLevel' => ACCOUNT_LEVEL_FREE]);
    }

    public function testDowngradeTrial()
    {
        $this->mockService('aw.email.mailer', $this->make(Mailer::class, [
            'getMessageByTemplate' => Expected::once(function (AbstractTemplate $template) {
                $this->assertInstanceOf(AwPlusExpired::class, $template);
                $this->assertTrue($template->trial);

                return new Message();
            }),
            'send' => Expected::once(),
        ]));
        $this->reInitCommand();
        $this->aw->addUserPayment($this->user->getId(), Cart::PAYMENTTYPE_CREDITCARD, new AwPlusTrial(), [], new \DateTime("-3 month -5 day"));
        $this->executeCommand();
        $this->logContains('was downgraded to free account');
        $this->db->seeInDatabase('Usr', ['UserID' => $this->user->getId(), 'AccountLevel' => ACCOUNT_LEVEL_FREE]);
    }

    public function testDowngradeViaCoupon()
    {
        $this->reInitCommand();
        $cart = $this->aw->addUserPayment($this->user->getId(), Cart::PAYMENTTYPE_APPSTORE, null, [], new \DateTime("-7 month"));
        /** @var AwPlus $item */
        $item = $cart->getItems()->first();
        $item->setDiscount(100);
        $this->em->flush();

        $this->executeCommand();
        $this->logContains('was downgraded to free account');
        $this->db->seeInDatabase('Usr', ['UserID' => $this->user->getId(), 'AccountLevel' => ACCOUNT_LEVEL_FREE]);
    }

    public function testUpgradeViaGooglePlay()
    {
        $this->mockService(PurchasesSubscriptions::class, $this->make(PurchasesSubscriptions::class, [
            'get' => function () {
                $result = new SubscriptionPurchase();
                $result->autoRenewing = true;
                $result->expiryTimeMillis = strtotime("+1 year") * 1000;

                return $result;
            },
        ]));

        $this->reInitCommand();
        $payDate = new \DateTime('@' . $this->ts);
        $payDate->modify("-1 year -1 day");
        $this->user->setAccountlevel(ACCOUNT_LEVEL_FREE);
        $this->user->setPlusExpirationDate(null);
        $this->em->flush();
        $cart = $this->cartManager->createNewCart();
        $cart->setPaymenttype(Cart::PAYMENTTYPE_ANDROIDMARKET);
        $cart->setPurchaseToken("xxx");
        $cart->setBillingtransactionid($baseOrderId = "GPA." . rand(0, 9999) . "-" . rand(0, 9999) . "-" . rand(0, 9999) . "-" . rand(0, 99999) . "");
        $this->cartManager->addAwSubscriptionItem($cart, clone $payDate);
        $this->cartManager->markAsPayed($cart, null, clone $payDate);
        $this->user->setSubscription(Usr::SUBSCRIPTION_MOBILE);
        $this->em->flush();

        $this->reInitCommand();
        $this->executeCommand();
        $this->logNotContains('was downgraded to free account');
        $this->logNotContains('was upgraded via google play');
        $this->db->seeInDatabase('Usr', ['UserID' => $this->user->getId(), 'AccountLevel' => ACCOUNT_LEVEL_AWPLUS]);

        $payDate->modify("-17 day");
        $cart->setPaydate(clone $payDate);
        $this->em->flush();
        $this->logs->clear();

        $subscriptionExpirationDate = (clone $cart->getPaydate())->modify('+1 year');

        /** @var GooglePlay $provider */
        $provider = $this->make(GooglePlay::class, [
            "scanSubscriptions" => function () use ($subscriptionExpirationDate) {
                $this->billing->processing(
                    AbstractSubscription::create(
                        SubscriptionAwPlus::class,
                        $this->user,
                        Cart::PAYMENTTYPE_ANDROIDMARKET,
                        time(),
                        $subscriptionExpirationDate
                    )
                        ->setPurchaseToken('xxx')
                );
            },
        ]);
        $this->container->get(ProviderRegistry::class)->addProvider($provider);

        $this->reInitCommand();
        $this->executeCommand();
        $this->logNotContains('was downgraded to free account');
        $this->logContains('was upgraded via google play');
        $this->db->seeInDatabase('Usr', ['UserID' => $this->user->getId(), 'AccountLevel' => ACCOUNT_LEVEL_AWPLUS]);
        $expiration = $this->container->get(ExpirationCalculator::class)->getAccountExpiration($this->user->getId());
        $this->assertEquals($payDate->modify('+2 year')->format('Y-m-d H:i'), date("Y-m-d H:i", $expiration['date']));
    }

    public function testUpgradeViaAppleAppStore()
    {
        $this->reInitCommand();
        $payDate = new \DateTime('@' . $this->ts);
        $payDate->modify("-1 year -1 day");
        $this->user->setAccountlevel(ACCOUNT_LEVEL_FREE);
        $this->user->setPlusExpirationDate(null);
        $this->em->flush();
        $cart = $this->cartManager->createNewCart();
        $cart->setPaymenttype(Cart::PAYMENTTYPE_APPSTORE);
        $cart->setPurchaseToken("xxx");
        $cart->setBillingtransactionid("12345");
        $this->cartManager->addAwSubscriptionItem($cart, clone $payDate);
        $this->cartManager->markAsPayed($cart, null, clone $payDate);
        $this->user->setSubscription(Usr::SUBSCRIPTION_MOBILE);
        $this->em->flush();

        $this->reInitCommand();
        $this->executeCommand();
        $this->logNotContains('was downgraded to free account');
        $this->logNotContains('was upgraded via apple appstore');
        $this->db->seeInDatabase('Usr', ['UserID' => $this->user->getId(), 'AccountLevel' => ACCOUNT_LEVEL_AWPLUS]);

        $payDate->modify("-16 day");
        $cart->setPaydate($payDate);
        $this->em->flush();
        $this->logs->clear();

        $subscriptionExpirationDate = (clone $cart->getPaydate())->modify('+1 year');

        /** @var AppleAppStore $provider */
        $provider = $this->make(AppleAppStore::class, [
            "em" => $this->em,
            "validate" => [
                AbstractSubscription::create(
                    SubscriptionAwPlus::class,
                    $this->user,
                    Cart::PAYMENTTYPE_APPSTORE,
                    time(),
                    $subscriptionExpirationDate
                )
                    ->setPurchaseToken('xxx'),
            ],
        ]);
        $this->container->get(ProviderRegistry::class)->addProvider($provider);

        $this->reInitCommand();
        $this->executeCommand();
        $this->logNotContains('was downgraded to free account');
        $this->logContains('was upgraded via apple appstore');
        $this->db->seeInDatabase('Usr', ['UserID' => $this->user->getId(), 'AccountLevel' => ACCOUNT_LEVEL_AWPLUS]);
        $expiration = $this->container->get(ExpirationCalculator::class)->getAccountExpiration($this->user->getId());
        $this->assertEquals($payDate->modify('+2 year')->format('Y-m-d H:i'), date("Y-m-d H:i", $expiration['date']));
    }

    private function reInitCommand()
    {
        $app = new Application($this->container->get('kernel'));
        $app->add(new ExpireAwPlusCommand(
            $this->container->get(LoggerInterface::class),
            $this->container->get(ExpireAwPlusDataProvider::class),
            $this->container->get(MailerCollection::class)
        ));

        /** @var ExpireAwPlusCommand $command */
        $command = $app->find('aw:email:expire-awplus');

        $this->tester = new CommandTester($command);
        $this->commandName = $command->getName();

        $this->logs = new TestHandler();
        $this->container->get(LoggerInterface::class)->pushHandler($this->logs);
        $this->container->get("monolog.logger.payment")->pushHandler($this->logs);

        $this->cartManager = $this->container->get('aw.manager.cart');
        $this->cartManager->setUser($this->user);

        $this->billing = $this->container->get(Billing::class);
        $this->ts = time();

        $this->user->setAccountlevel(ACCOUNT_LEVEL_AWPLUS);
        $this->user->setAccounts(10);
        $this->user->setPlusExpirationDate(new \DateTime());
        $this->user->setIosReceipt('xxx');
        $this->em->flush();

        if (!$this->isInited) {
            $this->db->haveInDatabase("MobileDevice", ["DeviceKey" => "test" . $this->user->getId(), "DeviceType" => MobileDevice::TYPE_IOS, 'Lang' => 'en', 'UserID' => $this->user->getId(), 'AppVersion' => '3.13']);
            $this->db->haveInDatabase("MobileDevice", ["DeviceKey" => "test" . $this->user->getId(), "DeviceType" => MobileDevice::TYPE_CHROME, 'Lang' => 'en', 'UserID' => $this->user->getId(), 'AppVersion' => 'web:personal']);
            $this->isInited = true;
        }
    }

    private function getLogs()
    {
        return "\n------\n" . implode("\n", array_map(function (array $record) {
            return $record['message'];
        }, $this->logs->getRecords())) . "\n------\n";
    }

    /**
     * @param string $str
     */
    private function logContains($str)
    {
        $this->assertStringContainsString($str, $this->getLogs());
    }

    /**
     * @param string $str
     */
    private function logNotContains($str)
    {
        $this->assertStringNotContainsString($str, $this->getLogs());
    }

    private function executeCommand($userId = null, $notifyExpiresSoon = true)
    {
        $userId = !isset($userId) ? $this->user->getId() : $userId;
        $input = [
            'command' => $this->commandName,
            '--userId' => [$userId],
            '--expiresSoon' => $notifyExpiresSoon,
        ];

        $this->tester->execute($input);
    }
}
