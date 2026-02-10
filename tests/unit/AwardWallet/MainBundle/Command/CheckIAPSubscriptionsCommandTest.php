<?php

namespace AwardWallet\Tests\Unit\AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Command\CheckIAPSubscriptionsCommand;
use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription;
use AwardWallet\MainBundle\Entity\CartItem\BalanceWatchCredit;
use AwardWallet\MainBundle\Entity\Repositories\CartRepository;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\Billing\RecurringManager;
use AwardWallet\MainBundle\Service\InAppPurchase\AbstractSubscription;
use AwardWallet\MainBundle\Service\InAppPurchase\AppleAppStore\Connector;
use AwardWallet\MainBundle\Service\InAppPurchase\AppleAppStore\Provider as AppleAppStore;
use AwardWallet\MainBundle\Service\InAppPurchase\AppleAppStore\UserDetector;
use AwardWallet\MainBundle\Service\InAppPurchase\Billing;
use AwardWallet\MainBundle\Service\InAppPurchase\GooglePlay\Decoder;
use AwardWallet\MainBundle\Service\InAppPurchase\GooglePlay\Provider as GooglePlay;
use AwardWallet\MainBundle\Service\InAppPurchase\Subscription\AwPlus as SubscriptionAwPlus;
use AwardWallet\Tests\Unit\CommandTester;
use Google\Service\AndroidPublisher\Resource\PurchasesSubscriptions;
use Psr\Log\LoggerInterface;

/**
 * @group frontend-unit
 * @group billing
 * @group mobile
 * @group mobile/billing
 */
class CheckIAPSubscriptionsCommandTest extends CommandTester
{
    /**
     * @var Billing
     */
    private $billing;

    public function _before()
    {
        parent::_before();

        $cartManager = $this->container->get('aw.manager.cart');
        $cartManager->setUser($this->user);
        $this->billing = new Billing(
            $this->container->get('doctrine.orm.default_entity_manager'),
            $cartManager,
            $this->makeEmpty(LoggerInterface::class),
            $this->container->get('aw.email.mailer'),
            $this->createMock(RecurringManager::class)
        );
        $this->db->executeQuery("
            DELETE FROM Cart WHERE PaymentType IN (" . implode(",", [Cart::PAYMENTTYPE_APPSTORE, Cart::PAYMENTTYPE_ANDROIDMARKET]) . ") AND BillingTransactionID LIKE 'testTransaction%'
        ");

        $this->user->setIosReceipt('xxx');
        $this->em->flush();
    }

    public function _after()
    {
        $this->billing = null;
        $this->cleanCommand();
        parent::_after();
    }

    public function platformProvider()
    {
        return [
            ['ios'],
            ['android'],
        ];
    }

    /**
     * @dataProvider platformProvider
     */
    public function testFreeUserWithoutCartsShouldNotBeProcessed($platform)
    {
        $this->user->setAccountlevel(ACCOUNT_LEVEL_FREE);
        $this->em->flush();
        $this->initCommand($this->container->get(CheckIAPSubscriptionsCommand::class));
        $this->executeCommand(['platform' => $platform]);
        $this->logContains('processed users: 0, upgraded: 0, downgraded: 0');
    }

    /**
     * @dataProvider platformProvider
     */
    public function testAwPlusUserWithMonthBeforeExpirationShouldNotBeProcessed($platform)
    {
        $this->user->setAccountlevel(ACCOUNT_LEVEL_AWPLUS);
        $this->user->setPlusExpirationDate(new \DateTime('+1 month'));
        $this->em->flush();
        $this->addSubscriptionCart(new \DateTime('-11 month'), $platform);

        $this->initCommand($this->container->get(CheckIAPSubscriptionsCommand::class));
        $this->executeCommand(['platform' => $platform]);
        $this->logContains('processed users: 0, upgraded: 0, downgraded: 0');
    }

    /**
     * @dataProvider platformProvider
     */
    public function testAwPlusUserWith1HourBeforeExpirationShouldBeProcessed($platform)
    {
        $this->user->setAccountlevel(ACCOUNT_LEVEL_AWPLUS);
        $this->user->setPlusExpirationDate(new \DateTime('+1 hour'));
        $this->em->flush();
        $this->addSubscriptionCart(new \DateTime('-1 year +1 hour'), $platform);

        $this->mockProvider($platform);
        $this->initCommand($this->container->get(CheckIAPSubscriptionsCommand::class));
        $this->executeCommand(['platform' => $platform]);
        $this->logContains('processed users: 1, upgraded: 0, downgraded: 0');
    }

    /**
     * @dataProvider platformProvider
     */
    public function testAwPlusUserWith1HourBeforeExpirationShouldBeUpgraded($platform)
    {
        $this->user->setAccountlevel(ACCOUNT_LEVEL_AWPLUS);
        $this->user->setPlusExpirationDate(new \DateTime('+1 hour'));
        $this->em->flush();
        $this->addSubscriptionCart(new \DateTime('-1 year +1 hour'), $platform);

        $this->mockProvider($platform, [
            'validate' => [
                $this->createPurchase($platform, new \DateTime('-2 hour'), 'testTransaction-1'),
            ],
        ]);
        $this->initCommand($this->container->get(CheckIAPSubscriptionsCommand::class));
        $this->executeCommand(['platform' => $platform]);
        $this->logContains("userId: {$this->user->getId()} was upgraded");
        $this->logContains('processed users: 1, upgraded: 1, downgraded: 0');
    }

    /**
     * @dataProvider platformProvider
     */
    public function testAwPlusUserWith2DaysAfterExpirationShouldBeProcessed($platform)
    {
        $this->user->setAccountlevel(ACCOUNT_LEVEL_AWPLUS);
        $this->user->setPlusExpirationDate(new \DateTime('-2 day'));
        $this->em->flush();
        $this->addSubscriptionCart(new \DateTime('-1 year -2 day'), $platform);

        $this->mockProvider($platform);
        $this->initCommand($this->container->get(CheckIAPSubscriptionsCommand::class));
        $this->executeCommand(['platform' => $platform]);
        $this->logContains('processed users: 1, upgraded: 0, downgraded: 0');
    }

    /**
     * @dataProvider platformProvider
     */
    public function testAwPlusUserWith2DaysAfterExpirationShouldBeUpgraded($platform)
    {
        $this->user->setAccountlevel(ACCOUNT_LEVEL_AWPLUS);
        $this->user->setPlusExpirationDate(new \DateTime('-2 day'));
        $this->em->flush();
        $this->addSubscriptionCart(new \DateTime('-1 year -2 day'), $platform);

        $this->mockProvider($platform, [
            'validate' => [
                $this->createPurchase($platform, new \DateTime('-2 hour'), 'testTransaction-1'),
            ],
        ]);
        $this->initCommand($this->container->get(CheckIAPSubscriptionsCommand::class));
        $this->executeCommand(['platform' => $platform]);
        $this->logContains("userId: {$this->user->getId()} was upgraded");
        $this->logContains('processed users: 1, upgraded: 1, downgraded: 0');
    }

    /**
     * @dataProvider platformProvider
     */
    public function testAwPlusUserWith8DaysAfterExpirationShouldNotBeProcessed($platform)
    {
        $this->user->setAccountlevel(ACCOUNT_LEVEL_AWPLUS);
        $this->user->setPlusExpirationDate(new \DateTime('-8 day'));
        $this->em->flush();
        $this->addSubscriptionCart(new \DateTime('-1 year -8 day'), $platform);

        $this->mockProvider($platform);
        $this->initCommand($this->container->get(CheckIAPSubscriptionsCommand::class));
        $this->executeCommand(['platform' => $platform]);
        $this->logContains('processed users: 0, upgraded: 0, downgraded: 0');
    }

    /**
     * @dataProvider platformProvider
     */
    public function testAwPlusUserWithRefundShouldBeProcessed($platform)
    {
        $this->user->setAccountlevel(ACCOUNT_LEVEL_AWPLUS);
        $this->user->setPlusExpirationDate(new \DateTime('+1 year -2 day'));
        $this->em->flush();
        $this->addSubscriptionCart(new \DateTime('-2 day'), $platform);

        $this->mockProvider($platform, [
            'validate' => [
                $this->createPurchase($platform, new \DateTime('-2 day'), 'testTransaction', true),
            ],
        ]);
        $this->initCommand($this->container->get(CheckIAPSubscriptionsCommand::class));
        $this->executeCommand(['platform' => $platform]);
        $this->logContains("userId: {$this->user->getId()} was downgraded");
        $this->logContains('processed users: 1, upgraded: 0, downgraded: 1');
        $this->assertFalse($this->user->isAwPlus());
        $this->db->dontSeeInDatabase('Cart', ['UserID' => $this->user->getId()]);
    }

    /**
     * @dataProvider platformProvider
     */
    public function testAwPlusUserWithRefundShouldNotBeProcessed($platform)
    {
        $this->user->setAccountlevel(ACCOUNT_LEVEL_AWPLUS);
        $this->user->setPlusExpirationDate(new \DateTime('+11 month'));
        $this->em->flush();
        $this->addSubscriptionCart(new \DateTime('-1 month'), $platform);

        $this->mockProvider($platform, [
            'validate' => [
                $this->createPurchase($platform, new \DateTime('-1 month'), 'testTransaction', true),
            ],
        ]);
        $this->initCommand($this->container->get(CheckIAPSubscriptionsCommand::class));
        $this->executeCommand(['platform' => $platform]);
        $this->logNotContains("userId: {$this->user->getId()} was downgraded");
        $this->logContains('processed users: 0, upgraded: 0, downgraded: 0');
    }

    /**
     * @dataProvider platformProvider
     */
    public function testDowngradedUserShouldBeUpgraded($platform)
    {
        $this->addSubscriptionCart(new \DateTime('-13 month'), $platform);
        $this->assertFalse($this->user->isAwPlus());

        $this->mockProvider($platform, [
            'validate' => [
                $this->createPurchase($platform, new \DateTime('-1 month'), 'testTransaction-1'),
            ],
        ]);
        $this->initCommand($this->container->get(CheckIAPSubscriptionsCommand::class));
        $this->executeCommand(['platform' => $platform, '--startDate' => date("Y-m-d H:i:s", strtotime("-14 month"))]);
        $this->logContains("userId: {$this->user->getId()} was upgraded");
        $this->logContains('processed users: 1, upgraded: 1, downgraded: 0');
        $this->assertTrue($this->user->isAwPlus());
        $this->assertEquals(2, $this->db->grabCountFromDatabase('CartItem', [
            'ID' => $this->user->getId(),
            'TypeID' => AwPlusSubscription::TYPE,
        ]));
        $this->assertEquals(1, $this->db->grabCountFromDatabase('CartItem', [
            'ID' => $this->user->getId(),
            'TypeID' => BalanceWatchCredit::TYPE,
        ]));
    }

    /**
     * @dataProvider platformProvider
     */
    public function testMobileSubscriptionScanAfterIllegalDowngradeResolvedByUpgrade(string $platform)
    {
        $this->addSubscriptionCart(new \DateTime('-1 year -2 day'), $platform);
        $this->assertFalse($this->user->isAwPlus());

        $this->mockProvider($platform, [
            'validate' => [
                $this->createPurchase($platform, new \DateTime(), 'testTransaction-1'),
            ],
        ]);
        $this->initCommand($this->container->get(CheckIAPSubscriptionsCommand::class));
        $this->executeCommand(['platform' => $platform]);
        $this->logContains("userId: {$this->user->getId()} was upgraded");
        $this->logContains('processed users: 1, upgraded: 1, downgraded: 0');
        $this->assertTrue($this->user->isAwPlus());
    }

    protected function executeCommand(array $args = [])
    {
        parent::executeCommand(array_merge([
            '--userId' => [$this->user->getId()],
        ], $args));
    }

    protected function addSubscriptionCart(\DateTime $payDate, $platform, $transactionId = 'testTransaction')
    {
        $cartId = $this->db->shouldHaveInDatabase('Cart', [
            'UserID' => $this->user->getId(),
            'PayDate' => $payDate->format('Y-m-d H:i:s'),
            'PaymentType' => $platform == 'ios' ? Cart::PAYMENTTYPE_APPSTORE : Cart::PAYMENTTYPE_ANDROIDMARKET,
            'PurchaseToken' => "xxx",
            'BillingTransactionID' => $transactionId,
        ]);
        $this->db->shouldHaveInDatabase('CartItem', [
            'CartID' => $cartId,
            'ID' => $this->user->getId(),
            'TypeID' => AwPlusSubscription::TYPE,
            'Name' => 'AwardWallet Plus yearly subscription',
            'Price' => AwPlusSubscription::PRICE,
        ]);
    }

    protected function mockProvider($platform, array $methods = [])
    {
        if ($platform == 'ios') {
            $this->mockAppleProvider($methods);
        } else {
            $this->mockGoogleProvider($methods);
        }
    }

    protected function mockAppleProvider(array $methods)
    {
        /** @var AppleAppStore $provider */
        $provider = $this->construct(AppleAppStore::class, [
            $this->makeEmpty(Connector::class),
            $this->makeEmpty(LoggerInterface::class),
            $this->container->get('translator'),
            $this->em,
            $this->container->get('aw.api.versioning'),
            $this->make(UserDetector::class, [
                'detect' => function () {
                    return $this->user;
                },
            ]),
            $this->container->get(LocalizeService::class),
            '',
            true,
        ], $methods);
        $this->mockService(AppleAppStore::class, $provider);
    }

    protected function mockGoogleProvider(array $methods)
    {
        if (isset($methods['validate'])) {
            $methods['validatePurchaseData'] = $methods['validate'];
            unset($methods['validate']);
        }
        /** @var GooglePlay $provider */
        $provider = $this->construct(GooglePlay::class, [
            $this->makeEmpty(LoggerInterface::class),
            $this->container->get('translator'),
            $this->em,
            $this->container->get('aw.api.versioning'),
            false,
            $this->makeEmpty(Decoder::class),
            '',
            $this->makeEmpty(PurchasesSubscriptions::class),
            $this->container->get(CartRepository::class),
            $this->container->get(LocalizeService::class),
        ], $methods);
        $this->mockService(GooglePlay::class, $provider);
    }

    protected function createPurchase($platform, \DateTime $purchaseDate, $transactionId = 'testTransaction', $canceled = false)
    {
        /** @var AbstractSubscription $purchase */
        $purchase = AbstractSubscription::create(
            SubscriptionAwPlus::class,
            $this->user,
            $platform == 'ios' ? Cart::PAYMENTTYPE_APPSTORE : Cart::PAYMENTTYPE_ANDROIDMARKET,
            $transactionId,
            $purchaseDate
        );
        $purchase->setCanceled($canceled);
        $purchase->setPurchaseToken('xxx');

        return $purchase;
    }
}
