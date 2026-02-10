<?php

namespace AwardWallet\Test\Unit\MobileBilling;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\InAppPurchase\AbstractSubscription;
use AwardWallet\MainBundle\Service\InAppPurchase\AppleAppStore\Provider;
use AwardWallet\MainBundle\Service\InAppPurchase\AppleAppStore\UserDetector;
use AwardWallet\MainBundle\Service\InAppPurchase\Billing;
use AwardWallet\MainBundle\Service\InAppPurchase\Consumable\AwPlus as ConsumableAwPlus;
use AwardWallet\MainBundle\Service\InAppPurchase\Subscription\AwPlus;
use AwardWallet\Tests\Unit\BaseUserTest;
use Monolog\Handler\TestHandler;

/**
 * @group mobile
 * @group frontend-unit
 * @group mobile/billing
 * @group billing
 */
class UserDetectorTest extends BaseUserTest
{
    /**
     * @var TestHandler
     */
    private $logs;

    /**
     * @var UserDetector
     */
    private $detector;

    /**
     * @var Provider
     */
    private $provider;

    public function _before()
    {
        parent::_before();
        $this->logs = new TestHandler();
        $this->container->get("monolog.logger.payment")->pushHandler($this->logs);

        $this->detector = $this->container->get(UserDetector::class);
        $this->provider = $this->container->get(Provider::class);

        $this->db->executeQuery("
            DELETE FROM Cart WHERE PaymentType = " . Cart::PAYMENTTYPE_APPSTORE . " AND BillingTransactionID LIKE 'testTransaction%'
        ");
    }

    public function _after()
    {
        $this->provider = $this->detector = null;

        $this->container->get("monolog.logger.payment")->popHandler();
        parent::_after();
    }

    public function testConsumable()
    {
        $this->assertEquals($this->user, $this->detect(ConsumableAwPlus::class, new \stdClass(), $this->user));
        $this->logContains('consumable product');
    }

    public function testDisabledScanUserShouldBeDetectedViaTransactionId()
    {
        $user = $this->createUser();
        $this->addSubscriptionCart($user, new \DateTime("-12 month"), 'ios', 123, 'testTransaction');

        $this->assertEquals($user, $this->detect(AwPlus::class, $this->json('testTransaction', null, 200), $this->user, false));
        $this->logContains('subscription');
        $this->logContains('detected');
    }

    public function testDisabledScanUserShouldBeDetectedViaOriginalTransactionId()
    {
        $user = $this->createUser();
        $this->addSubscriptionCart($user, new \DateTime(), 'ios', 123, 'testTransactionOrig');

        $this->assertEquals($user, $this->detect(AwPlus::class, $this->json('testTransaction', 'testTransactionOrig', 200), $this->user, false));
        $this->logContains('subscription');
        $this->logContains('detected');
    }

    public function testDisabledScanUserShouldBeDetectedViaAppleTransactionId()
    {
        $user = $this->createUser();
        $this->addSubscriptionCart($user, new \DateTime(), 'ios', 123, 'testTransactionOld');

        $this->assertEquals($user, $this->detect(AwPlus::class, $this->json('testTransaction', 'testTransactionOrig', 123), $this->user, false));
        $this->logContains('subscription');
        $this->logContains('detected');
    }

    public function testDisabledScanUserShouldNotBeDetected()
    {
        $user = $this->createUser();
        $this->addSubscriptionCart($user, new \DateTime(), 'ios', 321, 'testTransactionOld');

        $this->assertEquals($this->user, $this->detect(AwPlus::class, $this->json('testTransaction', 'testTransactionOrig', 123), $this->user, false));
        $this->logContains('subscription');
        $this->logContains('detected');

        $this->logs->clear();

        $this->assertNull($this->detect(AwPlus::class, $this->json('testTransaction', 'testTransactionOrig', 123), null, false));
        $this->logContains('subscription');
        $this->logContains('disabled scan users');
        $this->logNotContains('detected');
    }

    public function testEnableScanUserShouldBeDetected()
    {
        $user = $this->createUser([
            'IosReceipt' => 'xxx',
        ]);

        $now = time();
        $oldTransactionId = 'testTransaction1';
        $newTransactionId = 'testTransaction2';

        $cartId = $this->addSubscriptionCart($user, new \DateTime('@' . $now), 'ios', null, $oldTransactionId);
        /** @var Provider $provider */
        $provider = $this->make(Provider::class, [
            "scanSubscriptions" => function ($u, $billing) use ($user, $newTransactionId, $now) {
                /** @var Billing $billing */
                $billing->processing(
                    AbstractSubscription::create(
                        AwPlus::class,
                        $user,
                        Cart::PAYMENTTYPE_APPSTORE,
                        $newTransactionId,
                        new \DateTime('@' . $now)
                    )
                        ->setUserToken('yyy')
                        ->setSecondaryTransactionId(123)
                );
            },
        ]);
        $json = $this->json($newTransactionId, 'testTransactionOrig', 123, $now * 1000);
        $this->assertNull($this->detect(AwPlus::class, $json, null, false));
        $this->logContains('disabled scan users');
        $this->logNotContains('detected');
        $this->db->seeInDatabase('Cart', [
            'CartID' => $cartId,
            'BillingTransactionID' => $oldTransactionId,
            'AppleTransactionID' => null,
        ]);

        $this->logs->clear();

        $this->assertEquals($user, $this->detect(AwPlus::class, $json, null, true, $provider));
        $this->logContains('new attempt, detected');
        $this->logContains('scan user');
        $this->logContains('update transaction id');
        $this->logContains('migrate ios cart');
        $this->db->seeInDatabase('Cart', [
            'CartID' => $cartId,
            'BillingTransactionID' => $newTransactionId,
            'AppleTransactionID' => 123,
        ]);
    }

    protected function addSubscriptionCart(Usr $user, \DateTime $payDate, $platform, $appleTransactionId, $transactionId = 'testTransaction')
    {
        $cartId = $this->db->shouldHaveInDatabase("Cart", [
            "UserID" => $user->getUserid(),
            "PayDate" => $payDate->format("Y-m-d H:i:s"),
            "PaymentType" => $platform == 'ios' ? Cart::PAYMENTTYPE_APPSTORE : Cart::PAYMENTTYPE_ANDROIDMARKET,
            "PurchaseToken" => "xxx",
            "BillingTransactionID" => $transactionId,
            "AppleTransactionID" => $appleTransactionId,
        ]);
        $this->db->shouldHaveInDatabase("CartItem", [
            "CartID" => $cartId,
            "ID" => $user->getUserid(),
            "TypeID" => AwPlusSubscription::TYPE,
            "Name" => "AwardWallet Plus yearly subscription",
            "Price" => AwPlusSubscription::PRICE,
        ]);

        return $cartId;
    }

    private function detect($productId, $json, $authUser = null, $enableScan = true, $provider = null): ?Usr
    {
        return $this->detector->detect($productId, $json, $provider ?? $this->provider, $authUser, $enableScan);
    }

    private function createUser(array $fields = []): Usr
    {
        return $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find($this->aw->createAwUser(null, null, $fields));
    }

    private function json($transactionId, $originalTransactionId, $appleTransactionId, $purchaseDateMs = null, $originalPurchaseDateMs = null)
    {
        $json = new \stdClass();
        $json->transaction_id = $transactionId;
        $json->original_transaction_id = $originalTransactionId;
        $json->web_order_line_item_id = $appleTransactionId;
        $json->purchase_date_ms = $purchaseDateMs ?? time() * 1000;
        $json->original_purchase_date_ms = $originalPurchaseDateMs ?? time() * 1000;

        return $json;
    }

    private function logContains($str)
    {
        $this->assertStringContainsString((string) $str, $this->getLogs());
    }

    private function logNotContains($str)
    {
        $this->assertStringNotContainsString((string) $str, $this->getLogs());
    }

    private function getLogs()
    {
        return "\n------\n" . implode("\n", array_map(function (array $record) {
            return $record['message'];
        }, $this->logs->getRecords())) . "\n------\n";
    }
}
