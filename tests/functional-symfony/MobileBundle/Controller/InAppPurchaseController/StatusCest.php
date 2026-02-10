<?php

namespace AwardWallet\Tests\FunctionalSymfony\MobileBundle\Controller\InAppPurchaseController;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription;
use AwardWallet\MainBundle\Entity\CartItem\BalanceWatchCredit;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\Cart\Manager;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\Billing\AppleStoreCallbackConsumer;
use AwardWallet\MainBundle\Service\Billing\AppleStoreCallbackTask;
use AwardWallet\MainBundle\Service\InAppPurchase\AppleAppStore\Connector;
use AwardWallet\MainBundle\Service\InAppPurchase\AppleAppStore\Provider as AppleStore;
use AwardWallet\MainBundle\Service\InAppPurchase\AppleAppStore\UserDetector;
use AwardWallet\MainBundle\Service\TaskScheduler\Producer;
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
class StatusCest extends BaseTraitCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    public const ORIGINAL_TRANSACTION_ID = 100500;
    public const ORIGINAL_WEB_ORDER_LINE_ITEM_ID = 100501;
    public const TRANSACTION_ID = 100502;
    public const WEB_ORDER_LINE_ITEM_ID = 100503;

    /**
     * @var RouterInterface
     */
    private $router;

    /** @var TestHandler */
    private $logs;

    public function _before(\TestSymfonyGuy $I)
    {
        parent::_before($I);
        $I->mockService(Producer::class, $I->stubMakeEmpty(Producer::class, [
            'publish' => function (AppleStoreCallbackTask $task, $delay = null) use ($I) {
                /** @var AppleStoreCallbackConsumer $consumer */
                $consumer = $I->grabService(AppleStoreCallbackConsumer::class);
                $consumer->consume($task);
            },
        ]));
        $this->router = $I->grabService('router');
        $this->logs = new TestHandler();
        $I->grabService(LoggerInterface::class)->pushHandler($this->logs);
        $I->grabService("monolog.logger.payment")->pushHandler($this->logs);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->executeQuery("DELETE FROM Cart WHERE PaymentType = " . Cart::PAYMENTTYPE_APPSTORE . " AND BillingTransactionID IN ('" . implode("','", [self::ORIGINAL_TRANSACTION_ID, self::TRANSACTION_ID]) . "')");
    }

    public function _after(\TestSymfonyGuy $I)
    {
        $this->router = null;
        $I->grabService(LoggerInterface::class)->popHandler();
        $I->grabService("monolog.logger.payment")->popHandler();
        parent::_after($I);
    }

    public function testRequestType(\TestSymfonyGuy $I)
    {
        $I->sendGET($this->router->generate("aw_mobile_purchase_status"));
        $I->seeResponseCodeIs(405);
        $I->sendPOST($this->router->generate("aw_mobile_purchase_status"));
        $I->seeResponseCodeIs(200);
    }

    public function testInitialBuy(\TestSymfonyGuy $I)
    {
        $I->sendPOST($this->router->generate("aw_mobile_purchase_status"), [
            'notification_type' => 'INITIAL_BUY',
            'password' => $I->getContainer()->getParameter("aw.mobile.iap_apple_secret"),
            'environment' => 'PROD',
            'latest_receipt' => 'abc',
        ]);
        $this->logContains($I, "Initial buy");
        $I->seeResponseCodeIs(200);
    }

    public function testWrongPassword(\TestSymfonyGuy $I)
    {
        $I->sendPOST($this->router->generate("aw_mobile_purchase_status"), [
            'notification_type' => 'RENEWAL',
            'password' => 123,
            'environment' => 'PROD',
            'latest_receipt' => 'abc',
        ]);
        $this->logContains($I, "Wrong password");
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(["error" => "Wrong password"]);
    }

    public function testBadEnvironment(\TestSymfonyGuy $I)
    {
        $I->sendPOST($this->router->generate("aw_mobile_purchase_status"), [
            'notification_type' => 'RENEWAL',
            'password' => $I->getContainer()->getParameter("aw.mobile.iap_apple_secret"),
            'environment' => 'SANDBOX',
            'latest_receipt' => 'abc',
        ]);
        $this->logContains($I, "Bad environment");
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(["error" => "Bad environment"]);
    }

    public function testReceipt(\TestSymfonyGuy $I)
    {
        $I->sendPOST($this->router->generate("aw_mobile_purchase_status"), $request = [
            'notification_type' => 'RENEWAL',
            'password' => $I->getContainer()->getParameter("aw.mobile.iap_apple_secret"),
            'environment' => 'PROD',
            'latest_receipt' => '',
        ]);
        $this->logContains($I, "Empty receipt");
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(["error" => "Empty receipt"]);

        unset($request['latest_receipt']);
        $I->sendPOST($this->router->generate("aw_mobile_purchase_status"), $request = array_merge($request, [
            'latest_expired_receipt' => '',
        ]));
        $this->logContains($I, "Empty receipt");
    }

    public function testSuccess(\TestSymfonyGuy $I)
    {
        $startDate = new \DateTime("-18 MONTH");
        $renewDate = new \DateTime("-6 MONTH");
        /** @var EntityManager $em */
        $em = $I->grabService("doctrine")->getManager();
        /** @var Manager $cartManager */
        $cartManager = $I->grabService("aw.manager.cart");
        $userId = $I->createAwUser();
        /** @var Usr $user */
        $user = $em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find($userId);
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
            'latest_receipt_info' => [
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

        $I->sendPOST($this->router->generate("aw_mobile_purchase_status"), $request = [
            'notification_type' => 'RENEWAL',
            'password' => $I->getContainer()->getParameter("aw.mobile.iap_apple_secret"),
            'environment' => 'PROD',
            'latest_receipt' => '123456',
        ]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(["success" => true]);
        $I->assertEquals(2, $I->grabCountFromDatabase("CartItem", [
            "ID" => $user->getUserid(),
            "TypeID" => AwPlusSubscription::TYPE,
        ]));
        $I->assertEquals(2, $I->grabCountFromDatabase("CartItem", [
            "ID" => $user->getUserid(),
            "TypeID" => BalanceWatchCredit::TYPE,
        ]));
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
            'sendRequest' => Stub::exactly(1, function () use ($return) {
                return json_encode($return);
            }),
        ]);
    }

    private function getUserDetector(\TestSymfonyGuy $I, EntityManagerInterface $em, int $userId): UserDetector
    {
        return $I->stubMake(UserDetector::class, [
            'detect' => Stub::atLeastOnce(function () use ($userId, $em) {
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
