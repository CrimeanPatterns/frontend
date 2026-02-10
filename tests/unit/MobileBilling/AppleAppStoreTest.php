<?php

namespace AwardWallet\Tests\Unit\MobileBilling;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Service\Billing\RecurringManager;
use AwardWallet\MainBundle\Service\InAppPurchase\AppleAppStore\Connector;
use AwardWallet\MainBundle\Service\InAppPurchase\AppleAppStore\Provider as AppleStore;
use AwardWallet\MainBundle\Service\InAppPurchase\AppleAppStore\UserDetector;
use AwardWallet\MainBundle\Service\InAppPurchase\Billing;
use AwardWallet\MainBundle\Service\InAppPurchase\Exception\ConnectException;
use AwardWallet\MainBundle\Service\InAppPurchase\PurchaseInterface;
use AwardWallet\MainBundle\Service\InAppPurchase\Subscription\AwPlus as SubscriptionAwPlus;
use AwardWallet\Tests\Unit\BaseUserTest;
use Buzz\Client\Curl;
use Buzz\Exception\RequestException;
use Monolog\Handler\TestHandler;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @group mobile
 * @group frontend-unit
 * @group mobile/billing
 * @group billing
 */
class AppleAppStoreTest extends BaseUserTest
{
    /**
     * @var TestHandler
     */
    private $logs;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var string
     */
    private $secret;

    public function _before()
    {
        parent::_before();
        $this->logs = new TestHandler();
        $this->container->get(LoggerInterface::class)->pushHandler($this->logs);

        $this->translator = $this->container->get('translator');
        $this->secret = $this->container->getParameter('aw.mobile.iap_apple_secret');
        $this->db->executeQuery("delete from GroupUserLink where UserID = {$this->user->getUserid()}");
        $this->em->refresh($this->user);
    }

    public function _after()
    {
        $this->container->get(LoggerInterface::class)->popHandler();

        $this->logs =
        $this->secret =
        $this->translator = null;
        parent::_after();
    }

    public function testFailConnect()
    {
        $this->expectException(\AwardWallet\MainBundle\Service\InAppPurchase\Exception\VerificationException::class);
        /** @var Connector $connector */
        $connector = $this->prophesize(Connector::class);
        $connector->sendRequest(Argument::type('string'), Argument::type('string'))
            ->willThrow(new ConnectException('Invalid response'));
        $provider = $this->getProvider($connector->reveal());
        $provider->validate([
            'type' => 'ios-appstore',
            'appStoreReceipt' => '123',
        ], $this->user);
    }

    public function testCurlRequestException()
    {
        $this->expectException(\AwardWallet\MainBundle\Service\InAppPurchase\Exception\ConnectException::class);
        $this->expectExceptionMessage('Test Error');
        $curl = $this->getMockBuilder(Curl::class)->disableOriginalConstructor()->getMock();
        $curl->expects($this->exactly(3))->method('send')->willThrowException(new RequestException("Test Error", 123));
        /** @var Connector $connector */
        $connector = $this->container->get(Connector::class);
        $connector->setClient($curl);
        $connector->setWaitBeforeRetry(1);
        $connector->sendRequest(AppleStore::ENDPOINT_SANDBOX, "");
    }

    public function testInvalidReceiptDataIOS6()
    {
        $this->expectException(\AwardWallet\MainBundle\Service\InAppPurchase\Exception\VerificationException::class);
        $provider = $this->getProvider($this->getConnector([]));
        $provider->validate([
            'type' => 'ios-appstore',
            'transactionReceipt' => '123',
        ], $this->user);
    }

    public function testInvalidReceiptDataIOS7()
    {
        $this->expectException(\AwardWallet\MainBundle\Service\InAppPurchase\Exception\VerificationException::class);
        $provider = $this->getProvider($this->getConnector([]));
        $provider->validate([
            'type' => 'ios-appstore',
            'appStoreReceipt' => '123',
        ], $this->user);
    }

    public function testInvalidTransactionId()
    {
        $this->expectException(\AwardWallet\MainBundle\Service\InAppPurchase\Exception\QuietVerificationException::class);
        $this->expectExceptionMessageRegExp('#Invalid transaction id#');
        $provider = $this->getProvider($this->getConnector([]));
        $provider->validate([
            'type' => 'ios-appstore',
            'id' => 'abc',
            'appStoreReceipt' => '123',
        ], $this->user);
    }

    public function testMalformedResponse()
    {
        $this->expectException(\AwardWallet\MainBundle\Service\InAppPurchase\Exception\VerificationException::class);
        $this->expectExceptionMessageRegExp('#receipt-data property was malformed#');
        $provider = $this->getProvider($this->getConnector(['status' => AppleStore::RESULT_DATA_MALFORMED]));
        $provider->validate([
            'type' => 'ios-appstore',
            'appStoreReceipt' => '123',
        ], $this->user);
    }

    public function testInvalidResponseStatusIOS7()
    {
        $this->expectException(\AwardWallet\MainBundle\Service\InAppPurchase\Exception\VerificationException::class);
        $this->expectExceptionMessageRegExp('#receipt could not be authenticated#');
        $provider = $this->getProvider($this->getConnector([
            'status' => AppleStore::RESULT_RECEIPT_NOT_AUTHENTICATED,
            'receipt' => [
                'in_app' => [],
            ],
        ]));
        $provider->validate([
            'type' => 'ios-appstore',
            'appStoreReceipt' => '123',
        ], $this->user);
    }

    public function testInvalidResponseStatusIOS6()
    {
        $this->expectException(\AwardWallet\MainBundle\Service\InAppPurchase\Exception\VerificationException::class);
        $this->expectExceptionMessageRegExp('#receipt could not be authenticated#');
        $provider = $this->getProvider($this->getConnector([
            'status' => AppleStore::RESULT_RECEIPT_NOT_AUTHENTICATED,
            'receipt' => [
                'bid' => AppleStore::BUNDLE_ID,
            ],
        ]));
        $provider->validate([
            'type' => 'ios-appstore',
            'id' => 123,
            'transactionReceipt' => '123',
        ], $this->user);
    }

    public function testInvalidBundleIdIOS7()
    {
        $this->expectException(\AwardWallet\MainBundle\Service\InAppPurchase\Exception\VerificationException::class);
        $this->expectExceptionMessageRegExp('#Invalid bundle id#');
        $provider = $this->getProvider($this->getConnector([
            'status' => AppleStore::RESULT_OK,
            'receipt' => [
                'in_app' => [],
                'bundle_id' => 'xxx',
            ],
        ]));
        $provider->validate([
            'type' => 'ios-appstore',
            'appStoreReceipt' => '123',
        ], $this->user);
    }

    public function testInvalidBundleIdIOS6()
    {
        $this->expectException(\AwardWallet\MainBundle\Service\InAppPurchase\Exception\VerificationException::class);
        $this->expectExceptionMessageRegExp('#Invalid bundle id#');
        $provider = $this->getProvider($this->getConnector([
            'status' => AppleStore::RESULT_OK,
            'receipt' => [
                'bid' => 'xxx',
            ],
        ]));
        $provider->validate([
            'type' => 'ios-appstore',
            'id' => 123,
            'transactionReceipt' => '123',
        ], $this->user);
    }

    public function testInvalidProductIdIOS6()
    {
        $request = [
            'type' => 'ios-appstore',
            'id' => 123,
            'transactionReceipt' => '123',
        ];
        $return = [
            'status' => AppleStore::RESULT_OK,
            'receipt' => [
                'bid' => AppleStore::BUNDLE_ID,
                'product_id' => 'xxx',
                'purchase_date_ms' => time() * 1000,
            ],
        ];
        $provider = $this->getProvider($this->getConnector($return));
        $provider->validate($request, $this->user);
        $this->assertStringContainsString('Invalid product id', $this->getLogs());
    }

    public function testSuccessValidationIOS6()
    {
        $request = [
            'type' => 'ios-appstore',
            'id' => 123,
            'transactionReceipt' => '123',
        ];
        $return = [
            'status' => AppleStore::RESULT_OK,
            'receipt' => [
                'bid' => AppleStore::BUNDLE_ID,
                'transaction_id' => 123,
                'product_id' => AppleStore::PRODUCT_AWPLUS_SUBSCR,
                'purchase_date_ms' => time() * 1000,
                'original_transaction_id' => 123456,
            ],
        ];
        $provider = $this->getProvider($this->getConnector($return));
        $purchases = $provider->validate($request, $this->user);
        $this->assertCount(1, $purchases);
        /** @var PurchaseInterface $purchase */
        $purchase = $purchases[0];
        $this->assertStringStartsWith('123', strval($purchase->getTransactionId()));
        $this->assertEquals(SubscriptionAwPlus::class, $purchase->getPurchaseType());
        $this->assertEquals(Cart::PAYMENTTYPE_APPSTORE, $purchase->getPaymentType());
        $this->assertEmpty($purchase->getUserToken());
        $this->assertNotEmpty($purchase->getPurchaseToken());
    }

    public function testSuccessValidationIOS7()
    {
        $originalTransactionId = 'original' . StringHandler::getRandomCode(10);
        $request = [
            'type' => 'ios-appstore',
            'appStoreReceipt' => '123',
        ];
        $return = [
            'status' => AppleStore::RESULT_OK,
            'receipt' => [
                'bundle_id' => AppleStore::BUNDLE_ID,
                'in_app' => [
                    [
                        "quantity" => "1",
                        "product_id" => AppleStore::PRODUCT_AWPLUS_SUBSCR,
                        "transaction_id" => 'testTransaction001' . StringHandler::getRandomCode(10),
                        "purchase_date_ms" => time() * 1000,
                        'original_transaction_id' => $originalTransactionId,
                    ],
                    [
                        "quantity" => "1",
                        "product_id" => AppleStore::PRODUCT_AWPLUS_SUBSCR,
                        "transaction_id" => 'testTransaction002' . StringHandler::getRandomCode(10),
                        "purchase_date_ms" => time() * 1000,
                        'original_transaction_id' => $originalTransactionId,
                    ],
                ],
            ],
        ];
        $provider = $this->getProvider($this->getConnector($return));
        $purchases = $provider->validate($request, $this->user);
        $this->assertCount(2, $purchases);
        /** @var PurchaseInterface $purchase */
        $purchase = $purchases[0];
        $this->assertStringStartsWith('testTransaction001', $purchase->getTransactionId());
        $this->assertEquals(SubscriptionAwPlus::class, $purchase->getPurchaseType());
        $this->assertEquals(Cart::PAYMENTTYPE_APPSTORE, $purchase->getPaymentType());
        $this->assertEquals(123, $purchase->getUserToken());
        $this->assertEmpty($purchase->getPurchaseToken());
    }

    public function testTransactionAndAppStoreReceipt()
    {
        $request = [
            'type' => 'ios-appstore',
            'id' => 123,
            'transactionReceipt' => '123',
            'appStoreReceipt' => '321',
        ];
        $return = [
            'status' => AppleStore::RESULT_OK,
            'receipt' => [
                'bid' => AppleStore::BUNDLE_ID,
                'transaction_id' => 123,
                'product_id' => AppleStore::PRODUCT_AWPLUS_SUBSCR,
                'purchase_date_ms' => time() * 1000,
                'original_transaction_id' => 123456,
            ],
        ];
        $provider = $this->getProvider($this->getConnector($return));
        $purchases = $provider->validate($request, $this->user);
        $this->assertCount(1, $purchases);
        /** @var PurchaseInterface $purchase */
        $purchase = $purchases[0];
        $this->assertStringStartsWith('123', strval($purchase->getTransactionId()));
        $this->assertEquals(SubscriptionAwPlus::class, $purchase->getPurchaseType());
        $this->assertEquals(Cart::PAYMENTTYPE_APPSTORE, $purchase->getPaymentType());
        $this->assertEquals('321', $purchase->getUserToken());
        $this->assertEquals('123', $purchase->getPurchaseToken());

        unset($request['transactionReceipt']);
        $purchases = $provider->validate($request, $this->user);
        $this->assertCount(1, $purchases);
        /** @var PurchaseInterface $purchase */
        $purchase = $purchases[0];
        $this->assertEmpty($purchase->getUserToken());
        $this->assertNotEmpty($purchase->getPurchaseToken());
    }

    public function testRenewal()
    {
        $time = time();
        $manager = $this->container->get('aw.manager.cart');
        $cart = $manager->setUser($this->user)->createNewCart();
        $manager->addAwSubscriptionItem($cart, (new \DateTime())->setTimestamp($time));
        $cart->setPaymenttype(PAYMENTTYPE_APPSTORE);
        $cart->setBillingtransactionid(123456789);
        $manager->markAsPayed($cart);
        $manager->save($cart);
        $this->assertEquals(ACCOUNT_LEVEL_AWPLUS, $this->user->getAccountlevel());
        $this->assertNotEmpty($this->user->getPlusExpirationDate());
        $this->assertEquals(date("Y-m-d", strtotime("+1 year", $time)), $this->user->getPlusExpirationDate()->format("Y-m-d"));

        $request = [
            'type' => 'ios-appstore',
            'appStoreReceipt' => '123',
        ];
        $return = [
            'status' => AppleStore::RESULT_OK,
            'receipt' => [
                'bundle_id' => AppleStore::BUNDLE_ID,
                'in_app' => [
                    [
                        "quantity" => "1",
                        "product_id" => AppleStore::PRODUCT_AWPLUS_SUBSCR,
                        "transaction_id" => 123456789,
                        "purchase_date_ms" => $time * 1000,
                        'original_transaction_id' => 123456789,
                    ],
                    [
                        "quantity" => "1",
                        "product_id" => AppleStore::PRODUCT_AWPLUS_SUBSCR,
                        "transaction_id" => 123456790,
                        "purchase_date_ms" => (new \DateTime())->setTimestamp($time)->modify('+1 year')->getTimestamp(),
                        'original_transaction_id' => 123456789,
                    ],
                ],
            ],
        ];
        $provider = $this->getProvider($this->getConnector($return));
        $purchases = $provider->validate($request, $this->user);

        $this->assertCount(2, $purchases);
        /** @var PurchaseInterface $purchase */
        $purchase = $purchases[1];
        $this->assertEquals(123456790, $purchase->getTransactionId());
        $this->assertEquals(SubscriptionAwPlus::class, $purchase->getPurchaseType());
        $this->assertEquals(Cart::PAYMENTTYPE_APPSTORE, $purchase->getPaymentType());
    }

    public function testRenewalIOS6()
    {
        $time = time();
        $this->db->executeQuery("DELETE FROM Cart WHERE PaymentType = " . PAYMENTTYPE_APPSTORE . " AND BillingTransactionID IN ('123', '124')");
        $startDateFirstSubscription = new \DateTime("2016-01-01");
        $startDateSecondSubscription = new \DateTime();
        $manager = $this->container->get('aw.manager.cart');
        $cart = $manager->setUser($this->user)->createNewCart();
        $manager->addAwSubscriptionItem($cart, $startDateFirstSubscription);
        $cart->setPaymenttype(PAYMENTTYPE_APPSTORE);
        $cart->setBillingtransactionid(123);
        $manager->markAsPayed($cart);
        $cart->setPaydate($startDateFirstSubscription);
        $manager->save($cart);
        $this->user->setPlusExpirationDate(new \DateTime("@" . $time));
        $this->em->flush();

        $request = [
            'type' => 'ios-appstore',
            'appStoreReceipt' => '123',
        ];
        $return = [
            'status' => AppleStore::RESULT_OK,
            'receipt' => [
                'bid' => AppleStore::BUNDLE_ID,
                'transaction_id' => 123,
                'product_id' => AppleStore::PRODUCT_AWPLUS_SUBSCR,
                'purchase_date_ms' => $startDateFirstSubscription->getTimestamp() * 1000,
                'original_transaction_id' => 123,
            ],
            'latest_receipt_info' => [
                'bid' => AppleStore::BUNDLE_ID,
                'transaction_id' => 124,
                'product_id' => AppleStore::PRODUCT_AWPLUS_SUBSCR,
                'purchase_date_ms' => $startDateSecondSubscription->getTimestamp() * 1000,
                'original_transaction_id' => 123,
            ],
        ];
        $provider = $this->getProvider($this->getConnector($return));
        $purchases = $provider->validate($request, $this->user);

        $this->assertCount(2, $purchases);
        /** @var PurchaseInterface $purchase */
        $purchase = $purchases[1];
        $this->assertEquals(124, $purchase->getTransactionId());
        $this->assertEquals(SubscriptionAwPlus::class, $purchase->getPurchaseType());
        $this->assertEquals(Cart::PAYMENTTYPE_APPSTORE, $purchase->getPaymentType());

        $billing = new Billing(
            $this->em,
            $this->container->get("aw.manager.cart"),
            $this->makeEmpty(LoggerInterface::class),
            $this->container->get('aw.email.mailer'),
            $this->createMock(RecurringManager::class)
        );

        $billing->processing($purchases[1]);

        $this->em->refresh($this->user);
        $this->assertEquals(ACCOUNT_LEVEL_AWPLUS, $this->user->getAccountlevel());
        $this->assertNotEmpty($this->user->getPlusExpirationDate());
        $this->assertEquals(date("Y-m-d", strtotime("+1 year", $time)), $this->user->getPlusExpirationDate()->format("Y-m-d"));
    }

    public function testCancelledTransaction()
    {
        $time = time();
        $transactionId = time();
        $cartManager = $this->container->get("aw.manager.cart");
        $cartManager->setUser($this->user);

        $cart = $cartManager->createNewCart();
        $cart->setPaymenttype(Cart::PAYMENTTYPE_APPSTORE);
        $cart->setBillingtransactionid($transactionId);
        $cartManager->addAwSubscriptionItem($cart, new \DateTime("@" . $time));
        $cartManager->markAsPayed($cart);
        $this->em->refresh($this->user);

        $this->assertEquals(ACCOUNT_LEVEL_AWPLUS, $this->user->getAccountlevel());
        $this->assertEquals(1, $this->user->getCarts()->filter(function ($cart) {
            /** @var Cart $cart */
            return $cart->isAwPlusSubscription();
        })->count());
        $this->assertEquals($cart, $this->user->getCarts()->first());
        $this->assertEquals(date("Y-m-d", strtotime("+1 year", $time)), $this->user->getPlusExpirationDate()->format("Y-m-d"));

        $request = [
            'type' => 'ios-appstore',
            'appStoreReceipt' => '321',
        ];
        $return = [
            'status' => AppleStore::RESULT_OK,
            'receipt' => [
                'bundle_id' => AppleStore::BUNDLE_ID,
                'in_app' => [],
            ],
            'latest_receipt_info' => [
                [
                    'quantity' => 1,
                    'product_id' => AppleStore::PRODUCT_AWPLUS_SUBSCR,
                    'transaction_id' => $transactionId,
                    'original_transaction_id' => $transactionId,
                    'purchase_date' => '2017-06-21 19:52:45 Etc/GMT',
                    'purchase_date_ms' => '1498074765000',
                    'cancellation_date' => '2017-06-22 13:47:25 Etc/GMT',
                    'cancellation_date_ms' => '1498139245000',
                ],
            ],
        ];
        $provider = $this->getProvider($this->getConnector($return));
        $purchases = $provider->validate($request, $this->user);
        $this->assertCount(1, $purchases);
        /** @var PurchaseInterface $purchase */
        $purchase = $purchases[0];
        $this->assertEquals($transactionId, $purchase->getTransactionId());
        $this->assertEquals(SubscriptionAwPlus::class, $purchase->getPurchaseType());
        $this->assertEquals(Cart::PAYMENTTYPE_APPSTORE, $purchase->getPaymentType());
        $this->assertEquals('321', $purchase->getUserToken());
        $this->assertEmpty($purchase->getPurchaseToken());
        $this->assertTrue($purchase->isCanceled());
    }

    /**
     * @return Connector
     */
    private function getConnector($return)
    {
        /** @var Connector $connector */
        $connector = $this->prophesize(Connector::class);
        $connector->sendRequest(Argument::type('string'), Argument::type('string'))->willReturn(json_encode($return));

        return $connector->reveal();
    }

    /**
     * @return AppleStore
     */
    private function getProvider(Connector $connector)
    {
        return new AppleStore(
            $connector,
            $this->container->get(LoggerInterface::class),
            $this->translator,
            $this->em,
            $this->container->get('aw.api.versioning'),
            $this->make(UserDetector::class, [
                'detect' => function () {
                    return $this->user;
                },
            ]),
            $this->container->get(LocalizeService::class),
            $this->secret,
            true
        );
    }

    private function getLogs()
    {
        return "\n------\n" . implode("\n", array_map(function (array $record) {
            return $record['message'];
        }, $this->logs->getRecords())) . "\n------\n";
    }
}
