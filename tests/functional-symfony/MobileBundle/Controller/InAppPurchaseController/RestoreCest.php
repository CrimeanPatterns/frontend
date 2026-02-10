<?php

namespace AwardWallet\Tests\FunctionalSymfony\MobileBundle\Controller\InAppPurchaseController;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription;
use AwardWallet\MainBundle\Entity\CartItem\BalanceWatchCredit;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\Cart\Manager;
use AwardWallet\MainBundle\Globals\Headers\MobileHeaders;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\InAppPurchase\AppleAppStore\Connector;
use AwardWallet\MainBundle\Service\InAppPurchase\AppleAppStore\Provider as AppleStore;
use AwardWallet\MainBundle\Service\InAppPurchase\AppleAppStore\UserDetector;
use AwardWallet\MainBundle\Service\InAppPurchase\Billing;
use AwardWallet\MainBundle\Service\InAppPurchase\Subscription\AwPlus;
use AwardWallet\MainBundle\Worker\AsyncProcess\Callback\CallbackTask;
use AwardWallet\MainBundle\Worker\AsyncProcess\Callback\CallbackTaskExecutor;
use AwardWallet\MainBundle\Worker\AsyncProcess\Process;
use AwardWallet\Tests\FunctionalSymfony\BaseTraitCest;
use Codeception\Util\Stub;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Handler\TestHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * @group frontend-functional
 * @group mobile
 * @group mobile/billing
 * @group billing
 */
class RestoreCest extends BaseTraitCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    public const APP_VERSION = '4.4.0';

    public const ORIGINAL_TRANSACTION_ID = 100500;
    public const ORIGINAL_WEB_ORDER_LINE_ITEM_ID = 100501;
    public const TRANSACTION_ID = 100502;
    public const WEB_ORDER_LINE_ITEM_ID = 100503;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var TestHandler
     */
    private $logs;

    public function _before(\TestSymfonyGuy $I)
    {
        parent::_before($I);
        $this->router = $I->grabService('router');
        $this->logs = new TestHandler();
        $I->grabService(LoggerInterface::class)->pushHandler($this->logs);
        $I->grabService("monolog.logger.payment")->pushHandler($this->logs);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->haveHttpHeader(MobileHeaders::MOBILE_PLATFORM, "ios");
        $I->haveHttpHeader(MobileHeaders::MOBILE_VERSION, self::APP_VERSION);
        $I->mockService(Process::class, $I->stubMakeEmpty(Process::class, [
            'execute' => fn (CallbackTask $task, $delay = null) =>
                $I->grabService(CallbackTaskExecutor::class)->execute($task, $delay),
        ]));
        $I->executeQuery("
            DELETE FROM Cart WHERE PaymentType = " . Cart::PAYMENTTYPE_APPSTORE . " AND BillingTransactionID IN ('" . implode("', '", [self::ORIGINAL_TRANSACTION_ID, self::TRANSACTION_ID]) . "')
        ");
    }

    public function _after(\TestSymfonyGuy $I)
    {
        $this->router = null;
        $I->grabService(LoggerInterface::class)->popHandler();
        $I->grabService("monolog.logger.payment")->popHandler();
        parent::_after($I);
    }

    public function testEmptyRequest(\TestSymfonyGuy $I)
    {
        $I->sendGET($this->router->generate("aw_mobile_purchase_restore"));
        $I->seeResponseCodeIs(405);
        $I->sendPOST($this->router->generate("aw_mobile_purchase_restore"));
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(["success" => true]);
    }

    public function testSuccess(\TestSymfonyGuy $I)
    {
        $startDate = new \DateTime("-18 MONTH");
        $renewDate = new \DateTime("-6 MONTH");
        /** @var EntityManager $em */
        $em = $I->grabService("doctrine")->getManager();
        /** @var Manager $cartManager */
        $cartManager = $I->grabService("aw.manager.cart");
        /** @var Usr $user */
        $user = $em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find($userId = $I->createAwUser());
        $return = [
            'status' => AppleStore::RESULT_OK,
            'receipt' => [
                'quantity' => 1,
                'bid' => AppleStore::BUNDLE_ID,
                'original_transaction_id' => self::ORIGINAL_TRANSACTION_ID,
                'transaction_id' => self::TRANSACTION_ID,
                'product_id' => AppleStore::PRODUCT_AWPLUS_SUBSCR,
                'purchase_date' => $renewDate->format("Y-m-d H:i:s Etc/GMT"),
                'purchase_date_ms' => $renewDate->getTimestamp() * 1000,
                'web_order_line_item_id' => self::WEB_ORDER_LINE_ITEM_ID,
            ],
        ];
        $I->mockService(AppleStore::class, $this->getProvider($this->getConnector($I, $return), $this->getUserDetector($I, $em, $userId), $I));
        $cartManager->setUser($user);
        $cart = $cartManager->createNewCart();
        $cart->addItem(new AwPlusSubscription());
        $cart->setPaymenttype(PAYMENTTYPE_APPSTORE);
        $cart->setBillingtransactionid(self::ORIGINAL_TRANSACTION_ID);
        $cartManager->markAsPayed($cart);
        $cart->setPaydate($startDate);
        $cartManager->save($cart);

        $I->comment('renew subscription');
        $I->sendPOST($this->router->generate("aw_mobile_purchase_restore"), json_encode([
            'appStoreReceipt' => "12345abc",
        ]));
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(["success" => true]);
        $I->assertEquals(2, $I->grabCountFromDatabase("CartItem", [
            "ID" => $userId,
            "TypeID" => AwPlusSubscription::TYPE,
        ]));
        $this->logContains($I, "ios restore receipts");
        $this->logContains($I, "upgrade user, subscription: product: " . AwPlus::class . ", userId: {$userId}");
        $I->seeInDatabase("Cart", [
            "UserID" => $userId,
            "PaymentType" => Cart::PAYMENTTYPE_APPSTORE,
            "BillingTransactionID" => self::ORIGINAL_TRANSACTION_ID,
            "AppleTransactionID" => null,
        ]);
        $I->seeInDatabase("Cart", [
            "UserID" => $userId,
            "PaymentType" => Cart::PAYMENTTYPE_APPSTORE,
            "BillingTransactionID" => self::TRANSACTION_ID,
            "AppleTransactionID" => self::WEB_ORDER_LINE_ITEM_ID,
            "PurchaseToken" => "12345abc",
        ]);

        $I->comment('update transaction receipt');
        $I->sendPOST($this->router->generate("aw_mobile_purchase_restore"), json_encode([
            'receiptForTransaction' => [self::TRANSACTION_ID => "12345cba"],
        ]));
        $I->assertEquals(2, $I->grabCountFromDatabase("CartItem", [
            "ID" => $userId,
            "TypeID" => AwPlusSubscription::TYPE,
        ]));
        $I->assertEquals(2, $I->grabCountFromDatabase("CartItem", [
            "ID" => $userId,
            "TypeID" => BalanceWatchCredit::TYPE,
        ]));
        $I->seeInDatabase("Cart", [
            "UserID" => $userId,
            "PaymentType" => Cart::PAYMENTTYPE_APPSTORE,
            "BillingTransactionID" => self::TRANSACTION_ID,
            "AppleTransactionID" => self::WEB_ORDER_LINE_ITEM_ID,
            "PurchaseToken" => "12345cba",
        ]);

        $return = [
            'status' => AppleStore::RESULT_OK,
            'receipt' => [
                'in_app' => [
                    [
                        'quantity' => 1,
                        'original_transaction_id' => self::ORIGINAL_TRANSACTION_ID,
                        'transaction_id' => self::ORIGINAL_TRANSACTION_ID,
                        'product_id' => AppleStore::PRODUCT_AWPLUS_SUBSCR,
                        'purchase_date' => $startDate->format("Y-m-d H:i:s Etc/GMT"),
                        'purchase_date_ms' => $startDate->getTimestamp() * 1000,
                        'web_order_line_item_id' => self::ORIGINAL_WEB_ORDER_LINE_ITEM_ID,
                    ],
                    [
                        'quantity' => 1,
                        'original_transaction_id' => self::ORIGINAL_TRANSACTION_ID,
                        'transaction_id' => self::TRANSACTION_ID,
                        'product_id' => AppleStore::PRODUCT_AWPLUS_SUBSCR,
                        'purchase_date' => $renewDate->format("Y-m-d H:i:s Etc/GMT"),
                        'purchase_date_ms' => $renewDate->getTimestamp() * 1000,
                        'web_order_line_item_id' => self::WEB_ORDER_LINE_ITEM_ID,
                    ],
                ],
                'bundle_id' => AppleStore::BUNDLE_ID,
            ],
        ];
        $I->mockService(AppleStore::class, $this->getProvider($this->getConnector($I, $return), $this->getUserDetector($I, $em, $userId), $I));
        $I->comment('update app receipt');
        $I->sendPOST($this->router->generate("aw_mobile_purchase_restore"), json_encode([
            'appStoreReceipt' => "appReceipt123",
        ]));
        $I->seeResponseCodeIs(200);
        $I->assertEquals(2, $I->grabCountFromDatabase("CartItem", [
            "ID" => $userId,
            "TypeID" => AwPlusSubscription::TYPE,
        ]));
        $I->seeInDatabase("Cart", [
            "UserID" => $userId,
            "PaymentType" => Cart::PAYMENTTYPE_APPSTORE,
            "BillingTransactionID" => self::ORIGINAL_TRANSACTION_ID,
            "AppleTransactionID" => self::ORIGINAL_WEB_ORDER_LINE_ITEM_ID,
        ]);
        $I->seeInDatabase("Cart", [
            "UserID" => $userId,
            "PaymentType" => Cart::PAYMENTTYPE_APPSTORE,
            "BillingTransactionID" => self::TRANSACTION_ID,
            "AppleTransactionID" => self::WEB_ORDER_LINE_ITEM_ID,
            "PurchaseToken" => "12345cba",
        ]);
        $I->seeInDatabase("Usr", [
            "UserID" => $userId,
            "IosReceipt" => "appReceipt123",
        ]);
    }

    public function testUpdateIosRestoredReceipt(\TestSymfonyGuy $I)
    {
        /** @var EntityManager $em */
        $em = $I->grabService("doctrine.orm.entity_manager");
        $userRep = $em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);
        $userId = $I->createAwUser(null, null, [
            "IosRestoredReceipt" => 0,
        ]);
        $user = $userRep->find($userId);
        $date = time();
        $return = [
            'status' => AppleStore::RESULT_OK,
            'receipt' => [
                'quantity' => 1,
                'bid' => AppleStore::BUNDLE_ID,
                'original_transaction_id' => self::ORIGINAL_TRANSACTION_ID,
                'transaction_id' => self::TRANSACTION_ID,
                'product_id' => AppleStore::PRODUCT_AWPLUS_SUBSCR,
                'purchase_date' => date("Y-m-d H:i:s", $date) . " Etc/GMT",
                'purchase_date_ms' => $date * 1000,
                'web_order_line_item_id' => self::WEB_ORDER_LINE_ITEM_ID,
            ],
        ];
        $I->mockService(AppleStore::class, $this->getProvider($this->getConnector($I, $return), $this->getUserDetector($I, $em, $userId), $I));
        /** @var Manager $cartManager */
        $cartManager = $I->grabService("aw.manager.cart");
        $cartManager->setUser($user);
        $cart = $cartManager->createNewCart();
        $cart->addItem(new AwPlusSubscription());
        $cart->setPaymenttype(PAYMENTTYPE_APPSTORE);
        $cart->setBillingtransactionid(self::ORIGINAL_TRANSACTION_ID);
        $cart->setAppleTransactionID(self::ORIGINAL_WEB_ORDER_LINE_ITEM_ID);
        $cartManager->markAsPayed($cart);

        $I->sendPOST($this->router->generate("aw_mobile_purchase_restore"), json_encode([
            'appStoreReceipt' => "12345abc",
        ]));
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(["success" => true]);
        $I->seeInDatabase("Usr", [
            "UserID" => $userId,
            "IosRestoredReceipt" => 1,
        ]);

        $user->setIosRestoredReceipt(false);
        $em->flush();

        $I->sendGET($this->router->generate('awm_new_login_status') . '?_switch_user=' . $user->getLogin());
        $I->sendPOST($this->router->generate("aw_mobile_purchase_restore"));
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(["success" => true]);
        $I->seeInDatabase("Usr", [
            "UserID" => $userId,
            "IosRestoredReceipt" => 1,
        ]);
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
    private function logContains(\TestSymfonyGuy $I, $str)
    {
        $I->assertStringContainsString($str, $this->getLogs());
    }

    /**
     * @return Connector
     */
    private function getConnector(\TestSymfonyGuy $I, $return)
    {
        return $I->stubMake(Connector::class, [
            'sendRequest' => Stub::atLeastOnce(function () use ($return) {
                return json_encode($return);
            }),
        ]);
    }

    private function getUserDetector(\TestSymfonyGuy $I, EntityManagerInterface $em, int $userId): UserDetector
    {
        return $I->stubMake(UserDetector::class, [
            'detect' => Stub::atLeastOnce(function () use ($em, $userId) {
                return $em->getRepository(Usr::class)->find($userId);
            }),
        ]);
    }

    /**
     * @return AppleStore
     */
    private function getProvider(Connector $connector, UserDetector $detector, \TestSymfonyGuy $I)
    {
        return new AppleStore(
            $connector,
            $I->grabService(LoggerInterface::class),
            $I->grabService("translator"),
            $I->grabService("doctrine")->getManager(),
            $I->grabService("aw.api.versioning"),
            $detector,
            $I->grabService(LocalizeService::class),
            $I->getContainer()->getParameter("aw.mobile.iap_apple_secret"),
            true
        );
    }
}
