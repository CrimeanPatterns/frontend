<?php

namespace AwardWallet\Tests\FunctionalSymfony\User;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem\AwPlus1Year;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusTrial;
use AwardWallet\MainBundle\Entity\CartItem\BalanceWatchCredit;
use AwardWallet\MainBundle\Entity\CartItem\Discount;
use AwardWallet\MainBundle\Entity\CartItem\OneCard;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\Tests\FunctionalSymfony\Security\LoginTrait;

/**
 * @group frontend-functional
 */
class CouponCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use LoginTrait;

    public function useCouponValid(\TestSymfonyGuy $I)
    {
        $expiredCouponCode = 'ExpiredCoupon-' . StringUtils::getRandomCode(15);
        $usefulCouponCode = 'UsefulCoupon-' . StringUtils::getRandomCode(15);

        $I->shouldHaveInDatabase('Coupon', [
            'Name' => 'ExpiredCoupon',
            'Code' => $expiredCouponCode,
            'Discount' => 100,
            'EndDate' => date("Y-m-d H:i:s", strtotime('-1 year')),
            'MaxUses' => 1,
        ]);
        $I->shouldHaveInDatabase('Coupon', [
            'Name' => 'UsefulCoupon',
            'Code' => $usefulCouponCode,
            'Discount' => 100,
            'EndDate' => date("Y-m-d H:i:s", strtotime('+1 year')),
            'MaxUses' => 1,
        ]);

        $userId = $I->createAwUser(
            $username = 'tuc' . $I->grabRandomString(10),
            $password = 'testpass',
            ['Email' => $email = "$username@fakeemail.com", 'InBeta' => 1, 'BetaApproved' => 1]
        );
        $startDate = new \DateTime($I->grabFromDatabase('Usr', 'CreationDateTime', ['UserID' => $userId]));
        $page = $I->grabService('router')->generate('aw_users_usecoupon');
        $I->amOnPage($page . "?_switch_user=" . $username);

        $I->fillField('Coupon code', $I->grabRandomString(15));
        $I->click('Apply Coupon');
        $I->see('Invalid coupon code');
        $I->fillField('Coupon code', $expiredCouponCode);
        $I->click('Apply Coupon');
        $I->see('Expired coupon code');
        $I->fillField('Coupon code', $usefulCouponCode);
        $I->click('Apply Coupon');
        $I->see('Coupon Requires Subscription');
        $I->click('Set Up Subscription');
        $I->seeInCurrentRoute('aw_cart_common_paymenttype');
        $I->payWithStripeIntent($userId, $email, true, 0, 49.99, false);

        $date = $I->grabFromDatabase('Usr', 'PlusExpirationDate', [
            'Login' => $username,
        ]);
        $date = new \DateTime($date);
        $I->assertNotEmpty($date);
        $diff = $startDate->diff($date);
        $I->assertEquals(6, $diff->m);

        $discount = $I->query("select ci.Discount from CartItem ci, Cart c
        where ci.CartID = c.CartID and ci.TypeID = " . Discount::TYPE . " and c.UserID = $userId and c.PayDate is not null")->fetchColumn();

        $I->assertEquals(0, $discount);
        $I->dontSeeEmailTo($email, 'AwardWallet.com Order ID', null, 60);
    }

    /**
     * @group locks
     */
    public function useCouponLockout(\TestSymfonyGuy $I)
    {
        $I->wantTo("check coupon");

        $I->createAwUser(
            $username = 'tuc' . $I->grabRandomString(10),
            $password = 'testpass',
            ['InBeta' => 1, 'BetaApproved' => 1]
        );
        $page = $I->grabService('router')->generate('aw_users_usecoupon');
        $I->amOnPage($page . "?_switch_user=" . $username);

        $I->fillField('Coupon code', $I->grabRandomString(15));
        $I->click('Apply Coupon');
        $I->see('Invalid coupon code');

        for ($i = 0; $i <= 5; $i++) {
            $I->fillField('Coupon code', $I->grabRandomString(15));
            $I->click('Apply Coupon');
        }

        $I->see('Please wait 5 minutes before next attempt.');
    }

    public function firstTimeOnlySuccess(\TestSymfonyGuy $I)
    {
        $userId = $I->createAwUser(
            $username = 'tuc' . $I->grabRandomString(10),
            $password = 'testpass',
            ['Email' => $email = "$username@fakeemail.com", 'InBeta' => 1, 'BetaApproved' => 1, 'CreationDateTime' => date("Y-m-d", strtotime("-7 day"))]
        );
        $I->addUserPayment($userId, Cart::PAYMENTTYPE_CREDITCARD, new AwPlusTrial());
        $page = $I->grabService('router')->generate('aw_users_usecoupon');
        $I->amOnPage($page . "?_switch_user=" . $username);

        $oldDate = new \DateTime($I->grabFromDatabase('Usr', 'PlusExpirationDate', [
            'Login' => $username,
        ]));

        $couponCode = 'UsefulCoupon-' . StringUtils::getRandomCode(15);
        $I->shouldHaveInDatabase('Coupon', [
            'Name' => 'UsefulCoupon',
            'Code' => $couponCode,
            'Discount' => 100,
            'EndDate' => date("Y-m-d H:i:s", strtotime('+1 year')),
            'MaxUses' => 1,
            'FirstTimeOnly' => 1,
        ]);

        $I->fillField('Coupon code', $couponCode);
        $I->click('Apply Coupon');
        $I->see('Coupon Requires Subscription');
        $I->click('Set Up Subscription');
        $I->seeInCurrentRoute('aw_cart_common_paymenttype');
        $I->payWithStripeIntent($userId, $email, true, 0, 49.99, false);
        $date = new \DateTime($I->grabFromDatabase('Usr', 'PlusExpirationDate', [
            'Login' => $username,
        ]));
        $I->assertNotEmpty($date);
        $diff = $oldDate->diff($date);
        $I->assertGreaterThan(88, $diff->days);
        $I->assertGreaterOrEquals(2, $diff->m);
        $I->assertLessOrEquals(3, $diff->m);
        $I->dontSeeEmailTo($email, 'AwardWallet.com Order ID', null, 60);
    }

    public function firstTimeOnlyNoTrial(\TestSymfonyGuy $I)
    {
        $userId = $I->createAwUser(
            $username = 'tuc' . $I->grabRandomString(10),
            $password = 'testpass',
            ['Email' => $email = "$username@fakeemail.com", 'InBeta' => 1, 'BetaApproved' => 1, 'CreationDateTime' => date("Y-m-d", strtotime("-7 day"))]
        );
        $page = $I->grabService('router')->generate('aw_users_usecoupon');
        $I->amOnPage($page . "?_switch_user=" . $username);

        $oldDate = $I->grabFromDatabase('Usr', 'PlusExpirationDate', [
            'Login' => $username,
        ]);
        $I->assertIsEmpty($oldDate);

        $couponCode = 'UsefulCoupon-' . StringUtils::getRandomCode(15);
        $I->shouldHaveInDatabase('Coupon', [
            'Name' => 'UsefulCoupon',
            'Code' => $couponCode,
            'Discount' => 100,
            'EndDate' => date("Y-m-d H:i:s", strtotime('+1 year')),
            'MaxUses' => 1,
            'FirstTimeOnly' => 1,
        ]);

        $I->fillField('Coupon code', $couponCode);
        $I->click('Apply Coupon');
        $I->see('Coupon Requires Subscription');
        $I->click('Set Up Subscription');
        $I->seeInCurrentRoute('aw_cart_common_paymenttype');
        $I->payWithStripeIntent($userId, $email, true, 0, 49.99, false);
        $date = $I->grabFromDatabase('Usr', 'PlusExpirationDate', [
            'Login' => $username,
        ]);
        $I->assertNotEmpty($date);
        $I->dontSeeEmailTo($email, 'AwardWallet.com Order ID', null, 60);
    }

    public function firstTimeOnlyFailure(\TestSymfonyGuy $I)
    {
        $userId = $I->createAwUser(
            $username = 'tuc' . $I->grabRandomString(10),
            $password = 'testpass',
            ['InBeta' => 1, 'BetaApproved' => 1, 'CreationDateTime' => date("Y-m-d", strtotime("-7 day"))]
        );
        $I->addUserPayment($userId, Cart::PAYMENTTYPE_CREDITCARD, new AwPlus1Year());
        $page = $I->grabService('router')->generate('aw_users_usecoupon');
        $I->amOnPage($page . "?_switch_user=" . $username);

        $oldDate = new \DateTime($I->grabFromDatabase('Usr', 'PlusExpirationDate', [
            'Login' => $username,
        ]));

        $couponCode = 'UsefulCoupon-' . StringUtils::getRandomCode(15);
        $I->shouldHaveInDatabase('Coupon', [
            'Name' => 'UsefulCoupon',
            'Code' => $couponCode,
            'Discount' => 100,
            'EndDate' => date("Y-m-d H:i:s", strtotime('+1 year')),
            'MaxUses' => 1,
            'FirstTimeOnly' => 1,
        ]);

        $I->fillField('Coupon code', $couponCode);
        $I->click('Apply Coupon');
        $I->see("This coupon code is only valid for the users who never had AwardWallet Plus in the past.");
        $date = $I->grabFromDatabase('Usr', 'PlusExpirationDate', [
            'Login' => $username,
        ]);
        $date = new \DateTime($date);
        $I->assertNotEmpty($date);
        $diff = $oldDate->diff($date);
        $I->assertEquals(0, $diff->m);
        $I->assertEquals(0, $diff->y);
    }

    public function couponWith25Discount(\TestSymfonyGuy $I)
    {
        $userId = $I->createAwUser(
            $username = 'tuc' . $I->grabRandomString(10)
        );
        $page = $I->grabService('router')->generate('aw_users_usecoupon');
        $I->amOnPage($page . "?_switch_user=" . $username);

        $oldDate = $I->grabFromDatabase('Usr', 'PlusExpirationDate', [
            'Login' => $username,
        ]);
        $I->assertIsEmpty($oldDate);

        $couponCode = StringUtils::getRandomCode(20);
        $couponId = $I->haveInDatabase("Coupon", [
            "Code" => $couponCode,
            "Name" => "Special Discount 25%",
            "Discount" => 25,
            "FirstTimeOnly" => 0,
        ]);
        $I->haveInDatabase("CouponItem", ["CouponID" => $couponId, "CartItemType" => AwPlusSubscription::TYPE]);

        $I->prepareStripeIntentMocks();
        $I->fillField('Coupon code', $couponCode);
        $I->click('Apply Coupon');
        $I->seeCurrentUrlEquals('/cart/paymentType');
        $I->click('Continue');
        $I->seeInCurrentRoute('aw_cart_stripe_orderdetails');
        $I->see("Special Discount 25%");
        $I->see('$' . round(AwPlusSubscription::PRICE * 0.75, 2));
        $I->see('Balance Watch Credits');
    }

    public function couponNotForSubscribers(\TestSymfonyGuy $I)
    {
        $userId = $I->createAwUser(
            $username = 'tuc' . $I->grabRandomString(10),
            null,
            ['Subscription' => Usr::SUBSCRIPTION_SAVED_CARD]
        );
        $page = $I->grabService('router')->generate('aw_users_usecoupon');
        $I->amOnPage($page . "?_switch_user=" . $username);

        $oldDate = $I->grabFromDatabase('Usr', 'PlusExpirationDate', [
            'Login' => $username,
        ]);
        $I->assertIsEmpty($oldDate);

        $couponCode = StringUtils::getRandomCode(20);
        $couponId = $I->haveInDatabase("Coupon", [
            "Code" => $couponCode,
            "Name" => "Special Discount 25%",
            "Discount" => 25,
            "FirstTimeOnly" => 0,
        ]);
        $I->haveInDatabase("CouponItem", ["CouponID" => $couponId, "CartItemType" => AwPlusSubscription::TYPE]);

        $I->fillField('Coupon code', $couponCode);
        $I->click('Apply Coupon');
        $I->see("You already have a current subscription to AwardWallet Plus, unfortunately this coupon will not work with your account.");
    }

    public function onecard(\TestSymfonyGuy $I)
    {
        $I->createAwUser(
            $username = 'tuc' . $I->grabRandomString(10),
            $password = 'testpass',
            ['Email' => $email = "$username@fakeemail.com"]
        );
        $page = $I->grabService('router')->generate('aw_users_usecoupon');
        $I->amOnPage($page . "?_switch_user=" . $username);

        $oldDate = $I->grabFromDatabase('Usr', 'PlusExpirationDate', [
            'Login' => $username,
        ]);
        $I->assertIsEmpty($oldDate);

        $couponCode = StringUtils::getRandomCode(20);
        $couponId = $I->haveInDatabase("Coupon", [
            "Code" => $couponCode,
            "Name" => "Free Onecard",
            "Discount" => 100,
            "FirstTimeOnly" => 0,
        ]);
        $I->haveInDatabase("CouponItem", ["CouponID" => $couponId, "CartItemType" => OneCard::TYPE]);

        $I->fillField('Coupon code', $couponCode);
        $I->click('Apply Coupon');
        $I->seeCurrentUrlEquals('/user/useCoupon');
        $I->see("Free Onecard");
        $I->see('OneCard Credits');
        $I->see('$0.00');
        $I->dontSee('Balance Watch Credits');

        $cartId = $I->grabFromDatabase("Cart", "CartID", ["CouponID" => $couponId]);
        $I->assertNotEmpty($cartId);
        $I->assertEquals(2, $I->grabCountFromDatabase("CartItem", ["CartID" => $cartId]));
        $I->assertEquals(1, $I->grabCountFromDatabase("CartItem", ["CartID" => $cartId, "TypeID" => OneCard::TYPE]));
        $I->assertEquals(1, $I->grabCountFromDatabase("CartItem", ["CartID" => $cartId, "TypeID" => Discount::TYPE]));

        $I->dontSeeEmailTo($email, 'AwardWallet.com Order ID', null, 60);
    }

    public function registerWith25Coupon(\TestSymfonyGuy $I)
    {
        $couponCode = StringUtils::getRandomCode(20);
        $couponId = $I->haveInDatabase("Coupon", [
            "Code" => $couponCode,
            "Name" => "Special Discount 25%",
            "Discount" => 25,
            "FirstTimeOnly" => 0,
        ]);
        $I->haveInDatabase("CouponItem", ["CouponID" => $couponId, "CartItemType" => AwPlusSubscription::TYPE]);

        $this->loadCSRF($I);

        $login = bin2hex(random_bytes(10));
        $I->haveHttpHeader("Content-Type", "application/json");

        $I->sendPOST("/user/register", '{"user":{"pass":"Somepass12","email":"' . $login . '@gmail.com","firstname":"Vladimir","lastname":"Silantyev"},"coupon":"' . $couponCode . '","recaptcha":"xxx"}');
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(["success" => true]);
    }

    public function multipleItems(\TestSymfonyGuy $I)
    {
        $userId = $I->createAwUser(
            $username = 'tuc' . $I->grabRandomString(10),
            $password = 'testpass',
            ['Email' => $email = "$username@fakeemail.com"]
        );
        $page = $I->grabService('router')->generate('aw_users_usecoupon');
        $I->amOnPage($page . "?_switch_user=" . $username);

        $oldDate = $I->grabFromDatabase('Usr', 'PlusExpirationDate', [
            'Login' => $username,
        ]);
        $I->assertIsEmpty($oldDate);

        $couponCode = StringUtils::getRandomCode(20);
        $couponId = $I->haveInDatabase("Coupon", [
            "Code" => $couponCode,
            "Name" => "Multiple Items",
            "Discount" => 100,
            "FirstTimeOnly" => 0,
        ]);
        $I->haveInDatabase("CouponItem", ["CouponID" => $couponId, "CartItemType" => OneCard::TYPE]);
        $I->haveInDatabase("CouponItem", [
            "CouponID" => $couponId,
            "CartItemType" => BalanceWatchCredit::TYPE,
            "Cnt" => 3,
        ]);

        $I->fillField('Coupon code', $couponCode);
        $I->click('Apply Coupon');
        $I->seeCurrentUrlEquals('/user/useCoupon');
        $I->see("Multiple Items");
        $I->see('OneCard Credits');
        $I->see('Balance Watch Credit');
        $I->see('3');
        $I->see('$0.00');

        $cartId = $I->grabFromDatabase("Cart", "CartID", ["CouponID" => $couponId]);
        $I->assertNotEmpty($cartId);
        $I->assertEquals(3, $I->grabCountFromDatabase("CartItem", ["CartID" => $cartId]));
        $I->assertEquals(1, $I->grabCountFromDatabase("CartItem", ["CartID" => $cartId, "TypeID" => BalanceWatchCredit::TYPE, "Cnt" => 3]));
        $I->assertEquals(1, $I->grabCountFromDatabase("CartItem", ["CartID" => $cartId, "TypeID" => OneCard::TYPE]));
        $I->assertEquals(1, $I->grabCountFromDatabase("CartItem", ["CartID" => $cartId, "TypeID" => Discount::TYPE]));
        $I->assertEquals(3, $I->grabFromDatabase("Usr", "BalanceWatchCredits", ["UserID" => $userId]));

        $I->dontSeeEmailTo($email, 'AwardWallet.com Order ID', null, 60);
    }
}
