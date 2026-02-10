<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\Cart;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem\AwPlus;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusPrepaid;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusRecurring;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription6Months;
use AwardWallet\MainBundle\Entity\CartItem\BalanceWatchCredit;
use AwardWallet\MainBundle\Entity\CartItem\Discount;
use AwardWallet\MainBundle\Entity\CartItem\OneCard;
use AwardWallet\MainBundle\Entity\Coupon;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\Cart\SubscriptionPeriod;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Service\Billing\ExpirationCalculator;
use AwardWallet\MainBundle\Service\Billing\PaypalRestApi;
use Codeception\Example;
use Codeception\Util\Stub;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @group frontend-functional
 */
class IpnControllerCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    public const URL = '/paypal/IPNListener.php';

    public function sendBadRequest(\TestSymfonyGuy $I)
    {
        $I->sendPOST(self::URL, ['some' => 'param']);
        $I->seeResponseCodeIs(400);
    }

    public function successWrongStatus(\TestSymfonyGuy $I)
    {
        $I->sendPOST(self::URL, ['txn_type' => 'recurring_payment', 'payment_status' => 'Something']);
        $I->seeResponseCodeIs(200);
    }

    public function successProfileNotFound(\TestSymfonyGuy $I)
    {
        $I->sendPOST(self::URL, [
            'txn_type' => 'recurring_payment',
            'txn_id' => bin2hex(random_bytes(6)),
            'payment_status' => 'Completed',
            'recurring_payment_id' => StringHandler::getRandomCode(10),
        ]);
        $I->seeResponseCodeIs(200);
        $email = $I->grabLastMailMessageBody();
        $I->assertStringContainsString("can't find user with recurring profile", $email);
    }

    public function successNoCarts(\TestSymfonyGuy $I)
    {
        $profileId = StringHandler::getRandomCode(10);
        $I->createAwUser(null, null, ['PaypalRecurringProfileID' => $profileId, 'Subscription' => Usr::SUBSCRIPTION_PAYPAL]);

        $I->sendPOST(self::URL, [
            'txn_type' => 'recurring_payment',
            'txn_id' => bin2hex(random_bytes(6)),
            'payment_status' => 'Completed',
            'recurring_payment_id' => $profileId,
            'payment_cycle' => 'every 6 Months',
            'payment_gross' => 5,
        ]);
        $I->seeResponseCodeIs(200);
        $email = $I->grabLastMailMessageBody();
        $I->assertStringContainsString("cart with subscription not found", $email);
    }

    public function successInvalidCart(\TestSymfonyGuy $I)
    {
        $profileId = StringHandler::getRandomCode(10);
        $userId = $I->createAwUser(null, null, ['PaypalRecurringProfileID' => $profileId, 'Subscription' => Usr::SUBSCRIPTION_PAYPAL]);
        $I->addUserPayment($userId, PAYMENTTYPE_CREDITCARD, new OneCard());

        $I->sendPOST(self::URL, [
            'txn_type' => 'recurring_payment',
            'txn_id' => bin2hex(random_bytes(6)),
            'payment_status' => 'Completed',
            'recurring_payment_id' => $profileId,
            'payment_gross' => 0,
        ]);
        $I->seeResponseCodeIs(200);
        $email = $I->grabLastMailMessageBody();
        $I->assertStringContainsString("cart with subscription not found", $email);
    }

    public function successInvalidPaymentType(\TestSymfonyGuy $I)
    {
        $profileId = StringHandler::getRandomCode(10);
        $userId = $I->createAwUser(null, null, ['PaypalRecurringProfileID' => $profileId, 'Subscription' => Usr::SUBSCRIPTION_PAYPAL]);
        $I->addUserPayment($userId, PAYMENTTYPE_APPSTORE, new AwPlusSubscription());

        $I->sendPOST(self::URL, [
            'txn_type' => 'recurring_payment',
            'txn_id' => bin2hex(random_bytes(6)),
            'payment_status' => 'Completed',
            'recurring_payment_id' => $profileId,
            'payment_gross' => 0,
        ]);
        $I->seeResponseCodeIs(200);
        $email = $I->grabLastMailMessageBody();
        $I->assertStringContainsString("cart found, but payment type is invalid", $email);
    }

    public function successInvalidPeriod(\TestSymfonyGuy $I)
    {
        $profileId = StringHandler::getRandomCode(10);
        $userId = $I->createAwUser(null, null, ['PaypalRecurringProfileID' => $profileId, 'Subscription' => Usr::SUBSCRIPTION_PAYPAL]);
        $I->addUserPayment($userId, PAYMENTTYPE_CREDITCARD, new AwPlusSubscription());

        $I->sendPOST(self::URL, [
            'txn_type' => 'recurring_payment',
            'txn_id' => bin2hex(random_bytes(6)),
            'payment_status' => 'Completed',
            'recurring_payment_id' => $profileId,
            'payment_cycle' => 'every 6 Months',
            'payment_gross' => 0,
        ]);
        $I->seeResponseCodeIs(200);
        $email = $I->grabLastMailMessageBody();
        $I->assertStringContainsString("ipn period does not match cart period", $email);
    }

    public function successSixMonths(\TestSymfonyGuy $I)
    {
        $profileId = StringHandler::getRandomCode(10);
        $userId = $I->createAwUser(null, null, ['PaypalRecurringProfileID' => $profileId, 'Subscription' => Usr::SUBSCRIPTION_PAYPAL]);
        $cart = $I->addUserPayment($userId, PAYMENTTYPE_CREDITCARD, new AwPlus(), [new AwPlusRecurring()]);
        $oldExpiration = $I->getContainer()->get(ExpirationCalculator::class)->getAccountExpiration($userId);
        $I->assertGreaterThan(time(), $oldExpiration['date']);
        $tranId = StringUtils::getRandomCode(20);

        $I->sendPOST(self::URL, [
            'txn_type' => 'recurring_payment',
            'txn_id' => $tranId,
            'payment_status' => 'Completed',
            'recurring_payment_id' => $profileId,
            'payment_cycle' => 'every 6 Months',
            'payment_gross' => 5,
        ]);
        $I->seeResponseCodeIs(200);
        $newExpiration = $I->getContainer()->get(ExpirationCalculator::class)->getAccountExpiration($userId);
        $newCartId = $I->grabFromDatabase("Cart", "CartID", ["UserID" => $userId, "BillingTransactionID" => $tranId]);
        $I->assertNotEmpty($newCartId);
        $I->assertEquals(6, $this->dateDiffInMonths($oldExpiration['date'], $newExpiration['date']));
        $I->dontSeeInDatabase('CartItem', [
            'ID' => $userId,
            'TypeID' => BalanceWatchCredit::TYPE,
        ]);
        $email = $I->grabLastMail();
        $I->assertStringContainsString('Order ID: ' . $newCartId, $email->getBody());
        $I->assertStringContainsString('You have successfully paid *$5*', $email->getBody());
        $I->assertStringContainsString('VISA', $email->getBody());
        $I->assertStringContainsString('XXXXXXXXXXXX5678', $email->getBody());

        $I->wantToTest("ignore repeated request");
        $I->sendPOST(self::URL, [
            'txn_type' => 'recurring_payment',
            'txn_id' => $tranId,
            'payment_status' => 'Completed',
            'recurring_payment_id' => $profileId,
            'payment_cycle' => 'every 6 Months',
            'payment_gross' => 5,
        ]);
        $I->seeResponseCodeIs(200);
    }

    /**
     * @dataProvider repeatedStandardSubscriptionDataProvider
     */
    public function repeatedStandardSubscription(\TestSymfonyGuy $I, Example $example)
    {
        $profileId = StringHandler::getRandomCode(10);
        $userId = $I->createAwUser(null, null, [
            'PaypalRecurringProfileID' => $profileId,
            'Subscription' => Usr::SUBSCRIPTION_PAYPAL,
            'SubscriptionPrice' => $example['SubscriptionPrice'],
            'SubscriptionPeriod' => $example['SubscriptionPeriod'],
            'SubscriptionType' => $example['SubscriptionType'],
        ]);
        $I->addUserPayment($userId, PAYMENTTYPE_CREDITCARD, new $example['SubscriptionCartItemClass'](), $example['SubscriptionExtraItems']);
        $I->updateInDatabase("Usr", ["SubscriptionPrice" => $example['SubscriptionPrice']], ["UserID" => $userId]);
        $I->grabService(EntityManagerInterface::class)->flush();

        $oldExpiration = $I->getContainer()->get(ExpirationCalculator::class)->getAccountExpiration($userId);
        $I->assertGreaterThan(time(), $oldExpiration['date']);
        $tranId = StringUtils::getRandomCode(20);

        $I->sendPOST(self::URL, [
            'txn_type' => 'recurring_payment',
            'txn_id' => $tranId,
            'payment_status' => 'Completed',
            'recurring_payment_id' => $profileId,
            'payment_cycle' => $example['payment_cycle'],
            'payment_gross' => $example['payment_gross'],
        ]);
        $I->seeResponseCodeIs(200);
        $newExpiration = $I->getContainer()->get(ExpirationCalculator::class)->getAccountExpiration($userId);
        $newCartId = $I->grabFromDatabase("Cart", "CartID", ["UserID" => $userId, "BillingTransactionID" => $tranId]);
        $I->assertNotEmpty($newCartId);
        $I->assertEquals($example['expectedPlusMonths'], $this->dateDiffInMonths($oldExpiration['date'], $newExpiration['date']));

        if ($example['expectedBalanceWatch']) {
            $I->seeInDatabase('CartItem', [
                'ID' => $userId,
                'TypeID' => BalanceWatchCredit::TYPE,
            ]);
        } else {
            $I->dontSeeInDatabase('CartItem', [
                'ID' => $userId,
                'TypeID' => BalanceWatchCredit::TYPE,
            ]);
        }
        $I->seeInDatabase('CartItem', [
            'ID' => $userId,
            'TypeID' => $example['expectedTypeId'],
            'Price' => $example['expectedPrice'],
        ]);
        $I->assertEquals($example['expectedBalanceWatch'] ? 2 : 1, $I->grabCountFromDatabase("CartItem", ["CartID" => $newCartId]));
        $email = $I->grabLastMail();
        $I->assertStringContainsString('Order ID: ' . $newCartId, $email->getBody());
        $I->assertStringContainsString('You have successfully paid *$' . $example['expectedPrice'] . '*', $email->getBody());
        $I->assertStringContainsString('VISA', $email->getBody());
        $I->assertStringContainsString('XXXXXXXXXXXX5678', $email->getBody());
    }

    public function successSixMonths20Bucks(\TestSymfonyGuy $I)
    {
        $profileId = StringHandler::getRandomCode(10);
        $userId = $I->createAwUser(null, null, ['PaypalRecurringProfileID' => $profileId, 'Subscription' => Usr::SUBSCRIPTION_PAYPAL]);
        $cart = $I->addUserPayment($userId, PAYMENTTYPE_PAYPAL, new AwPlus(), [new AwPlusRecurring()]);
        $oldExpiration = $I->getContainer()->get(ExpirationCalculator::class)->getAccountExpiration($userId);
        $I->assertGreaterThan(time(), $oldExpiration['date']);
        $tranId = StringUtils::getRandomCode(20);

        $I->sendPOST(self::URL, [
            'txn_type' => 'recurring_payment',
            'txn_id' => $tranId,
            'payment_status' => 'Completed',
            'recurring_payment_id' => $profileId,
            'payment_cycle' => 'every 6 Months',
            'payment_gross' => 20,
        ]);
        $I->seeResponseCodeIs(200);
        $newExpiration = $I->getContainer()->get(ExpirationCalculator::class)->getAccountExpiration($userId);
        $newCartId = $I->grabFromDatabase("Cart", "CartID", ["UserID" => $userId, "BillingTransactionID" => $tranId]);
        $I->assertNotEmpty($newCartId);
        $I->assertEquals(20, $I->grabFromDatabase("CartItem", "Price", ["CartID" => $newCartId]));
        $I->assertEquals(PAYMENTTYPE_PAYPAL, $I->grabFromDatabase("Cart", "PaymentType", ["CartID" => $newCartId]));
        $I->assertEquals(6, $this->dateDiffInMonths($oldExpiration['date'], $newExpiration['date']));
        $I->dontSeeInDatabase('CartItem', [
            'ID' => $userId,
            'TypeID' => BalanceWatchCredit::TYPE,
        ]);
        $email = $I->grabLastMail();
        $I->assertStringContainsString('Order ID: ' . $newCartId, $email->getBody());
        $I->assertStringContainsString('You have successfully paid *$20*', $email->getBody());
        $I->assertStringContainsString('Payment method: PayPal', $email->getBody());
    }

    /**
     * @dataProvider successSixMonthsAndOneCardDataProvider
     */
    public function successSixMonthsAndOneCard(\TestSymfonyGuy $I, Example $example)
    {
        $profileId = StringHandler::getRandomCode(10);
        $userId = $I->createAwUser(null, null, ['PaypalRecurringProfileID' => $profileId, 'Subscription' => Usr::SUBSCRIPTION_PAYPAL]);
        $cart = $I->addUserPayment($userId, PAYMENTTYPE_PAYPAL, new AwPlus(), [new AwPlusRecurring(), new OneCard(), (new Discount())->setPrice(-5)]);
        $oldExpiration = $I->getContainer()->get(ExpirationCalculator::class)->getAccountExpiration($userId);
        $I->assertGreaterThan(time(), $oldExpiration['date']);
        $tranId = StringUtils::getRandomCode(20);

        $I->sendPOST(self::URL, [
            'txn_type' => 'recurring_payment',
            'txn_id' => $tranId,
            'payment_status' => 'Completed',
            'recurring_payment_id' => $profileId,
            'payment_cycle' => 'every 6 Months',
            'payment_gross' => $example['total'],
        ]);
        $I->seeResponseCodeIs(200);
        $newExpiration = $I->getContainer()->get(ExpirationCalculator::class)->getAccountExpiration($userId);
        $newCartId = $I->grabFromDatabase("Cart", "CartID", ["UserID" => $userId, "BillingTransactionID" => $tranId]);
        $I->assertNotEmpty($newCartId);
        $I->assertEquals($example['total'], $I->grabFromDatabase("CartItem", "Price", ["CartID" => $newCartId, "TypeID" => AwPlus::TYPE]));
        $I->assertEquals(PAYMENTTYPE_PAYPAL, $I->grabFromDatabase("Cart", "PaymentType", ["CartID" => $newCartId]));
        $I->assertEquals(6, $this->dateDiffInMonths($oldExpiration['date'], $newExpiration['date']));
        $I->dontSeeInDatabase('CartItem', [
            'ID' => $userId,
            'TypeID' => BalanceWatchCredit::TYPE,
        ]);
        $I->dontSeeInDatabase('CartItem', [
            'CartID' => $newCartId,
            'TypeID' => OneCard::TYPE,
        ]);
        $email = $I->grabLastMail();
        $I->assertStringContainsString('Order ID: ' . $newCartId, $email->getBody());
        $I->assertStringContainsString('You have successfully paid *$' . $example['total'] . '*', $email->getBody());
        $I->assertStringContainsString('Payment method: PayPal', $email->getBody());
    }

    public function successSixMonthsAndOneCardDataProvider()
    {
        return [['total' => 5], ['total' => 10]];
    }

    public function success1YearAndOneCard(\TestSymfonyGuy $I)
    {
        $profileId = StringHandler::getRandomCode(10);
        $userId = $I->createAwUser(null, null, ['PaypalRecurringProfileID' => $profileId, 'Subscription' => Usr::SUBSCRIPTION_PAYPAL]);
        /** @var Usr $user */
        $user = $I->getContainer()->get('doctrine')->getRepository(Usr::class)->find($userId);

        $cartManager = $I->getContainer()->get("aw.manager.cart");
        $cartManager->setUser($user);
        $cart = $cartManager->createNewCart();
        $cart->setPaymenttype(Cart::PAYMENTTYPE_PAYPAL);
        $cartManager->addAwSubscriptionItem($cart, new \DateTime());
        $cart->addItem(new OneCard());
        $cartManager->markAsPayed($cart);
        $email = $I->grabLastMail();
        $I->assertStringContainsString('You have successfully paid *$' . (AwPlusSubscription::PRICE + OneCard::PRICE) . '*', $email->getBody());

        $oldExpiration = $I->getContainer()->get(ExpirationCalculator::class)->getAccountExpiration($userId);
        $I->assertGreaterThan(time(), $oldExpiration['date']);
        $tranId = StringUtils::getRandomCode(20);

        $I->sendPOST(self::URL, [
            'txn_type' => 'recurring_payment',
            'txn_id' => bin2hex(random_bytes(6)),
            'payment_status' => 'Completed',
            'recurring_payment_id' => $profileId,
            'payment_cycle' => 'every 12 Months',
            'payment_gross' => AwPlusSubscription::PRICE,
        ]);
        $I->seeResponseCodeIs(200);
        $newExpiration = $I->getContainer()->get(ExpirationCalculator::class)->getAccountExpiration($userId);
        $I->assertEquals(12, $this->dateDiffInMonths($oldExpiration['date'], $newExpiration['date']));
        $I->assertEquals(2, $I->grabCountFromDatabase('CartItem', [
            'ID' => $userId,
            'TypeID' => BalanceWatchCredit::TYPE,
        ]));
        $email = $I->grabLastMail();
        $I->assertStringContainsString('You have successfully paid *$' . AwPlusSubscription::PRICE . '*', $email->getBody());
    }

    public function success1YearFull(\TestSymfonyGuy $I)
    {
        $profileId = StringHandler::getRandomCode(10);
        $userId = $I->createAwUser(null, null, ['PaypalRecurringProfileID' => $profileId, 'Subscription' => Usr::SUBSCRIPTION_PAYPAL]);
        $I->addUserPayment($userId, PAYMENTTYPE_CREDITCARD, new AwPlusSubscription());
        $cartId = $this->getCartId($I, $userId, AwPlusSubscription::TYPE);
        $I->assertEmpty($I->grabFromDatabase("CartItem", "ScheduledDate", ["CartID" => $cartId]));
        $oldExpiration = $I->getContainer()->get(ExpirationCalculator::class)->getAccountExpiration($userId);
        $I->assertGreaterThan(time(), $oldExpiration['date']);
        $I->assertEquals(1, $I->grabCountFromDatabase('CartItem', [
            'ID' => $userId,
            'TypeID' => BalanceWatchCredit::TYPE,
        ]));

        $I->sendPOST(self::URL, [
            'txn_type' => 'recurring_payment',
            'txn_id' => bin2hex(random_bytes(6)),
            'payment_status' => 'Completed',
            'recurring_payment_id' => $profileId,
            'payment_cycle' => 'every 12 Months',
            'payment_gross' => 30,
        ]);
        $I->seeResponseCodeIs(200);
        $newExpiration = $I->getContainer()->get(ExpirationCalculator::class)->getAccountExpiration($userId);
        $I->assertEquals(12, $this->dateDiffInMonths($oldExpiration['date'], $newExpiration['date']));
        $email = $I->grabLastMail();
        $I->assertStringContainsString('You have successfully paid *$30*', $email->getBody());
        $cartId = $this->getCartId($I, $userId, AwPlusSubscription::TYPE);
        $I->assertEmpty($I->grabFromDatabase("CartItem", "ScheduledDate", ["CartID" => $cartId]));
        $I->assertEquals(Cart::SOURCE_RECURRING, $I->grabFromDatabase("Cart", "Source", ["CartID" => $cartId]));
        $I->assertEquals(2, $I->grabCountFromDatabase('CartItem', [
            'ID' => $userId,
            'TypeID' => BalanceWatchCredit::TYPE,
        ]));
    }

    public function successFirstCallback(\TestSymfonyGuy $I)
    {
        $profileId = StringHandler::getRandomCode(10);
        $userId = $I->createAwUser(null, null, ['PaypalRecurringProfileID' => $profileId, 'Subscription' => Usr::SUBSCRIPTION_PAYPAL]);
        $I->addUserPayment($userId, PAYMENTTYPE_PAYPAL, new AwPlusSubscription());
        $cartId = $this->getCartId($I, $userId, AwPlusSubscription::TYPE);
        $I->updateInDatabase("Cart", ["BillingTransactionID" => $profileId], ["CartID" => $cartId]);
        $I->assertEmpty($I->grabFromDatabase("CartItem", "ScheduledDate", ["CartID" => $cartId]));
        $oldExpiration = $I->getContainer()->get(ExpirationCalculator::class)->getAccountExpiration($userId);
        $I->assertGreaterThan(time(), $oldExpiration['date']);
        $I->assertEquals(1, $I->grabCountFromDatabase('CartItem', [
            'ID' => $userId,
            'TypeID' => BalanceWatchCredit::TYPE,
        ]));
        $oldCartsCount = $I->query("select count(*) from Cart where UserID = $userId and PayDate is not null")->fetchColumn();

        $txnId = bin2hex(random_bytes(6));
        $I->sendPOST(self::URL, [
            'txn_type' => 'recurring_payment',
            'txn_id' => $txnId,
            'payment_status' => 'Completed',
            'recurring_payment_id' => $profileId,
            'payment_cycle' => 'every 12 Months',
            'payment_gross' => 30,
        ]);
        $I->seeResponseCodeIs(200);
        $newExpiration = $I->getContainer()->get(ExpirationCalculator::class)->getAccountExpiration($userId);
        $I->assertSame($oldExpiration['date'], $newExpiration['date']);
        $newCartsCount = $I->query("select count(*) from Cart where UserID = $userId and PayDate is not null")->fetchColumn();
        $I->assertSame($oldCartsCount, $newCartsCount);
        $I->assertEquals($txnId, $I->grabFromDatabase("Cart", "BillingTransactionID", ["CartID" => $cartId]));
    }

    public function successFirstCallbackWithPrepaid(\TestSymfonyGuy $I)
    {
        $profileId = 'I-' . strtoupper(StringHandler::getRandomCode(10));
        $userId = $I->createAwUser(null, null, ['PaypalRecurringProfileID' => $profileId, 'Subscription' => Usr::SUBSCRIPTION_PAYPAL]);
        $I->addUserPayment($userId, PAYMENTTYPE_PAYPAL, (new AwPlusSubscription())->setScheduledDate(new \DateTime("+1 year")), [new AwPlusPrepaid()]);
        $cartId = $this->getCartId($I, $userId, AwPlusSubscription::TYPE);
        $I->updateInDatabase("Cart", ["BillingTransactionID" => $profileId], ["CartID" => $cartId]);
        $oldExpiration = $I->getContainer()->get(ExpirationCalculator::class)->getAccountExpiration($userId);
        $I->assertGreaterThan(time(), $oldExpiration['date']);
        // no balance watch because it's prepaid, actual subscription is not bought yet
        $I->assertEquals(0, $I->grabCountFromDatabase('CartItem', [
            'ID' => $userId,
            'TypeID' => BalanceWatchCredit::TYPE,
        ]));
        $oldCartsCount = $I->query("select count(*) from Cart where UserID = $userId and PayDate is not null")->fetchColumn();

        $I->sendPOST(self::URL, [
            'txn_type' => 'recurring_payment',
            'txn_id' => bin2hex(random_bytes(6)),
            'payment_status' => 'Completed',
            'recurring_payment_id' => $profileId,
            'payment_cycle' => 'every 12 Months',
            'payment_gross' => 30,
        ]);
        $I->seeResponseCodeIs(200);
        $I->assertEquals($profileId, $I->grabFromDatabase("Cart", "BillingTransactionID", ["CartID" => $cartId]));
        $newExpiration = $I->getContainer()->get(ExpirationCalculator::class)->getAccountExpiration($userId);
        $I->assertEquals(12, $this->dateDiffInMonths($oldExpiration['date'], $newExpiration['date']));
        $newCartsCount = $I->query("select count(*) from Cart where UserID = $userId and PayDate is not null")->fetchColumn();
        $I->assertNotSame($oldCartsCount, $newCartsCount);
    }

    public function success1YearDiscount25(\TestSymfonyGuy $I)
    {
        $em = $I->getContainer()->get("doctrine.orm.entity_manager");
        $profileId = StringHandler::getRandomCode(10);
        $userId = $I->createAwUser(null, null, ['PaypalRecurringProfileID' => $profileId, 'Subscription' => Usr::SUBSCRIPTION_PAYPAL]);

        $couponCode = StringUtils::getRandomCode(20);
        $couponId = $I->haveInDatabase("Coupon", [
            "Code" => $couponCode,
            "Name" => "Special Discount 25%",
            "Discount" => 25,
        ]);
        $I->haveInDatabase("CouponItem", ["CouponID" => $couponId, "CartItemType" => AwPlusSubscription::TYPE]);
        $coupon = $em->find(Coupon::class, $couponId);

        $I->addUserPayment($userId, PAYMENTTYPE_CREDITCARD, new AwPlusSubscription(), null, null, $coupon);
        $cartId = $this->getCartId($I, $userId, AwPlusSubscription::TYPE);
        $I->assertEmpty($I->grabFromDatabase("CartItem", "ScheduledDate", ["CartID" => $cartId]));
        $oldExpiration = $I->getContainer()->get(ExpirationCalculator::class)->getAccountExpiration($userId);
        $I->assertGreaterThan(time(), $oldExpiration['date']);
        $I->assertEquals(1, $I->grabCountFromDatabase('CartItem', [
            'ID' => $userId,
            'TypeID' => BalanceWatchCredit::TYPE,
        ]));

        $I->sendPOST(self::URL, [
            'txn_type' => 'recurring_payment',
            'txn_id' => bin2hex(random_bytes(6)),
            'payment_status' => 'Completed',
            'recurring_payment_id' => $profileId,
            'payment_cycle' => 'every 12 Months',
            'payment_gross' => 22.5,
        ]);
        $I->seeResponseCodeIs(200);
        $newExpiration = $I->getContainer()->get(ExpirationCalculator::class)->getAccountExpiration($userId);
        $I->assertEquals(12, $this->dateDiffInMonths($oldExpiration['date'], $newExpiration['date']));
        $email = $I->grabLastMail();
        $I->assertStringContainsString('You have successfully paid *$22.50*', $email->getBody());
        $cartId = $this->getCartId($I, $userId, AwPlusSubscription::TYPE);
        $I->assertEmpty($I->grabFromDatabase("CartItem", "ScheduledDate", ["CartID" => $cartId]));
        $I->assertEquals(Cart::SOURCE_RECURRING, $I->grabFromDatabase("Cart", "Source", ["CartID" => $cartId]));
        $I->assertEquals(2, $I->grabCountFromDatabase('CartItem', [
            'ID' => $userId,
            'TypeID' => BalanceWatchCredit::TYPE,
        ]));
    }

    public function success1YearCallback(\TestSymfonyGuy $I)
    {
        $profileId = StringHandler::getRandomCode(10);
        $userId = $I->createAwUser(null, null, ['PaypalRecurringProfileID' => $profileId, 'Subscription' => Usr::SUBSCRIPTION_PAYPAL]);
        $I->addUserPayment($userId, PAYMENTTYPE_PAYPAL, new AwPlusSubscription());
        $cartId = $this->getCartId($I, $userId, AwPlusSubscription::TYPE);
        $I->executeQuery("update Cart set BillingTransactionID = '$profileId' where CartID = $cartId");
        $I->assertEmpty($I->grabFromDatabase("CartItem", "ScheduledDate", ["CartID" => $cartId]));
        $oldExpiration = $I->getContainer()->get(ExpirationCalculator::class)->getAccountExpiration($userId);
        $I->assertGreaterThan(time(), $oldExpiration['date']);
        $I->assertEquals(1, $I->grabCountFromDatabase('CartItem', [
            'ID' => $userId,
            'TypeID' => BalanceWatchCredit::TYPE,
        ]));

        $txnId = bin2hex(random_bytes(6));
        $I->sendPOST(self::URL, [
            'txn_type' => 'recurring_payment',
            'txn_id' => $txnId,
            'payment_status' => 'Completed',
            'recurring_payment_id' => $profileId,
            'payment_cycle' => 'every 12 Months',
            'payment_gross' => 30,
        ]);
        $I->seeResponseCodeIs(200);
        $newExpiration = $I->getContainer()->get(ExpirationCalculator::class)->getAccountExpiration($userId);
        $I->assertEquals(0, $this->dateDiffInMonths($oldExpiration['date'], $newExpiration['date']));
        $I->assertEquals($txnId, $I->grabFromDatabase("Cart", "BillingTransactionID", ["CartID" => $cartId]));
        $email = $I->grabLastMail();
        $I->assertStringContainsString(strval($cartId), $email->getBody());
        $lastCartId = $this->getCartId($I, $userId, AwPlusSubscription::TYPE);
        $I->assertEquals($cartId, $lastCartId);
        $I->assertEmpty($I->grabFromDatabase("CartItem", "ScheduledDate", ["CartID" => $lastCartId]));
        $I->assertEquals(Cart::SOURCE_USER, $I->grabFromDatabase("Cart", "Source", ["CartID" => $lastCartId]));
        $I->assertEquals(1, $I->grabCountFromDatabase('CartItem', [
            'ID' => $userId,
            'TypeID' => BalanceWatchCredit::TYPE,
        ]));
    }

    public function success1YearScheduled(\TestSymfonyGuy $I)
    {
        $profileId = StringHandler::getRandomCode(10);
        $userId = $I->createAwUser(null, null, ['PaypalRecurringProfileID' => $profileId, 'Subscription' => Usr::SUBSCRIPTION_PAYPAL]);
        $I->addUserPayment($userId, PAYMENTTYPE_PAYPAL, (new AwPlusSubscription())->setScheduledDate(new \DateTime("+1 month")));
        $cartId = $this->getCartId($I, $userId, AwPlusSubscription::TYPE);
        $I->executeQuery("update Cart set BillingTransactionID = '$profileId' where CartID = $cartId");
        $I->assertNotEmpty($I->grabFromDatabase("CartItem", "ScheduledDate", ["CartID" => $cartId]));
        $oldExpiration = $I->getContainer()->get(ExpirationCalculator::class)->getAccountExpiration($userId);
        $I->assertEmpty($oldExpiration['lastPrice']);
        $I->assertEquals(0, $I->grabCountFromDatabase('CartItem', [
            'ID' => $userId,
            'TypeID' => BalanceWatchCredit::TYPE,
        ]));

        $I->sendPOST(self::URL, [
            'txn_type' => 'recurring_payment',
            'txn_id' => bin2hex(random_bytes(6)),
            'payment_status' => 'Completed',
            'recurring_payment_id' => $profileId,
            'payment_cycle' => 'every 12 Months',
            'payment_gross' => 30,
        ]);
        $I->seeResponseCodeIs(200);
        $newExpiration = $I->getContainer()->get(ExpirationCalculator::class)->getAccountExpiration($userId);
        $I->assertEquals(12, $this->dateDiffInMonths($oldExpiration['date'], $newExpiration['date']));
        $I->assertEquals($profileId, $I->grabFromDatabase("Cart", "BillingTransactionID", ["CartID" => $cartId]));
        $email = $I->grabLastMail();
        $I->assertStringNotContainsString(strval($cartId), $email->getBody());
        $lastCartId = $this->getCartId($I, $userId, AwPlusSubscription::TYPE);
        $I->assertNotEquals($cartId, $lastCartId);
        $I->assertEmpty($I->grabFromDatabase("CartItem", "ScheduledDate", ["CartID" => $lastCartId]));
        $I->assertEquals(Cart::SOURCE_RECURRING, $I->grabFromDatabase("Cart", "Source", ["CartID" => $lastCartId]));
        $I->assertEquals(1, $I->grabCountFromDatabase('CartItem', [
            'ID' => $userId,
            'TypeID' => BalanceWatchCredit::TYPE,
        ]));
    }

    public function success1YearAndOneCardScheduled(\TestSymfonyGuy $I)
    {
        $profileId = StringHandler::getRandomCode(10);
        $userId = $I->createAwUser(null, null, ['PaypalRecurringProfileID' => $profileId, 'Subscription' => Usr::SUBSCRIPTION_PAYPAL]);
        $I->addUserPayment($userId, PAYMENTTYPE_PAYPAL, (new AwPlusSubscription())->setScheduledDate(new \DateTime("+1 month")), [new OneCard()]);
        $cartId = $this->getCartId($I, $userId, AwPlusSubscription::TYPE);
        $I->executeQuery("update Cart set BillingTransactionID = '$profileId' where CartID = $cartId");
        $I->assertNotEmpty($I->grabFromDatabase("CartItem", "ScheduledDate", ["CartID" => $cartId]));
        $oldExpiration = $I->getContainer()->get(ExpirationCalculator::class)->getAccountExpiration($userId);
        $I->assertEmpty($oldExpiration['lastPrice']);
        $I->assertEquals(0, $I->grabCountFromDatabase('CartItem', [
            'ID' => $userId,
            'TypeID' => BalanceWatchCredit::TYPE,
        ]));

        $I->sendPOST(self::URL, [
            'txn_type' => 'recurring_payment',
            'txn_id' => bin2hex(random_bytes(6)),
            'payment_status' => 'Completed',
            'recurring_payment_id' => $profileId,
            'payment_cycle' => 'every 12 Months',
            'payment_gross' => 30,
        ]);
        $I->seeResponseCodeIs(200);
        $newExpiration = $I->getContainer()->get(ExpirationCalculator::class)->getAccountExpiration($userId);
        $I->assertEquals(12, $this->dateDiffInMonths($oldExpiration['date'], $newExpiration['date']));
        $I->assertEquals($profileId, $I->grabFromDatabase("Cart", "BillingTransactionID", ["CartID" => $cartId]));
        $email = $I->grabLastMail();
        $I->assertStringNotContainsString(strval($cartId), $email->getBody());
        $lastCartId = $this->getCartId($I, $userId, AwPlusSubscription::TYPE);
        $I->assertNotEquals($cartId, $lastCartId);
        $I->assertEmpty($I->grabFromDatabase("CartItem", "ScheduledDate", ["CartID" => $lastCartId]));
        $I->assertEquals(Cart::SOURCE_RECURRING, $I->grabFromDatabase("Cart", "Source", ["CartID" => $lastCartId]));
        $I->assertEquals(1, $I->grabCountFromDatabase('CartItem', [
            'ID' => $userId,
            'TypeID' => BalanceWatchCredit::TYPE,
        ]));
    }

    public function testSkipped(\TestSymfonyGuy $I)
    {
        $profileId = StringHandler::getRandomCode(10);
        $userId = $I->createAwUser(null, null, [
            'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
            'PaypalRecurringProfileID' => $profileId,
            'Subscription' => Usr::SUBSCRIPTION_PAYPAL,
        ]);
        $I->addUserPayment($userId, PAYMENTTYPE_CREDITCARD, new AwPlusSubscription(), [], new \DateTime("-6 year"));
        $I->assertEquals(ACCOUNT_LEVEL_AWPLUS, $I->grabFromDatabase("Usr", "AccountLevel", ["UserID" => $userId]));
        $paypal = $I->stubMake(PaypalRestApi::class, [
            'cancelAgreement' => Stub::never(),
        ]);
        $I->mockService(PaypalRestApi::class, $paypal);
        $I->sendPOST(self::URL, [
            'txn_type' => 'recurring_payment_skipped',
            'recurring_payment_id' => $profileId,
            'amount' => 30,
        ]);
        $I->assertEquals(ACCOUNT_LEVEL_AWPLUS, $I->grabFromDatabase("Usr", "AccountLevel", ["UserID" => $userId]));
        $I->verifyMocks();
        $email = $I->grabLastMail();
        $I->assertStringContainsString('charge was declined', $email->getBody());
    }

    public function testFailed(\TestSymfonyGuy $I)
    {
        $profileId = StringHandler::getRandomCode(10);
        $userId = $I->createAwUser(null, null, [
            'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
            'PaypalRecurringProfileID' => $profileId,
            'Subscription' => Usr::SUBSCRIPTION_PAYPAL,
        ]);
        $I->addUserPayment($userId, PAYMENTTYPE_CREDITCARD, new AwPlusSubscription(), [], new \DateTime("-6 year"));
        $I->assertEquals(ACCOUNT_LEVEL_AWPLUS, $I->grabFromDatabase("Usr", "AccountLevel", ["UserID" => $userId]));
        $paypal = $I->stubMake(PaypalRestApi::class, [
            'cancelAgreement' => Stub::never(),
        ]);
        $I->mockService(PaypalRestApi::class, $paypal);
        $I->sendPOST(self::URL, [
            'txn_type' => 'recurring_payment_failed',
            'recurring_payment_id' => $profileId,
            'amount' => 30,
        ]);
        $I->assertEquals(ACCOUNT_LEVEL_FREE, $I->grabFromDatabase("Usr", "AccountLevel", ["UserID" => $userId]));
        $I->verifyMocks();
        $email = $I->grabLastMail();
        $I->assertStringContainsString('charge was declined', $email->getBody());
    }

    public function testSkippedNotExpired(\TestSymfonyGuy $I)
    {
        $profileId = StringHandler::getRandomCode(10);
        $userId = $I->createAwUser(null, null, ['PaypalRecurringProfileID' => $profileId, 'Subscription' => Usr::SUBSCRIPTION_PAYPAL]);
        $I->addUserPayment($userId, PAYMENTTYPE_CREDITCARD, new AwPlusSubscription(), [], new \DateTime("-1 month"));
        $I->assertEquals(ACCOUNT_LEVEL_AWPLUS, $I->grabFromDatabase("Usr", "AccountLevel", ["UserID" => $userId]));
        $I->sendPOST(self::URL, [
            'txn_type' => 'recurring_payment_skipped',
            'recurring_payment_id' => $profileId,
        ]);
        $I->assertEquals(ACCOUNT_LEVEL_AWPLUS, $I->grabFromDatabase("Usr", "AccountLevel", ["UserID" => $userId]));
        $email = $I->grabLastMail();
        $I->assertStringNotContainsString('membership *has expired*', $email->getBody());
        $I->assertEquals(1, $I->grabCountFromDatabase('CartItem', [
            'ID' => $userId,
            'TypeID' => BalanceWatchCredit::TYPE,
        ]));
    }

    public function cancelledProfile(\TestSymfonyGuy $I)
    {
        $profileId = StringHandler::getRandomCode(10);
        $userId = $I->createAwUser(null, null, ['PaypalRecurringProfileID' => $profileId, 'Subscription' => Usr::SUBSCRIPTION_PAYPAL]);
        $I->addUserPayment($userId, PAYMENTTYPE_CREDITCARD, new AwPlusSubscription());

        $I->sendPOST(self::URL, [
            'txn_type' => 'recurring_payment_profile_cancel',
            'profile_status' => 'Cancelled',
            'recurring_payment_id' => $profileId,
        ]);
        $I->seeResponseCodeIs(200);
        $I->assertNull($I->grabFromDatabase("Usr", "Subscription", ["UserID" => $userId]));
        $I->assertNull($I->grabFromDatabase("Usr", "PaypalRecurringProfileID", ["UserID" => $userId]));
    }

    public function refund(\TestSymfonyGuy $I)
    {
        $profileId = StringHandler::getRandomCode(10);
        $firstTxId = bin2hex(random_bytes(8));
        $userId = $I->createAwUser(null, null, ['PaypalRecurringProfileID' => $profileId, 'Subscription' => Usr::SUBSCRIPTION_PAYPAL]);
        $cart = $I->addUserPayment($userId, PAYMENTTYPE_PAYPAL, new AwPlusSubscription());
        $I->updateInDatabase("Cart", ["BillingTransactionID" => $firstTxId], ["CartID" => $cart->getCartid()]);
        $I->assertEquals(ACCOUNT_LEVEL_AWPLUS, $I->grabFromDatabase("Usr", "AccountLevel", ["UserID" => $userId]));

        $I->sendPOST(self::URL, [
            "payment_status" => "Refunded",
            'recurring_payment_id' => $profileId,
            "parent_txn_id" => $firstTxId,
        ]);
        $I->seeResponseCodeIs(200);
        $I->dontSeeInDatabase("Cart", ["CartID" => $cart->getCartid()]);
        $I->assertEquals(ACCOUNT_LEVEL_FREE, $I->grabFromDatabase("Usr", "AccountLevel", ["UserID" => $userId]));
    }

    public function success1YearDiscounted(\TestSymfonyGuy $I)
    {
        $profileId = StringHandler::getRandomCode(10);
        $userId = $I->createAwUser(null, null, ['PaypalRecurringProfileID' => $profileId, 'Subscription' => Usr::SUBSCRIPTION_PAYPAL]);
        /** @var Usr $user */
        $user = $I->getContainer()->get('doctrine')->getRepository(Usr::class)->find($userId);
        $user->setDiscountedUpgradeBefore(new \DateTime("+1 day"));
        $I->getContainer()->get("doctrine.orm.entity_manager")->flush();

        $cartManager = $I->getContainer()->get("aw.manager.cart");
        $cartManager->setUser($user);
        $cart = $cartManager->createNewCart();
        $cart->setPaymenttype(Cart::PAYMENTTYPE_PAYPAL);
        $cartManager->addAwSubscriptionItem($cart, new \DateTime(), true);
        $cartManager->markAsPayed($cart);

        $oldExpiration = $I->getContainer()->get(ExpirationCalculator::class)->getAccountExpiration($userId);
        $I->assertGreaterThan(time(), $oldExpiration['date']);

        $I->sendPOST(self::URL, [
            'txn_type' => 'recurring_payment',
            'txn_id' => bin2hex(random_bytes(6)),
            'payment_status' => 'Completed',
            'recurring_payment_id' => $profileId,
            'payment_cycle' => 'every 12 Months',
            'payment_gross' => 10,
        ]);
        $I->seeResponseCodeIs(200);
        $newExpiration = $I->getContainer()->get(ExpirationCalculator::class)->getAccountExpiration($userId);
        $I->assertEquals(12, $this->dateDiffInMonths($oldExpiration['date'], $newExpiration['date']));
        $email = $I->grabLastMail();
        $I->assertStringContainsString('You have successfully paid *$10*', $email->getBody());
        $I->assertEquals(2, $I->grabCountFromDatabase('CartItem', [
            'ID' => $userId,
            'TypeID' => BalanceWatchCredit::TYPE,
        ]));
    }

    private function repeatedStandardSubscriptionDataProvider()
    {
        return [
            [
                'SubscriptionCartItemClass' => AwPlus::class,
                'SubscriptionExtraItems' => [new AwPlusRecurring()],
                'SubscriptionPrice' => 25,
                'SubscriptionPeriod' => SubscriptionPeriod::DAYS_6_MONTHS,
                'SubscriptionType' => Usr::SUBSCRIPTION_TYPE_AWPLUS,
                'payment_cycle' => 'every 6 Months',
                'payment_gross' => 25,
                'expectedPlusMonths' => 6,
                'expectedBalanceWatch' => true,
                'expectedPrice' => 25,
                'expectedTypeId' => AwPlusSubscription6Months::TYPE,
            ],
            [
                'SubscriptionCartItemClass' => AwPlusSubscription::class,
                'SubscriptionExtraItems' => [],
                'SubscriptionPrice' => 49.99,
                'SubscriptionPeriod' => SubscriptionPeriod::DAYS_1_YEAR,
                'SubscriptionType' => Usr::SUBSCRIPTION_TYPE_AWPLUS,
                'payment_cycle' => 'every 12 Months',
                'payment_gross' => 49.99,
                'expectedPlusMonths' => 12,
                'expectedBalanceWatch' => true,
                'expectedPrice' => 49.99,
                'expectedTypeId' => AwPlusSubscription::TYPE,
            ],
        ];
    }

    private function getCartId(\TestSymfonyGuy $I, int $userId, int $type)
    {
        return $I
            ->query("
              select c.CartID from Cart c join CartItem ci on ci.CartID = c.CartID 
              where c.UserID = ? and c.PayDate is not null and ci.TypeID = ?
              order by c.CartID desc
            ", [$userId, $type])
            ->fetchColumn();
    }

    private function dateDiffInMonths($date1, $date2)
    {
        return round(($date2 - $date1) / (SECONDS_PER_DAY * 30));
    }
}
