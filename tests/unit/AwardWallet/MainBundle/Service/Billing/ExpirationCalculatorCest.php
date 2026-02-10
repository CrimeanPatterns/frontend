<?php

namespace AwardWallet\Tests\Unit\AwardWallet\MainBundle\Service\Billing;

use AwardWallet\MainBundle\Entity\CartItem\AwPlus;
use AwardWallet\MainBundle\Entity\CartItem\Discount;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Service\Billing\ExpirationCalculator;

/**
 * @group frontend-unit
 */
class ExpirationCalculatorCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function testCouponWithoutDiscountItem(\TestSymfonyGuy $I)
    {
        $couponCode = "cc" . StringUtils::getRandomCode(20);

        $couponId = $I->haveInDatabase("Coupon", [
            "Name" => "View From The Wing",
            "Code" => $couponCode,
            "Discount" => 100,
            "StartDate" => "2011-10-30",
            "EndDate" => date("Y-m-d", strtotime("+10 year")),
            "MaxUses" => 1000000,
            "FirstTimeOnly" => 1,
        ]);

        $userId = $I->createAwUser();

        $cartId = $I->haveInDatabase("Cart", [
            "UserID" => $userId,
            "CouponID" => $couponId,
            "LastUsedDate" => "2018-07-11 21:55:05",
            "PayDate" => "2018-07-11 21:55:05",
            "FirstName" => "Barry",
            "LastName" => "Inciong",
            "Email" => "smith@yahoo.com",
            "CouponName" => "View From The Wing",
            "CouponCode" => $couponCode,
        ]);

        $I->haveInDatabase("CartItem", [
            "CartID" => $cartId,
            "TypeID" => AwPlus::TYPE,
            "ID" => $userId,
            "Name" => "Account upgrade from regular to AwardWallet Plus",
            "Cnt" => 1,
            "Price" => 15,
            "Discount" => 100,
            "Description" => "for <strong>6 months<\/strong> (until <strong>1\/11\/19<\/strong>)",
        ]);

        /** @var ExpirationCalculator $calculator */
        $calculator = $I->grabService(ExpirationCalculator::class);
        $result = $calculator->getAccountExpiration($userId);

        $I->assertEquals(0, $result['lastPrice']);
    }

    public function testCouponWithDiscountItem(\TestSymfonyGuy $I)
    {
        $couponCode = "cc" . StringUtils::getRandomCode(20);

        $couponId = $I->haveInDatabase("Coupon", [
            "Name" => "View From The Wing",
            "Code" => $couponCode,
            "Discount" => 100,
            "StartDate" => "2011-10-30",
            "EndDate" => date("Y-m-d", strtotime("+10 year")),
            "MaxUses" => 1000000,
            "FirstTimeOnly" => 1,
        ]);

        $userId = $I->createAwUser();

        $cartId = $I->haveInDatabase("Cart", [
            "UserID" => $userId,
            "CouponID" => $couponId,
            "LastUsedDate" => "2018-07-11 21:55:05",
            "PayDate" => "2018-07-11 21:55:05",
            "FirstName" => "Barry",
            "LastName" => "Inciong",
            "Email" => "smith@yahoo.com",
            "CouponName" => "View From The Wing",
            "CouponCode" => $couponCode,
        ]);

        $I->haveInDatabase("CartItem", [
            "CartID" => $cartId,
            "TypeID" => AwPlus::TYPE,
            "ID" => $userId,
            "Name" => "Account upgrade from regular to AwardWallet Plus",
            "Cnt" => 1,
            "Price" => 15,
            "Discount" => 0,
            "Description" => "for <strong>6 months<\/strong> (until <strong>1\/11\/19<\/strong>)",
        ]);
        $I->haveInDatabase("CartItem", [
            "CartID" => $cartId,
            "TypeID" => Discount::TYPE,
            "ID" => 100,
            "Name" => "Coupon View From The Wing",
            "Cnt" => 1,
            "Price" => -15,
            "Discount" => 0,
            "Description" => "for <strong>6 months<\/strong> (until <strong>1\/11\/19<\/strong>)",
        ]);

        /** @var ExpirationCalculator $calculator */
        $calculator = $I->grabService(ExpirationCalculator::class);
        $result = $calculator->getAccountExpiration($userId);

        $I->assertEquals(0, $result['lastPrice']);
    }
}
