<?php

namespace AwardWallet\Tests\Unit\Billing;

use AwardWallet\MainBundle\Command\BillCardsCommand;
use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem\At201Items;
use AwardWallet\MainBundle\Entity\CartItem\AT201Subscription1Month;
use AwardWallet\MainBundle\Entity\CartItem\AT201Subscription1Year;
use AwardWallet\MainBundle\Entity\CartItem\AT201Subscription6Months;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusGift;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription;
use AwardWallet\MainBundle\Entity\CartItem\BalanceWatchCredit;
use AwardWallet\MainBundle\Entity\CartItem\Discount;
use AwardWallet\MainBundle\Entity\CartItem\OneCard;
use AwardWallet\MainBundle\Entity\CartItem\PlusItems;
use AwardWallet\MainBundle\Entity\Coupon;
use AwardWallet\MainBundle\Entity\Repositories\CartRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\Cart\Manager;
use AwardWallet\MainBundle\Globals\Cart\SubscriptionPeriod;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Service\AppBot\AT201Notifier;
use AwardWallet\MainBundle\Service\Billing\PaypalRestApi;
use Codeception\Example;
use Codeception\Util\Stub;
use PayPal\Api\CreditCard;
use PayPal\Exception\PayPalConnectionException;
use PHPUnit\Framework\Assert;
use Stripe\PaymentIntent;
use Stripe\Service\PaymentIntentService;
use Stripe\StripeClient;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group frontend-unit
 * @group billing
 */
class BillCardsCommandCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    /**
     * @var CommandTester
     */
    private $commandTester;

    /**
     * @var ContainerAwareCommand
     */
    private $command;

    /**
     * @var Manager
     */
    private $cartManager;

    /**
     * @var int
     */
    private $userId;

    /**
     * @var Usr
     */
    private $user;
    private ?Application $app;

    public function _before(\CodeGuy $I)
    {
        $this->app = new Application($I->grabService('kernel'));
    }

    public function testNotUpgraded(\CodeGuy $I)
    {
        $this->init($I);
        $I->assertEquals(0, $this->getPaidCartsCount($I));
    }

    public function testEarlyRun(\CodeGuy $I)
    {
        $this->init($I);
        $this->giveUpgrade($I, new \DateTime("-1 month"));
        $this->commandTester->execute(['command' => $this->command->getName(), '--userId' => $this->userId]);
        $I->assertEquals(1, $this->getPaidCartsCount($I));
        $this->assertBalanceWatchCredits($I, $this->userId, 1);
    }

    public function testNoDiscountedNow(\CodeGuy $I)
    {
        $paypal = $I->stubMake(PaypalRestApi::class, [
            'payWithSavedCard' => Stub::exactly(2, function (Cart $cart, $cardId, $total) use ($I) {
                $I->assertEquals(AwPlusSubscription::PRICE, $total);
            }),
        ]);
        $I->mockService(PaypalRestApi::class, $paypal);
        $this->init($I);

        $this->giveUpgrade($I, new \DateTime("-370 day"));
        $this->user->setDiscountedUpgradeBefore(new \DateTime("+1 day"));
        $em = $I->getContainer()->get("doctrine.orm.entity_manager");
        $em->persist($this->user);
        $em->flush();

        $this->assertBalanceWatchCredits($I, $this->userId, 1);

        $this->commandTester->execute(['command' => $this->command->getName(), '--userId' => $this->userId]);

        $I->assertEquals(2, $this->getPaidCartsCount($I));
        $this->assertBalanceWatchCredits($I, $this->userId, 2);
        $email = $I->grabLastMail();
        $I->assertStringContainsString('You have successfully paid *$' . AwPlusSubscription::PRICE . '*', $email->getBody());

        $I->wantTo("check next year");
        $I->executeQuery("update Usr set 
            DiscountedUpgradeBefore = adddate(DiscountedUpgradeBefore, interval -1 year), 
            PlusExpirationDate = adddate(PlusExpirationDate, interval -1 year)
        where UserID = {$this->userId}");
        $em->clear();

        $this->commandTester->execute(['command' => $this->command->getName(), '--userId' => $this->userId]);
        $this->assertBalanceWatchCredits($I, $this->userId, 3);
        $I->verifyMocks();
        $email = $I->grabLastMail();
        $I->assertStringContainsString('You have successfully paid *$' . AwPlusSubscription::PRICE . '*', $email->getBody());
    }

    /**
     * @dataProvider newPricingDataProvider
     */
    public function testNewPricing(\CodeGuy $I, Example $example)
    {
        $paypal = $I->stubMake(PaypalRestApi::class, [
            'payWithSavedCard' => Stub::exactly(2, function (Cart $cart, $cardId, $total) use ($I, $example) {
                $I->assertEquals($example['expectedPrice'], $total);
            }),
        ]);
        $I->mockService(PaypalRestApi::class, $paypal);
        $this->init($I);

        $firstCart = $this->cartManager->createNewCart();
        $firstCart->setCreditcardnumber('XXXXXXXXXXXX5678');
        $firstCart->setCreditcardtype('VISA');
        $firstCart->setPaymenttype(Cart::PAYMENTTYPE_CREDITCARD);
        $this->cartManager->addSubscription($example['oldSubscriptionType'], $example['oldSubscriptionDuration']);
        $firstCart->getPlusItem()->setPrice($example['oldPrice']);
        $this->cartManager->markAsPayed($firstCart, null, (new \DateTime(str_replace("+", "-", $example['oldSubscriptionDuration'])))->modify("-3 day"));
        $I->assertEquals(1, $this->getPaidCartsCount($I));

        $I->seeInDatabase("Usr", [
            "UserID" => $this->user->getId(),
            "FirstSubscriptionCartItemID" => $firstCart->getPlusItem()->getCartitemid(),
            "LastSubscriptionCartItemID" => $firstCart->getPlusItem()->getCartitemid(),
        ]);

        $this->user->setSubscriptionPeriod(SubscriptionPeriod::DURATION_TO_DAYS[$example['oldSubscriptionDuration']]);
        $this->user->setSubscriptionPrice($firstCart->getPlusItem()->getPrice());
        $em = $I->getContainer()->get("doctrine.orm.entity_manager");
        $em->persist($this->user);
        $em->flush();

        $this->assertBalanceWatchCredits($I, $this->userId, $example['oldBalanceWatchCredits']);

        $this->commandTester->execute(['command' => $this->command->getName(), '--userId' => $this->userId, '--new-pricing' => true]);

        $I->assertEquals(2, $this->getPaidCartsCount($I));
        $this->assertBalanceWatchCredits($I, $this->userId, $example['expectedBalanceWatchCredits1']);
        $email = $I->grabLastMail();
        $I->assertStringContainsString('You have successfully paid *$' . $example['expectedPrice'] . '*', $email->getBody());
        $recurringCart1 = $I->grabService(CartRepository::class)->getActiveAwSubscription($this->user);
        $I->assertEquals($example['expectedCartItemType'], $recurringCart1->getPlusItem()::TYPE);

        $I->assertNotEquals($firstCart->getPlusItem()->getCartitemid(), $recurringCart1->getPlusItem()->getCartitemid());
        $I->seeInDatabase("Usr", [
            "UserID" => $this->user->getId(),
            "FirstSubscriptionCartItemID" => $firstCart->getPlusItem()->getCartitemid(),
            "LastSubscriptionCartItemID" => $recurringCart1->getPlusItem()->getCartitemid(),
        ]);

        $I->wantTo("check next year");
        $I->executeQuery("update Usr set 
            AT201ExpirationDate = adddate(AT201ExpirationDate, interval -1 year),
            PlusExpirationDate = adddate(PlusExpirationDate, interval -1 year)
        where UserID = {$this->userId}");
        $em->clear();

        $this->commandTester->execute(['command' => $this->command->getName(), '--userId' => $this->userId, '--new-pricing' => true]);
        $this->assertBalanceWatchCredits($I, $this->userId, $example['expectedBalanceWatchCredits2']);
        $I->verifyMocks();
        $email = $I->grabLastMail();
        $I->assertStringContainsString('You have successfully paid *$' . $example['expectedPrice'] . '*', $email->getBody());
        $recurringCart2 = $I->grabService(CartRepository::class)->getActiveAwSubscription($this->user);
        $I->assertEquals($example['expectedCartItemType'], $recurringCart2->getPlusItem()::TYPE);
    }

    public function testDiscount25(\CodeGuy $I)
    {
        $expectedTotals = [round(AwPlusSubscription::PRICE, 2)];
        $paypal = $I->stubMake(PaypalRestApi::class, [
            'payWithSavedCard' => Stub::exactly(1,
                function (Cart $cart, $cardId, $total) use ($I, &$expectedTotals) {
                    $I->assertEquals(array_shift($expectedTotals), $total);
                }
            ),
        ]);
        $I->mockService(PaypalRestApi::class, $paypal);

        $this->init($I);

        $em = $I->getContainer()->get("doctrine.orm.entity_manager");

        $couponCode = StringUtils::getRandomCode(20);
        $couponId = $I->haveInDatabase("Coupon", [
            "Code" => $couponCode,
            "Name" => "Special Discount 25%",
            "Discount" => 25,
        ]);
        $I->haveInDatabase("CouponItem", ["CouponID" => $couponId, "CartItemType" => AwPlusSubscription::TYPE]);
        $coupon = $em->find(Coupon::class, $couponId);

        $this->giveUpgrade($I, new \DateTime("-370 day"), false, null, 0, $coupon);
        $em->persist($this->user);
        $em->flush();

        $this->assertBalanceWatchCredits($I, $this->userId, 1);

        $this->commandTester->execute(['command' => $this->command->getName(), '--userId' => $this->userId]);

        $I->assertEquals(2, $this->getPaidCartsCount($I));
        $this->assertBalanceWatchCredits($I, $this->userId, 2);

        $I->verifyMocks();
        $I->assertEmpty($expectedTotals);
    }

    public function testFull(\CodeGuy $I)
    {
        $paypal = $I->stubMake(PaypalRestApi::class, [
            'payWithSavedCard' => Stub::exactly(1,
                function (Cart $cart, $cardId, $total) use ($I) {
                    $I->assertEquals(AwPlusSubscription::PRICE, $total);
                }
            ),
        ]);
        $I->mockService(PaypalRestApi::class, $paypal);

        $this->init($I);

        $this->giveUpgrade($I, new \DateTime("-370 day"));

        $this->commandTester->execute(['command' => $this->command->getName(), '--userId' => $this->userId]);

        $I->assertEquals(2, $this->getPaidCartsCount($I));
        $this->assertBalanceWatchCredits($I, $this->userId, 2);
        $I->assertEquals(null, $I->grabFromDatabase("Usr", "DiscountedUpgradeBefore", ["UserID" => $this->userId]));
        $cartId = $I
            ->query("
                select max(c.CartID) from Cart c join CartItem ci on c.CartID = ci.CartID 
                where c.UserID = ? and c.PayDate is not null and ci.TypeID = ?
                ",
                [$this->userId, AwPlusSubscription::TYPE]
            )
            ->fetchColumn(0);
        $I->assertEquals(Cart::SOURCE_RECURRING, $I->grabFromDatabase("Cart", "Source", ["CartID" => $cartId]));
        $I->verifyMocks();

        $mail = $I->grabLastMail();
        $I->assertStringContainsString($cartId, $mail->getBody());
        $I->assertStringContainsString("XXXXXXXXXXXX5678", $mail->getBody());
        $I->assertStringContainsString("VISA", $mail->getBody());
    }

    public function testFullWithOnecard(\CodeGuy $I)
    {
        $paypal = $I->stubMake(PaypalRestApi::class, [
            'payWithSavedCard' => Stub::exactly(1,
                function (Cart $cart, $cardId, $total) use ($I) {
                    $I->assertEquals(AwPlusSubscription::PRICE, $total);
                }
            ),
        ]);
        $I->mockService(PaypalRestApi::class, $paypal);

        $this->init($I);

        $this->giveUpgrade($I, new \DateTime("-370 day"), false, null, 1);

        $this->commandTester->execute(['command' => $this->command->getName(), '--userId' => $this->userId]);

        $I->assertEquals(2, $this->getPaidCartsCount($I));
        $this->assertBalanceWatchCredits($I, $this->userId, 2);
        $I->assertEquals(null, $I->grabFromDatabase("Usr", "DiscountedUpgradeBefore", ["UserID" => $this->userId]));
        $I->verifyMocks();

        $mail = $I->grabLastMail();
        $I->assertStringContainsString("XXXXXXXXXXXX5678", $mail->getBody());
        $I->assertStringContainsString("VISA", $mail->getBody());
    }

    public function testFailure(\CodeGuy $I)
    {
        $paypal = $I->stubMake(PaypalRestApi::class, [
            'payWithSavedCard' => Stub::exactly(1,
                function (Cart $cart, $cardId, $total) {
                    $e = new PayPalConnectionException('', '');
                    $e->setData(json_encode(['name' => 'CREDIT_CARD_REFUSED']));

                    throw $e;
                }
            ),
            'getCardInfo' => Stub::exactly(1, function ($cardId) {
                return new CreditCard(['number' => '1234']);
            }),
        ]);
        $I->mockService(PaypalRestApi::class, $paypal);

        $this->init($I);

        $this->giveUpgrade($I, new \DateTime("-370 day"));

        $this->commandTester->execute(['command' => $this->command->getName(), '--userId' => $this->userId]);

        $I->assertEquals(1, $this->getPaidCartsCount($I));
        $this->assertBalanceWatchCredits($I, $this->userId, 1);
        $I->verifyMocks();

        $mail = $I->grabLastMail();
        $body = $mail->getBody();
        $I->assertStringContainsString("charge was declined", $body);
        $I->assertStringContainsString("yearly", $body);
        $I->assertStringContainsString("/cart/change-payment/{$this->userId}/", $body);
        $I->assertEquals(ACCOUNT_LEVEL_AWPLUS, $I->grabFromDatabase("Usr", "AccountLevel", ["UserID" => $this->userId]));

        if (!preg_match("#/cart/change-payment/{$this->userId}/\w{40}#", $body, $matches)) {
            $I->fail("upgrade link not found");
        }

        $I->prepareStripeIntentMocks();
        $I->amOnPage($matches[0]);
        $I->seeInSource("const stripe = Stripe(");
    }

    public function testUnknownErrorExpired(\CodeGuy $I)
    {
        $paypal = $I->stubMake(PaypalRestApi::class, [
            'payWithSavedCard' => Stub::exactly(1,
                function (Cart $cart, $cardId, $total) {
                    $e = new PayPalConnectionException('', '');
                    $e->setData(json_encode(['name' => 'UNKNOWN_ERROR']));

                    throw $e;
                }
            ),
            'getCardInfo' => Stub::exactly(2, function ($cardId) {
                $date = getdate();

                return new CreditCard(['number' => '1234', 'expire_month' => date("m", strtotime("first day of previous month")), 'expire_year' => date("Y", strtotime("first day of previous month"))]);
            }),
        ]);
        $I->mockService(PaypalRestApi::class, $paypal);

        $this->init($I);

        $this->giveUpgrade($I, new \DateTime("-370 day"));

        $this->commandTester->execute(['command' => $this->command->getName(), '--userId' => $this->userId]);

        $I->assertEquals(1, $this->getPaidCartsCount($I));
        $this->assertBalanceWatchCredits($I, $this->userId, 1);
        $I->verifyMocks();

        $mail = $I->grabLastMail();
        $I->assertStringContainsString("charge was declined", $mail->getBody());
        $I->assertStringContainsString("yearly", $mail->getBody());
        $I->assertEquals(ACCOUNT_LEVEL_AWPLUS, $I->grabFromDatabase("Usr", "AccountLevel", ["UserID" => $this->userId]));
    }

    public function testUnknownErrorNotExpired(\CodeGuy $I)
    {
        $paypal = $I->stubMake(PaypalRestApi::class, [
            'payWithSavedCard' => Stub::exactly(1,
                function (Cart $cart, $cardId, $total) {
                    $e = new PayPalConnectionException('', '');
                    $e->setData(json_encode(['name' => 'UNKNOWN_ERROR']));

                    throw $e;
                }
            ),
            'getCardInfo' => Stub::exactly(1, function ($cardId) {
                return new CreditCard(['number' => '1234', 'expire_month' => date("m", strtotime("+1 month")), 'expire_year' => date("Y", strtotime("+1 month"))]);
            }),
        ]);
        $I->mockService(PaypalRestApi::class, $paypal);

        $this->init($I);

        $this->giveUpgrade($I, new \DateTime("-370 day"));

        $this->commandTester->execute(['command' => $this->command->getName(), '--userId' => $this->userId, '--retry-count' => 0]);

        $I->assertEquals(1, $this->getPaidCartsCount($I));
        $this->assertBalanceWatchCredits($I, $this->userId, 1);
        $I->verifyMocks();

        $I->assertEquals(ACCOUNT_LEVEL_AWPLUS, $I->grabFromDatabase("Usr", "AccountLevel", ["UserID" => $this->userId]));
    }

    public function testAT201Subscription1month(\CodeGuy $I)
    {
        $I->mockService(AT201Notifier::class, $I->stubMake(AT201Notifier::class, [
            'subscribed' => Stub::exactly(1,
                function (Cart $cart) use ($I) {
                    $I->assertTrue($cart->getAT201Item() instanceof AT201Subscription1Month);
                    $I->assertEquals($this->user->getUserid(), $cart->getUser()->getUserid());
                }
            ),
            'recurringPayment' => Stub::exactly(1,
                function (Cart $cart) use ($I) {
                    $I->assertTrue($cart->getAT201Item() instanceof AT201Subscription1Month);
                    $I->assertEquals($this->user->getUserid(), $cart->getUser()->getUserid());
                }
            ),
        ]));
        $paypal = $I->stubMake(PaypalRestApi::class, [
            'payWithSavedCard' => Stub::exactly(1,
                function (Cart $cart, $cardId, $total) use ($I) {
                    $I->assertEquals(AT201Subscription1Month::PRICE, $total);
                }
            ),
        ]);
        $I->mockService(PaypalRestApi::class, $paypal);

        $this->init($I);
        // платеж в прошлом, чтобы создалась подписка, по которой пойдет BillCardsCommand
        $I->addUserPayment($this->userId, Cart::PAYMENTTYPE_CREDITCARD, new AT201Subscription1Month(), null, new \DateTime('-1 month -3 day'));

        $this->commandTester->execute(['command' => $this->command->getName(), '--userId' => $this->userId]);

        $I->assertEquals((new \DateTime('+1 month'))->format('Y-m-d'), $this->user->getAt201ExpirationDate()->format('Y-m-d'));
    }

    public function testGiftedAwPlusWithStripe(\CodeGuy $I)
    {
        $stripe = $I->stubMake(StripeClient::class);
        $stripe->paymentIntents = $I->stubMake(PaymentIntentService::class, [
            'create' => Stub::exactly(1, function (array $intentOptions) {
                Assert::assertEquals('cus_1234', $intentOptions['customer']);
                Assert::assertEquals('pm_1234', $intentOptions['payment_method']);

                return new PaymentIntent('pi_' . bin2hex(random_bytes(4)));
            }),
        ]);
        $I->mockService(StripeClient::class, $stripe);

        $this->init($I);

        $em = $I->getContainer()->get("doctrine.orm.entity_manager");

        $cart = $this->giveUpgrade($I, new \DateTime("-370 day"));
        $this->user->setPaypalrecurringprofileid('pm_1234');
        $cart->setPaymenttype(Cart::PAYMENTTYPE_STRIPE_INTENT);
        $em->flush();

        $giverId = $I->createAwUser(null, null, [
            'Subscription' => Usr::SUBSCRIPTION_SAVED_CARD,
            'DefaultTab' => 'cus_1234',
        ]);
        $giver = $I->getContainer()->get('doctrine')->getRepository(Usr::class)->find($giverId);

        $giftItem = new AwPlusGift();
        $giftItem
            ->setId($giver->getId())
            ->setName("AwardWallet Plus yearly subscription")
            ->setDescription("Gift from John Smith  (Merry Christmas and Happy traveling!)");
        $cart->addItem($giftItem);
        $em->persist($giftItem);
        $em->flush();

        $I->assertEquals(1, $this->getPaidCartsCount($I));

        $this->commandTester->execute(['command' => $this->command->getName(), '--userId' => $this->userId]);

        $I->assertEquals(2, $this->getPaidCartsCount($I));
        $cartId = $I
            ->query("
                select max(c.CartID) from Cart c join CartItem ci on c.CartID = ci.CartID 
                where c.UserID = ? and c.PayDate is not null and ci.TypeID = ?
                ",
                [$this->userId, AwPlusSubscription::TYPE]
            )
            ->fetchColumn(0);
        $I->assertEquals(Cart::SOURCE_RECURRING, $I->grabFromDatabase("Cart", "Source", ["CartID" => $cartId]));
        $I->verifyMocks();

        // check email to recipient
        $mails = $I->grabLastMails(2);
        $mails = array_filter($mails, function ($mail) {
            return $mail->getSubject() === 'You’ve just been given a year of AwardWallet Plus!';
        });
        $mail = array_pop($mails);
        $body = $mail->getBody();
        $I->assertStringNotContainsString($cartId, $body);
        $I->assertStringContainsString("Merry Christmas and Happy traveling!", $body);
        $I->assertStringNotContainsString("XXXXXXXXXXXX5678", $body);

        // check email to giver
        $mails = $I->grabLastMails(2);
        $mails = array_filter($mails, function ($mail) use ($cartId) {
            return $mail->getSubject() === 'AwardWallet.com Order ID: ' . $cartId;
        });
        $mail = array_pop($mails);
        $body = $mail->getBody();
        $I->assertStringContainsString($cartId, $body);
        $I->assertStringContainsString("XXXXXXXXXXXX5678", $body);
        $I->assertStringContainsString("VISA", $body);
    }

    public function _after()
    {
        $this->command = null;
        $this->commandTester = null;
        $this->cartManager = null;
        $this->user = null;
        $this->app = null;
    }

    private function newPricingDataProvider()
    {
        return [
            [
                'oldSubscriptionType' => Usr::SUBSCRIPTION_TYPE_AWPLUS,
                'oldSubscriptionDuration' => SubscriptionPeriod::DURATION_1_YEAR,
                'oldPrice' => 30,
                'expectedPrice' => '49.99',
                'expectedCartItemType' => AwPlusSubscription::TYPE,
                'oldBalanceWatchCredits' => 1,
                'expectedBalanceWatchCredits1' => 2,
                'expectedBalanceWatchCredits2' => 3,
            ],
            [
                'oldSubscriptionType' => Usr::SUBSCRIPTION_TYPE_AWPLUS,
                'oldSubscriptionDuration' => SubscriptionPeriod::DURATION_6_MONTHS,
                'oldPrice' => 5,
                'expectedPrice' => '49.99',
                'expectedCartItemType' => AwPlusSubscription::TYPE,
                'oldBalanceWatchCredits' => 1,
                'expectedBalanceWatchCredits1' => 2,
                'expectedBalanceWatchCredits2' => 3,
            ],
            [
                'oldSubscriptionType' => Usr::SUBSCRIPTION_TYPE_AT201,
                'oldSubscriptionDuration' => SubscriptionPeriod::DURATION_1_YEAR,
                'oldPrice' => 89.99,
                'expectedPrice' => '119.99',
                'expectedCartItemType' => AT201Subscription1Year::TYPE,
                'oldBalanceWatchCredits' => 0,
                'expectedBalanceWatchCredits1' => 0,
                'expectedBalanceWatchCredits2' => 0,
            ],
            [
                'oldSubscriptionType' => Usr::SUBSCRIPTION_TYPE_AT201,
                'oldSubscriptionDuration' => SubscriptionPeriod::DURATION_1_MONTH,
                'oldPrice' => 19.99,
                'expectedPrice' => '14.99',
                'expectedCartItemType' => AT201Subscription1Month::TYPE,
                'oldBalanceWatchCredits' => 0,
                'expectedBalanceWatchCredits1' => 0,
                'expectedBalanceWatchCredits2' => 0,
            ],
            [
                'oldSubscriptionType' => Usr::SUBSCRIPTION_TYPE_AT201,
                'oldSubscriptionDuration' => SubscriptionPeriod::DURATION_6_MONTHS,
                'oldPrice' => 59.99,
                'expectedPrice' => '69.99',
                'expectedCartItemType' => AT201Subscription6Months::TYPE,
                'oldBalanceWatchCredits' => 0,
                'expectedBalanceWatchCredits1' => 0,
                'expectedBalanceWatchCredits2' => 0,
            ],
        ];
    }

    // could not set this code in _before() because container mocks should be set before it
    private function init(\CodeGuy $I, array $userFields = [])
    {
        $this->command = $this->app->find('aw:bill-cards');
        $this->commandTester = new CommandTester($this->command);
        $this->cartManager = $I->getContainer()->get("aw.manager.cart");

        $this->userId = $I->createAwUser(null, null, array_merge([
            'Subscription' => Usr::SUBSCRIPTION_SAVED_CARD,
            'PaypalRecurringProfileID' => 'CARD-123',
        ], $userFields));
        $this->user = $I->getContainer()->get('doctrine')->getRepository(Usr::class)->find($this->userId);
        $this->cartManager->setUser($this->user);
    }

    private function giveUpgrade(\CodeGuy $I, \DateTime $date, $promo500k = false, ?\DateTime $scheduledDate = null, $onecards = 0, ?Coupon $coupon = null): Cart
    {
        $cart = $this->cartManager->createNewCart();
        $cart->setCreditcardnumber('XXXXXXXXXXXX5678');
        $cart->setCreditcardtype('VISA');
        $cart->setPaymenttype(Cart::PAYMENTTYPE_CREDITCARD);
        $startDate = new \DateTime();
        $this->cartManager->addAwSubscriptionItem($cart, $startDate);

        if ($promo500k) {
            $this->cartManager->addPromo500k($cart, $startDate);
        }

        if ($onecards > 0) {
            $item3 = new OneCard();
            $item3->setCnt($onecards);
            $cart->addItem($item3);
            $this->cartManager->save($cart);
        }

        if ($coupon !== null) {
            $discount = new Discount();
            $discount->setName("Coupon " . $coupon->getName());
            $discount->setPrice(-1 * $cart->getTotalPrice() * ($coupon->getDiscount() / 100));
            $discount->setId(Discount::ID_COUPON);
            $cart->addItem($discount);
            $cart->setCoupon($coupon);
        }

        if (!empty($scheduledDate)) {
            $em = $I->getContainer()->get('doctrine.orm.entity_manager');

            foreach ($cart->getItems() as $item) {
                $item->setScheduledDate($scheduledDate);
                $em->persist($item);
            }
            $em->flush();
        }
        $this->cartManager->markAsPayed($cart, null, $date);
        $I->assertEquals(1, $this->getPaidCartsCount($I));

        return $cart;
    }

    private function getPaidCartsCount(\CodeGuy $I)
    {
        return $I->query("
            select 
              count(*) 
            from Cart c join CartItem ci on ci.CartID = c.CartID 
            where c.UserID = ? and c.PayDate is not null and ci.TypeID in (" . implode(", ", array_merge(PlusItems::getTypes(), At201Items::getTypes())) . ")",
            [$this->userId]
        )->fetchColumn(0);
    }

    private function assertBalanceWatchCredits(\CodeGuy $I, int $userId, int $credits)
    {
        $I->assertEquals($credits, $I->grabCountFromDatabase('CartItem', [
            'ID' => $userId,
            'TypeID' => BalanceWatchCredit::TYPE,
        ]));
    }
}
