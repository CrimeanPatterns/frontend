<?php

namespace AwardWallet\Tests\Unit\MobileBilling;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\Repositories\CartRepository;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\InAppPurchase\AbstractSubscription;
use AwardWallet\MainBundle\Service\InAppPurchase\GooglePlay\Decoder;
use AwardWallet\MainBundle\Service\InAppPurchase\GooglePlay\Provider as GooglePlay;
use AwardWallet\MainBundle\Service\InAppPurchase\PurchaseInterface;
use AwardWallet\Tests\Unit\BaseUserTest;
use Google\Service\AndroidPublisher\Resource\PurchasesSubscriptions;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @group mobile
 * @group frontend-unit
 * @group mobile/billing
 * @group billing
 */
class GooglePlayTest extends BaseUserTest
{
    public const TEST_TRANSACTION = 'GPA.1234-5678-9012-34567';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    private $serviceAccountConfig;

    public function _before()
    {
        parent::_before();
        $this->db->executeQuery("delete from GroupUserLink where UserID = {$this->user->getUserid()}");
        $this->db->executeQuery("delete from Cart where PaymentType = " . Cart::PAYMENTTYPE_ANDROIDMARKET . " AND BillingTransactionID = '" . self::TEST_TRANSACTION . "'");
        $this->em->refresh($this->user);
        $this->logger = $this->prophesize(LoggerInterface::class)->reveal();
        $this->translator = $this->container->get('translator');
        $this->serviceAccountConfig = $this->container->getParameter('aw.mobile.iap_google_service_account_file');
    }

    public function _after()
    {
        $this->logger =
        $this->translator =
        $this->serviceAccountConfig = null;
        parent::_after();
    }

    public function testInvalidReceiptData()
    {
        $this->expectException(\AwardWallet\MainBundle\Service\InAppPurchase\Exception\QuietVerificationException::class);
        $this->expectExceptionMessage('Invalid receipt data');
        $this->getProvider()->validate([
            'type' => 'android-playstore',
        ], $this->user);
    }

    public function testMissingAndroidPublicKey()
    {
        $this->expectException(\AwardWallet\MainBundle\Service\InAppPurchase\Exception\VerificationException::class);
        $this->expectExceptionMessage('android public key is missing');
        $this->getProvider(new Decoder(''))->validate([
            'type' => 'android-playstore',
            'receipt' => 'xxx',
            'signature' => 'xxx',
        ], $this->user);
    }

    public function testInvalidAndroidPublicKey()
    {
        $this->expectException(\AwardWallet\MainBundle\Service\InAppPurchase\Exception\VerificationException::class);
        $this->expectExceptionMessage('invalid public key');
        $this->getProvider(new Decoder("abc"))->validate([
            'type' => 'android-playstore',
            'receipt' => 'xxx',
            'signature' => 'xxx',
        ], $this->user);
    }

    public function testInvalidDeveloperPayload()
    {
        $this->expectException(\AwardWallet\MainBundle\Service\InAppPurchase\Exception\VerificationException::class);
        $this->expectExceptionMessageRegExp('#^Unable to parse response body into JSON#');
        $this->getProvider($this->getDecoder([
            'developerPayload' => 'UserID: 12345',
        ]))->validate([
            'type' => 'android-playstore',
            'receipt' => 'xxx',
            'signature' => 'xxx',
        ], $this->user);
    }

    public function testUserNotDetected()
    {
        $this->expectException(\AwardWallet\MainBundle\Service\InAppPurchase\Exception\VerificationException::class);
        $this->expectExceptionMessage('User not detected');
        $this->getProvider($this->getDecoder([
            'developerPayload' => json_encode(["UserID" => 0]),
        ]))->validate([
            'type' => 'android-playstore',
            'receipt' => 'xxx',
            'signature' => 'xxx',
        ]);
    }

    public function testInvalidBundle()
    {
        $this->expectException(\AwardWallet\MainBundle\Service\InAppPurchase\Exception\VerificationException::class);
        $this->expectExceptionMessage('Missing or invalid packageName');
        $this->getProvider($this->getDecoder([
            'packageName' => 'abc',
            'developerPayload' => json_encode(["UserID" => $this->user->getUserid()]),
        ]))->validate([
            'type' => 'android-playstore',
            'receipt' => 'xxx',
            'signature' => 'xxx',
        ]);
    }

    public function testInvalidPurchaseState()
    {
        $this->expectException(\AwardWallet\MainBundle\Service\InAppPurchase\Exception\VerificationException::class);
        $this->expectExceptionMessage('Missing or invalid purchaseState');
        $this->getProvider($this->getDecoder([
            'purchaseState' => 1,
            'developerPayload' => json_encode(["UserID" => $this->user->getUserid()]),
        ]))->validate([
            'type' => 'android-playstore',
            'receipt' => 'xxx',
            'signature' => 'xxx',
        ]);
    }

    public function testMissingOrderId()
    {
        $this->expectException(\AwardWallet\MainBundle\Service\InAppPurchase\Exception\VerificationException::class);
        $this->expectExceptionMessage('Missing order id');
        $subscr = $this->getGoogleSubscription();
        $subscr->setOrderId(null);
        $this->getProvider($this->getDecoder([
            'developerPayload' => json_encode(["UserID" => $this->user->getUserid()]),
            'orderId' => null,
        ]), $subscr)->validate([
            'type' => 'android-playstore',
            'receipt' => 'xxx',
            'signature' => 'xxx',
        ]);
    }

    public function testInvalidProductIdBefore20170217()
    {
        $datetimeMs = strtotime("2017-02-01") * 1000;
        $subscr = $this->getGoogleSubscription();
        $subscr->setStartTimeMillis($datetimeMs);
        $this->assertNotEmpty($this->getProvider($this->getDecoder([
            'developerPayload' => json_encode(["UserID" => $this->user->getUserid()]),
            'purchaseTime' => $datetimeMs,
            'productId' => GooglePlay::PRODUCT_AWPLUS_SUBSCR,
        ]), $subscr)->validate([
            'type' => 'android-playstore',
            'receipt' => 'xxx',
            'signature' => 'xxx',
        ]));
        $this->assertNotEmpty($this->getProvider($this->getDecoder([
            'developerPayload' => json_encode(["UserID" => $this->user->getUserid()]),
            'purchaseTime' => $datetimeMs,
            'productId' => GooglePlay::PRODUCT_AWPLUS_SUBSCR_DISCOUNT,
        ]), $subscr)->validate([
            'type' => 'android-playstore',
            'receipt' => 'xxx',
            'signature' => 'xxx',
        ]));
    }

    public function testInvalidProductIdAfter20170217()
    {
        $datetimeMs = strtotime("2017-03-01") * 1000;
        $subscr = $this->getGoogleSubscription();
        $subscr->setStartTimeMillis($datetimeMs);
        $this->assertNotEmpty($this->getProvider($this->getDecoder([
            'developerPayload' => json_encode(["UserID" => $this->user->getUserid()]),
            'purchaseTime' => $datetimeMs,
            'productId' => GooglePlay::PRODUCT_AWPLUS_SUBSCR_DISCOUNT,
        ]), $subscr)->validate([
            'type' => 'android-playstore',
            'receipt' => 'xxx',
            'signature' => 'xxx',
        ]));
        $this->assertNotEmpty($this->getProvider($this->getDecoder([
            'developerPayload' => json_encode(["UserID" => $this->user->getUserid()]),
            'purchaseTime' => $datetimeMs,
            'productId' => GooglePlay::PRODUCT_AWPLUS_SUBSCR,
        ]), $subscr)->validate([
            'type' => 'android-playstore',
            'receipt' => 'xxx',
            'signature' => 'xxx',
        ]));
    }

    public function testCorrectOrderId()
    {
        $manager = $this->container->get('aw.manager.cart');
        $cart = $manager->setUser($this->user)->createNewCart();
        $manager->addAwSubscriptionItem($cart, new \DateTime("-1 year"));
        $cart->setPaymenttype(PAYMENTTYPE_ANDROIDMARKET);
        $cart->setBillingtransactionid(self::TEST_TRANSACTION);
        $manager->markAsPayed($cart);
        $manager->save($cart);

        $subscr = $this->getGoogleSubscription();
        $subscr->setStartTimeMillis(strtotime("-1 year") * 1000);
        $subscr->setExpiryTimeMillis(strtotime("+1 year") * 1000);
        $subscr->setOrderId($expected = self::TEST_TRANSACTION . '..0');

        $purchases = $this->getProvider($this->getDecoder([
            // alternative payload
            'developerPayload' => "subs:awardwallet_test:" . json_encode(["UserID" => $this->user->getUserid()]),
            'productId' => GooglePlay::PRODUCT_AWPLUS_SUBSCR,
            'orderId' => self::TEST_TRANSACTION . '..1',
            // 'purchaseTime' => strtotime("2017-01-01") * 1000,
        ]), $subscr)->validate([
            'type' => 'android-playstore',
            'receipt' => 'xxx',
            'signature' => 'xxx',
        ]);
        $this->assertNotEmpty($purchases);
        $this->assertCount(2, $purchases);
        /** @var PurchaseInterface $purchase */
        $purchase = $purchases[1];
        $this->assertEquals($expected, $purchase->getTransactionId());
    }

    public function testPaymentPending()
    {
        $subscr = $this->getGoogleSubscription();
        $subscr->setOrderId(self::TEST_TRANSACTION . '..0');
        $subscr->setStartTimeMillis(strtotime('-11 months') * 1000);
        $subscr->setExpiryTimeMillis(strtotime('+1 month +2 days') * 1000);

        /** @var PurchaseInterface[] $purchases */
        $purchases = $this->getProvider($this->getDecoder([
            'developerPayload' => json_encode(["UserID" => $this->user->getUserid()]),
            'productId' => GooglePlay::PRODUCT_AWPLUS_SUBSCR,
            'orderId' => self::TEST_TRANSACTION . '..0',
        ]), $subscr)->validate([
            'type' => 'android-playstore',
            'receipt' => 'xxx',
            'signature' => 'xxx',
        ]);

        $this->assertNotEmpty($purchases);
        $this->assertCount(2, $purchases);
        $this->assertEquals(self::TEST_TRANSACTION, $purchases[0]->getTransactionId());
        $this->assertFalse($purchases[0]->isCanceled());

        $subscr = $this->getGoogleSubscription();
        $subscr->setOrderId(self::TEST_TRANSACTION . '..1');
        $subscr->setStartTimeMillis(strtotime('-11 months') * 1000);
        $subscr->setExpiryTimeMillis(strtotime('+1 year +1 month +2 days') * 1000);
        /** @var PurchaseInterface[] $purchases */
        $purchases = $this->getProvider($this->getDecoder([
            'developerPayload' => json_encode(["UserID" => $this->user->getUserid()]),
            'productId' => GooglePlay::PRODUCT_AWPLUS_SUBSCR,
            'orderId' => self::TEST_TRANSACTION . '..1',
        ]), $subscr)->validate([
            'type' => 'android-playstore',
            'receipt' => 'xxx',
            'signature' => 'xxx',
        ]);

        $this->assertNotEmpty($purchases);
        $this->assertCount(3, $purchases);
        $this->assertEquals(self::TEST_TRANSACTION, $purchases[0]->getTransactionId());
        $this->assertEquals(self::TEST_TRANSACTION . '..0', $purchases[1]->getTransactionId());
        $this->assertFalse($purchases[0]->isCanceled());
        $this->assertFalse($purchases[1]->isCanceled());
    }

    public function testPaymentCancel()
    {
        $subscr = $this->getGoogleSubscription();
        $subscr->setOrderId(self::TEST_TRANSACTION . '..0');
        $subscr->setStartTimeMillis(strtotime('-11 months') * 1000);
        $subscr->setExpiryTimeMillis(strtotime('+1 month +2 days') * 1000);
        $subscr->setPaymentState(null);

        /** @var PurchaseInterface[] $purchases */
        $purchases = $this->getProvider($this->getDecoder([
            'developerPayload' => json_encode(["UserID" => $this->user->getUserid()]),
            'productId' => GooglePlay::PRODUCT_AWPLUS_SUBSCR,
            'orderId' => self::TEST_TRANSACTION . '..0',
        ]), $subscr)->validate([
            'type' => 'android-playstore',
            'receipt' => 'xxx',
            'signature' => 'xxx',
        ]);

        $this->assertNotEmpty($purchases);
        $this->assertCount(2, $purchases);
        $this->assertEquals(self::TEST_TRANSACTION, $purchases[0]->getTransactionId());
        $this->assertEquals(self::TEST_TRANSACTION . '..0', $purchases[1]->getTransactionId());
        $this->assertFalse($purchases[0]->isCanceled());
        $this->assertTrue($purchases[1]->isCanceled());

        $subscr = $this->getGoogleSubscription();
        $subscr->setOrderId(self::TEST_TRANSACTION . '..1');
        $subscr->setStartTimeMillis(strtotime('-11 months') * 1000);
        $subscr->setExpiryTimeMillis(strtotime('+1 year +1 month +2 days') * 1000);
        $subscr->setPaymentState(null);

        /** @var PurchaseInterface[] $purchases */
        $purchases = $this->getProvider($this->getDecoder([
            'developerPayload' => json_encode(["UserID" => $this->user->getUserid()]),
            'productId' => GooglePlay::PRODUCT_AWPLUS_SUBSCR,
            'orderId' => self::TEST_TRANSACTION . '..1',
        ]), $subscr)->validate([
            'type' => 'android-playstore',
            'receipt' => 'xxx',
            'signature' => 'xxx',
        ]);

        $this->assertNotEmpty($purchases);
        $this->assertCount(3, $purchases);
        $this->assertEquals(self::TEST_TRANSACTION, $purchases[0]->getTransactionId());
        $this->assertEquals(self::TEST_TRANSACTION . '..0', $purchases[1]->getTransactionId());
        $this->assertEquals(self::TEST_TRANSACTION . '..1', $purchases[2]->getTransactionId());
        $this->assertFalse($purchases[0]->isCanceled());
        $this->assertFalse($purchases[1]->isCanceled());
        $this->assertTrue($purchases[2]->isCanceled());
    }

    public function testRenewSubscription()
    {
        $manager = $this->container->get('aw.manager.cart');
        $cart = $manager->setUser($this->user)->createNewCart();
        $startDate = new \DateTime("-2 year");
        $endDate = new \DateTime("+1 year");
        $manager->addAwSubscriptionItem($cart, $startDate);
        $cart->setPaymenttype(PAYMENTTYPE_ANDROIDMARKET);
        $cart->setBillingtransactionid(self::TEST_TRANSACTION);
        $manager->markAsPayed($cart);
        $manager->save($cart);

        $subscr = $this->getGoogleSubscription();
        $subscr->setStartTimeMillis($startDate->getTimestamp() * 1000);
        $subscr->setOrderId(self::TEST_TRANSACTION . '..1');

        /** @var AbstractSubscription[] $purchases */
        $purchases = $this->getProvider($this->getDecoder([
            'developerPayload' => json_encode(["UserID" => $this->user->getUserid()]),
            'productId' => GooglePlay::PRODUCT_AWPLUS_SUBSCR,
            'purchaseTime' => $startDate->getTimestamp() * 1000,
        ]), $subscr)->validate([
            'type' => 'android-playstore',
            'receipt' => 'xxx',
            'signature' => 'xxx',
        ]);
        $this->assertNotEmpty($purchases);
        $this->assertCount(3, $purchases);
        $this->assertEquals(self::TEST_TRANSACTION, $purchases[0]->getTransactionId());
        $this->assertEquals(self::TEST_TRANSACTION . "..0", $purchases[1]->getTransactionId());
        $this->assertEquals(self::TEST_TRANSACTION . "..1", $purchases[2]->getTransactionId());
        $this->assertEquals($startDate->getTimestamp(), $purchases[0]->getPurchaseDate()->getTimestamp());
        $this->assertEquals($purchases[0]->getExpiresDate()->getTimestamp(), $purchases[1]->getPurchaseDate()->getTimestamp());
        $this->assertEquals($purchases[1]->getExpiresDate()->getTimestamp(), $purchases[2]->getPurchaseDate()->getTimestamp());
        $this->assertEquals($endDate->getTimestamp(), $purchases[2]->getExpiresDate()->getTimestamp());
    }

    public function testRefund()
    {
        $time = time();
        $startMs = strtotime("-2 years", $time) * 1000;
        $subscr = $this->getGoogleSubscription();
        $subscr->setOrderId(self::TEST_TRANSACTION . "..0");
        $subscr->setStartTimeMillis($startMs);
        $subscr->setExpiryTimeMillis($time * 1000);

        $decoder = $this->getDecoder([
            'developerPayload' => json_encode(["UserID" => $this->user->getUserid()]),
            'purchaseTime' => $startMs,
            'productId' => GooglePlay::PRODUCT_AWPLUS_SUBSCR_DISCOUNT,
        ]);
        $request = [
            'type' => 'android-playstore',
            'receipt' => 'xxx',
            'signature' => 'xxx',
        ];

        /** @var PurchaseInterface[] $purchases */
        $purchases = $this->getProvider($decoder, $subscr)->validate($request);
        $this->assertNotEmpty($purchases);
        $this->assertCount(2, $purchases);
        $this->assertFalse($purchases[0]->isCanceled());
        $this->assertFalse($purchases[1]->isCanceled());

        $subscr->setPaymentState(null);
        $subscr->setExpiryTimeMillis(strtotime("-11 months - 20 day", $time) * 1000);

        /** @var PurchaseInterface[] $purchases */
        $purchases = $this->getProvider($decoder, $subscr)->validate($request);
        $this->assertNotEmpty($purchases);
        $this->assertCount(2, $purchases);
        $this->assertFalse($purchases[0]->isCanceled());
        $this->assertTrue($purchases[1]->isCanceled());
        $this->assertEquals(self::TEST_TRANSACTION . "..0", $purchases[1]->getTransactionId());

        $subscr->setOrderId(self::TEST_TRANSACTION . "..1");
        $subscr->setStartTimeMillis(strtotime("2015-01-01") * 1000);
        $subscr->setPaymentState(null);
        $subscr->setExpiryTimeMillis(strtotime("2017-12-29") * 1000);

        /** @var PurchaseInterface[] $purchases */
        $purchases = $this->getProvider($decoder, $subscr)->validate($request);
        $this->assertNotEmpty($purchases);
        $this->assertCount(3, $purchases);
        $this->assertFalse($purchases[0]->isCanceled());
        $this->assertFalse($purchases[1]->isCanceled());
        $this->assertTrue($purchases[2]->isCanceled());
        $this->assertEquals(self::TEST_TRANSACTION . "..1", $purchases[2]->getTransactionId());
    }

    private function getDecoder(array $return = [], $merge = true)
    {
        if ($merge) {
            $result = array_merge([
                'autoRenewing' => true,
                'orderId' => 'GPA.0000-0000-0000-00000',
                'packageName' => GooglePlay::BUNDLE_ID,
                'productId' => GooglePlay::PRODUCT_AWPLUS_SUBSCR,
                'purchaseTime' => time() * 1000,
                'purchaseState' => GooglePlay::PURCHASE_STATE_PURCHASED,
                'purchaseToken' => 'purchase-token',
            ], $return);
        } else {
            $result = $return;
        }

        return $this->makeEmpty(Decoder::class, [
            'decode' => $result,
        ]);
    }

    private function getGoogleSubscription()
    {
        $googleSubscr = new \Google_Service_AndroidPublisher_SubscriptionPurchase();
        $googleSubscr->setAutoRenewing(true);
        $googleSubscr->setPaymentState(GooglePlay::PAYMENT_STATE_RECEIVED);
        $googleSubscr->setStartTimeMillis(time() * 1000);
        $googleSubscr->setExpiryTimeMillis(strtotime("+1 year") * 1000);
        $googleSubscr->setOrderId(self::TEST_TRANSACTION);

        return $googleSubscr;
    }

    /**
     * @return GooglePlay
     */
    private function getProvider(?Decoder $decoder = null, $googlePlaySubscription = null)
    {
        $decoder = $decoder ? $decoder : $this->getDecoder();
        $googlePlaySubscription = $googlePlaySubscription ? $googlePlaySubscription : $this->getGoogleSubscription();

        return $this->construct(
            GooglePlay::class,
            [
                $this->logger,
                $this->translator,
                $this->em,
                $this->container->get('aw.api.versioning'),
                false,
                $decoder,
                $this->serviceAccountConfig,
                $this->createMock(PurchasesSubscriptions::class),
                $this->container->get(CartRepository::class),
                $this->container->get(LocalizeService::class),
            ],
            ['getGooglePlaySubscription' => $googlePlaySubscription]
        );
    }
}
