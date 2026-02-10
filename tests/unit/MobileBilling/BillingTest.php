<?php

namespace AwardWallet\Test\Unit\MobileBilling;

use AwardWallet\MainBundle\Entity\BalanceWatchCreditsTransaction;
use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription;
use AwardWallet\MainBundle\Entity\CartItem\BalanceWatchCredit;
use AwardWallet\MainBundle\Entity\CartItem\Discount;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Service\Billing\RecurringManager;
use AwardWallet\MainBundle\Service\InAppPurchase\AbstractSubscription;
use AwardWallet\MainBundle\Service\InAppPurchase\Billing;
use AwardWallet\MainBundle\Service\InAppPurchase\Subscription\AwPlus as SubscriptionAwPlus;
use AwardWallet\Tests\Unit\BaseUserTest;
use Psr\Log\LoggerInterface;

/**
 * @group mobile
 * @group frontend-unit
 * @group mobile/billing
 * @group billing
 */
class BillingTest extends BaseUserTest
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
    }

    public function _after()
    {
        $this->billing = null;
        $this->em->remove($this->user);
        $this->em->flush();
        parent::_after();
    }

    public function testSuccess()
    {
        $this->assertEquals(ACCOUNT_LEVEL_FREE, $this->user->getAccountlevel());
        /** @var AbstractSubscription $purchase */
        $purchase = AbstractSubscription::create(
            SubscriptionAwPlus::class,
            $this->user,
            Cart::PAYMENTTYPE_APPSTORE,
            'testTransaction',
            $payDate = new \DateTime("-1 DAY")
        );
        $purchase->setPurchaseToken('xxx');

        $this->billing->processing($purchase);
        $this->em->refresh($this->user);
        $this->assertEquals(ACCOUNT_LEVEL_AWPLUS, $this->user->getAccountlevel());
        $subscriptionCartId = $this->db->grabFromDatabase("Cart", "CartID", [
            "UserID" => $this->user->getUserid(),
            "BillingTransactionID" => "testTransaction",
            "PayDate" => $payDate->format("Y-m-d H:i:s"),
            "Source" => Cart::SOURCE_USER,
        ]);
        $this->assertNotEmpty($subscriptionCartId);
        $cart = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Cart::class)->find($subscriptionCartId);
        $this->assertNotEmpty($cart);
        $this->assertTrue($cart->hasItemsByType([AwPlusSubscription::TYPE]));
        $this->assertFalse($cart->hasItemsByType([Discount::TYPE]));

        // BalanceWatchCredits
        $this->assertEquals(1, $this->user->getBalanceWatchCredits());
        $this->db->seeInDatabase("BalanceWatchCreditsTransaction", [
            "UserID" => $this->user->getUserid(),
            "AccountID" => null,
            "TransactionType" => BalanceWatchCreditsTransaction::TYPE_GIFT,
            "Amount" => 0,
            "Balance" => 1,
        ]);
        $this->assertTrue($cart->hasItemsByType([BalanceWatchCredit::TYPE]));
        $this->em->refresh($this->user);
        $this->assertEquals(Usr::SUBSCRIPTION_MOBILE, $this->user->getSubscription());
    }

    public function testSuccessRecurring()
    {
        $this->assertEquals(ACCOUNT_LEVEL_FREE, $this->user->getAccountlevel());
        /** @var AbstractSubscription $purchase */
        $purchase = AbstractSubscription::create(
            SubscriptionAwPlus::class,
            $this->user,
            Cart::PAYMENTTYPE_APPSTORE,
            'testTransaction',
            new \DateTime()
        );
        $purchase->setPurchaseToken('xxx');
        $purchase->setRecurring(true);
        $this->billing->processing($purchase);
        $this->em->refresh($this->user);
        $this->assertEquals(ACCOUNT_LEVEL_AWPLUS, $this->user->getAccountlevel());
        $this->db->seeInDatabase("Cart", [
            "UserID" => $this->user->getUserid(),
            "BillingTransactionID" => "testTransaction",
            "Source" => Cart::SOURCE_RECURRING,
        ]);
    }

    public function testCancel()
    {
        $this->assertEquals(ACCOUNT_LEVEL_FREE, $this->user->getAccountlevel());
        $this->assertEmpty($this->user->getPlusExpirationDate());

        /** @var AbstractSubscription $purchase */
        $purchase = AbstractSubscription::create(
            SubscriptionAwPlus::class,
            $this->user,
            Cart::PAYMENTTYPE_APPSTORE,
            $transactionId = 'cancel' . StringHandler::getRandomCode(7),
            $payDate = new \DateTime("-1 DAY")
        );
        $purchase->setPurchaseToken('xxx');
        $this->billing->processing($purchase);
        $this->em->refresh($this->user);

        $this->assertEquals(ACCOUNT_LEVEL_AWPLUS, $this->user->getAccountlevel());
        $this->assertNotEmpty($this->user->getPlusExpirationDate());
        $this->assertEquals(1, $this->user->getBalanceWatchCredits());
        $this->db->seeInDatabase("CartItem", [
            "ID" => $this->user->getUserid(),
            "TypeID" => BalanceWatchCredit::TYPE,
        ]);
        $this->assertEquals((clone $payDate)->modify("+1 year")->format("Y-m-d"), $this->user->getPlusExpirationDate()->format("Y-m-d"));

        // cancel
        /** @var AbstractSubscription $purchase */
        $purchase = AbstractSubscription::create(
            SubscriptionAwPlus::class,
            $this->user,
            Cart::PAYMENTTYPE_APPSTORE,
            $transactionId,
            $payDate
        );
        $purchase->setCancellationDate($payDate);
        $this->billing->processing($purchase);
        $this->em->refresh($this->user);

        $this->assertEmpty($this->user->getPlusExpirationDate());
        $this->assertEquals(ACCOUNT_LEVEL_FREE, $this->user->getAccountlevel());

        $this->assertEquals(0, $this->user->getBalanceWatchCredits());
        $this->db->seeInDatabase("BalanceWatchCreditsTransaction", [
            "UserID" => $this->user->getUserid(),
            "AccountID" => null,
            "TransactionType" => BalanceWatchCreditsTransaction::TYPE_REFUND,
            "Amount" => 1,
            "Balance" => 0,
        ]);

        $this->db->dontSeeInDatabase("CartItem", [
            "ID" => $this->user->getUserid(),
            "TypeID" => BalanceWatchCredit::TYPE,
        ]);
    }

    public function testUpdateIosReceipts()
    {
        $this->assertEquals(ACCOUNT_LEVEL_FREE, $this->user->getAccountlevel());
        /** @var AbstractSubscription $purchase */
        $purchase = AbstractSubscription::create(
            SubscriptionAwPlus::class,
            $this->user,
            Cart::PAYMENTTYPE_APPSTORE,
            'testTransaction',
            new \DateTime("-6 MONTHS")
        );
        $purchase->setUserToken('userToken1');
        $purchase->setPurchaseToken('purchaseToken1');
        $this->billing->processing($purchase);
        $this->em->refresh($this->user);
        $this->assertEquals(ACCOUNT_LEVEL_AWPLUS, $this->user->getAccountlevel());
        $this->assertEquals("userToken1", $this->user->getIosReceipt());
        $this->db->seeInDatabase("Cart", [
            "UserID" => $this->user->getUserid(),
            "PaymentType" => Cart::PAYMENTTYPE_APPSTORE,
            "BillingTransactionID" => "testTransaction",
            "PurchaseToken" => "purchaseToken1",
        ]);
        $purchase->setUserToken("userToken2");
        $purchase->setPurchaseToken("purchaseToken2");
        $this->billing->processing($purchase, true);
        $this->em->refresh($this->user);
        $this->assertEquals("userToken2", $this->user->getIosReceipt());
        $this->db->seeInDatabase("Cart", [
            "UserID" => $this->user->getUserid(),
            "PaymentType" => Cart::PAYMENTTYPE_APPSTORE,
            "BillingTransactionID" => "testTransaction",
            "PurchaseToken" => "purchaseToken2",
        ]);
    }

    public function testSimilarTransactions()
    {
        /** @var AbstractSubscription $purchase */
        $purchase = AbstractSubscription::create(
            SubscriptionAwPlus::class,
            $this->user,
            Cart::PAYMENTTYPE_APPSTORE,
            'testTransaction',
            $startDate = new \DateTime("-6 MONTHS")
        );
        $purchase->setUserToken('userToken1');
        $purchase->setPurchaseToken('purchaseToken1');
        $this->billing->processing($purchase);
        $this->em->refresh($this->user);
        $this->assertEquals(ACCOUNT_LEVEL_AWPLUS, $this->user->getAccountlevel());
        $this->db->seeInDatabase("Cart", [
            "UserID" => $this->user->getUserid(),
            "PaymentType" => Cart::PAYMENTTYPE_APPSTORE,
            "BillingTransactionID" => "testTransaction",
            "PurchaseToken" => "purchaseToken1",
            "CartAttrHash" => $code = sprintf("%s|%s|%s", $this->user->getUserid(), Cart::PAYMENTTYPE_APPSTORE, $startDate->format("Y-m-d H:i:s")),
        ]);

        $purchase->setTransactionId("testTransaction2");
        $purchase->setExpiresDate(new \DateTime("+6 MONTHS +3 HOURS"));
        $this->billing->processing($purchase);
        $this->em->refresh($this->user);
        $this->assertEquals(1, $this->db->grabCountFromDatabase("CartItem", [
            "ID" => $this->user->getUserid(),
            "TypeID" => AwPlusSubscription::TYPE,
        ]));
        $this->db->seeInDatabase("Cart", [
            "UserID" => $this->user->getUserid(),
            "PaymentType" => Cart::PAYMENTTYPE_APPSTORE,
            "BillingTransactionID" => "testTransaction2",
            "PurchaseToken" => "purchaseToken1",
            "CartAttrHash" => $code,
        ]);

        $purchase->setTransactionId("testTransaction");
        $this->billing->processing($purchase);
        $this->em->refresh($this->user);
        $this->db->seeInDatabase("Cart", [
            "UserID" => $this->user->getUserid(),
            "PaymentType" => Cart::PAYMENTTYPE_APPSTORE,
            "BillingTransactionID" => "testTransaction",
            "PurchaseToken" => "purchaseToken1",
            "CartAttrHash" => $code,
        ]);
    }
}
