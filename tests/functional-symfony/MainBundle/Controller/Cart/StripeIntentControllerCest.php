<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\Cart;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem\AT201Subscription6Months;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription;
use AwardWallet\MainBundle\Entity\CartItem\Discount;
use AwardWallet\MainBundle\Entity\CartItem\OneCard;
use AwardWallet\MainBundle\Entity\Repositories\CartRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\Cart\SubscriptionPeriod;
use AwardWallet\MainBundle\Globals\Cart\SubscriptionPrice;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Service\AppBot\AT201Notifier;
use AwardWallet\MainBundle\Service\Billing\PaypalSoapCancelListener;
use AwardWallet\Tests\FunctionalSymfony\BaseTraitCest;
use AwardWallet\Tests\FunctionalSymfony\Traits\FreeUser;
use AwardWallet\Tests\FunctionalSymfony\Traits\LoggedIn;
use Codeception\Util\Stub;
use Doctrine\ORM\EntityManager;
use Prophecy\Argument;
use Symfony\Component\Routing\RouterInterface;

/**
 * @group frontend-functional
 * @group billing
 */
class StripeIntentControllerCest extends BaseTraitCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use FreeUser;
    use LoggedIn;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var EntityManager
     */
    private $em;

    public function _before(\TestSymfonyGuy $I)
    {
        parent::_before($I);
        $this->router = $I->grabService('router');
        $this->em = $I->grabService('doctrine.orm.entity_manager');
    }

    public function _after(\TestSymfonyGuy $I)
    {
        $this->router = $this->em = null;
        // $I->verifyMocks();
        parent::_after($I);
    }

    public function testAwPlusSubscriptionImmediate(\TestSymfonyGuy $I)
    {
        $paypalCancelListener = $I->prophesize(PaypalSoapCancelListener::class);
        $paypalCancelListener
            ->cancelRecurring(Argument::cetera())
            ->shouldBeCalled()
        ;

        $I->mockService(PaypalSoapCancelListener::class, $paypalCancelListener->reveal());

        $I->executeQuery(
            "update Usr set 
            DiscountedUpgradeBefore = null, 
            Subscription = " . Usr::SUBSCRIPTION_PAYPAL . ", 
            PaypalRecurringProfileID = '123' 
        where 
            UserID = {$this->user->getUserid()}"
        );
        $I->assertEquals(ACCOUNT_LEVEL_FREE, $I->grabFromDatabase("Usr", "AccountLevel", ["UserID" => $this->user->getUserid()]));
        $I->amOnPage($this->router->generate("aw_users_pay"));
        $I->seeInSource('"giveAWPlus":true');
        $I->dontSee("Early Supporter");
        $I->submitForm("//form", [
            'user_pay' => [
                '_token' => $I->grabAttributeFrom("//input[@name='user_pay[_token]']", "value"),
                'awPlus' => 'true',
                'onecard' => 0,
            ],
        ]);
        $I->payWithStripeIntent($this->user->getId(), $this->user->getEmail(), true, AwPlusSubscription::PRICE, AwPlusSubscription::PRICE);
        $email = $I->grabLastMail();
        $I->assertStringContainsString('You have successfully paid *$' . AwPlusSubscription::PRICE . '*', $email->getBody());
        $lastPaidCartId = $I->query("select max(CartID) from Cart where UserID = ? and PayDate is not null", [$this->user->getId()])->fetchColumn();
        /** @var Cart $cart */
        $cart = $I->grabService(CartRepository::class)->find($lastPaidCartId);
        $plusItemId = $cart->getPlusItem()->getCartitemid();

        $this->assertBalanceWatchCredits($I);
        $I->seeInDatabase("Usr", [
            "UserID" => $this->user->getId(),
            "Subscription" => Usr::SUBSCRIPTION_STRIPE,
            "SubscriptionType" => Usr::SUBSCRIPTION_TYPE_AWPLUS,
            "SubscriptionPrice" => AwPlusSubscription::PRICE,
            "SubscriptionPeriod" => SubscriptionPeriod::DAYS_1_YEAR,
            "FirstSubscriptionCartItemID" => $plusItemId,
            "LastSubscriptionCartItemID" => $plusItemId,
        ]);
    }

    public function testAwPlusSubscriptionScheduled(\TestSymfonyGuy $I)
    {
        $paypalCancelListener = $I->prophesize(PaypalSoapCancelListener::class);
        $paypalCancelListener
            ->cancelRecurring(Argument::cetera())
            ->shouldBeCalled()
        ;
        $I->mockService(PaypalSoapCancelListener::class, $paypalCancelListener->reveal());

        $I->addUserPayment($this->user->getUserid(), PAYMENTTYPE_PAYPAL, new AwPlusSubscription(), null, new \DateTime('-2 months'));
        $I->executeQuery(
            "update Usr set 
            DiscountedUpgradeBefore = null, 
            Subscription = " . Usr::SUBSCRIPTION_PAYPAL . ", 
            PaypalRecurringProfileID = '123' 
        where 
            UserID = {$this->user->getUserid()}"
        );
        $I->assertEquals(ACCOUNT_LEVEL_AWPLUS, $I->grabFromDatabase("Usr", "AccountLevel", ["UserID" => $this->user->getUserid()]));
        $I->amOnPage("/cart/paypal/change-payment");
        //        $I->amOnPage($this->router->generate("aw_users_pay"));
        //        $I->seeInSource('"giveAWPlus":true');
        //        $I->dontSee("Early Supporter");
        //        $I->submitForm("//form", [
        //            'user_pay' => [
        //                '_token' => $I->grabAttributeFrom("//input[@name='user_pay[_token]']", "value"),
        //                'awPlus' => 'true',
        //                'onecard' => 0,
        //            ],
        //        ]);
        $I->payWithStripeIntent($this->user->getId(), $this->user->getEmail(), true, null, AwPlusSubscription::PRICE, false);

        $this->assertBalanceWatchCredits($I);
    }

    public function testAwPlusSubscriptionEarlySupporterFullPrice(\TestSymfonyGuy $I)
    {
        $referrer = 99;
        $I->executeQuery("update Usr set DiscountedUpgradeBefore = adddate(now(), 30), Promo500k = 1 where UserID = {$this->user->getUserid()}");
        $I->assertEquals(ACCOUNT_LEVEL_FREE, $I->grabFromDatabase("Usr", "AccountLevel", ["UserID" => $this->user->getUserid()]));
        $I->amOnPage($this->router->generate("aw_users_pay", ['ref' => $referrer]));
        $I->seeInSource('"giveAWPlus":true');
        $I->dontSee("Early Supporter");
        $I->dontSeeInSource('"discountAmount":20');
        $I->submitForm("//form", [
            'user_pay' => [
                '_token' => $I->grabAttributeFrom("//input[@name='user_pay[_token]']", "value"),
                'awPlus' => 'true',
                'onecard' => 0,
            ],
        ]);
        $expectedPrice = SubscriptionPrice::getPrice(Usr::SUBSCRIPTION_TYPE_AWPLUS, SubscriptionPeriod::DURATION_1_YEAR);
        $cartId = $I->payWithStripeIntent($this->user->getId(), $this->user->getEmail(), true, $expectedPrice, $expectedPrice);
        $I->dontSeeInDatabase("CartItem", ["CartID" => $cartId, "Name" => "Early Supporter discount (thank you)", "Price" => -20]);
        $I->assertEquals($referrer, $I->grabFromDatabase("Cart", "CameFrom", ["CartID" => $cartId]));
        $email = $I->grabLastMail();
        $I->assertStringContainsString('You have successfully paid *$' . $expectedPrice . '*', $email->getBody());

        $this->assertBalanceWatchCredits($I);
    }

    public function testSubscriptionWith25PercentDiscount(\TestSymfonyGuy $I)
    {
        $couponCode = StringUtils::getRandomCode(20);
        $couponId = $I->haveInDatabase("Coupon", [
            "Code" => $couponCode,
            "Name" => "Special Discount 25%",
            "Discount" => 25,
            "Firsttimeonly" => 0,
        ]);
        $I->haveInDatabase("CouponItem", ["CouponID" => $couponId, "CartItemType" => AwPlusSubscription::TYPE]);

        $cartId = $I->haveInDatabase("Cart", [
            "UserID" => $this->user->getUserid(),
            "CouponID" => $couponId,
            "LastUsedDate" => date("Y-m-d H:i:s"),
            "CalcDate" => date("Y-m-d H:i:s"),
        ]);
        $I->haveInDatabase("CartItem", [
            "CartID" => $cartId,
            "TypeID" => AwPlusSubscription::TYPE,
            "ID" => $this->user->getUserid(),
            "Name" => "AwardWallet Plus yearly subscription",
            "Cnt" => 1,
            "Price" => 30,
            "Discount" => 0,
            "Description" => "12 months (starting from 12/20/18)",
        ]);
        $I->haveInDatabase("CartItem", [
            "CartID" => $cartId,
            "TypeID" => Discount::TYPE,
            "Name" => "Special Discount 25%",
            "Cnt" => 1,
            "Price" => -7.5,
        ]);

        $I->assertEquals(ACCOUNT_LEVEL_FREE, $I->grabFromDatabase("Usr", "AccountLevel", ["UserID" => $this->user->getUserid()]));
        $I->amOnPage($this->router->generate("aw_cart_common_paymenttype"));

        $I->payWithStripeIntent($this->user->getId(), $this->user->getEmail(), true, 22.5, 22.5, true, function () use ($I) {
            $I->see("Special Discount 25%");
            $I->see('$22.50');
        });
        $email = $I->grabLastMail();
        $I->assertStringContainsString('You have successfully paid *$22.50*', $email->getBody());

        $this->assertBalanceWatchCredits($I);
    }

    public function testChangePaymentType(\TestSymfonyGuy $I)
    {
        $userId = $this->user->getUserid();
        $I->updateInDatabase(
            'Usr',
            ['Subscription' => Usr::SUBSCRIPTION_SAVED_CARD],
            ['UserID' => $userId]
        );
        $I->mockService(AT201Notifier::class, $I->stubMake(AT201Notifier::class, [
            'subscribed' => Stub::exactly(
                1,
                function (Cart $cart) use ($I, $userId) {
                    $I->assertTrue($cart->getAT201Item() instanceof AT201Subscription6Months);
                    $I->assertEquals($userId, $cart->getUser()->getUserid());
                }
            ),
        ]));

        $plusItem = new AT201Subscription6Months();
        $I->addUserPayment($userId, Cart::PAYMENTTYPE_CREDITCARD, $plusItem);
        $I->seeInDatabase(
            'Usr',
            [
                'UserID' => $userId,
                'Subscription' => Usr::SUBSCRIPTION_SAVED_CARD,
                'SubscriptionType' => Usr::SUBSCRIPTION_TYPE_AT201,
            ]
        );

        $I->amOnPage("/user/profile?_switch_user=" . $this->user->getUsername());
        $I->amOnPage($this->router->generate("aw_cart_change_payment_method_authorized"));

        $I->seeInCurrentRoute('aw_cart_stripe_orderdetails');
        $I->dontSee('Price', ['css' => 'form table.main-table.payment']);
    }

    private function assertBalanceWatchCredits(\TestSymfonyGuy $I, int $credits = 1)
    {
        $I->assertEquals(ACCOUNT_LEVEL_AWPLUS, $I->grabFromDatabase('Usr', 'AccountLevel', ['UserID' => $this->user->getUserid()]));
        $I->assertEquals($credits, $I->grabFromDatabase('Usr', 'BalanceWatchCredits', ['UserID' => $this->user->getUserid()]));
    }
}
