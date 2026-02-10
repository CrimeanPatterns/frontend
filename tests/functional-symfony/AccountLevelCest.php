<?php

namespace AwardWallet\Tests\FunctionalSymfony;

use AwardWallet\MainBundle\Entity\CartItem\AwPlus;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusTrial;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusTrial6Months;
use AwardWallet\MainBundle\Entity\CartItem\Discount;
use AwardWallet\MainBundle\Entity\Sitead;
use AwardWallet\MainBundle\FrameworkExtension\Listeners\ReferalListener;
use AwardWallet\MainBundle\Globals\StringUtils;

/**
 * @group frontend-functional
 */
class AccountLevelCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    private const USER_PASS = 'TestPassword123';
    private const ACCOUNT_TYPE_SELECTOR = '.account-type h5.account-type-title span';

    public function registeredUserWithoutCouponShouldGetTrial3Months(\TestSymfonyGuy $I)
    {
        $this->registerUser($I, $this->generateEmail($I));
        $userId = $this->seeRegisteredUser($I);
        $I->seeInDatabase('Usr', ['UserID' => $userId, 'AccountLevel' => ACCOUNT_LEVEL_AWPLUS]);
        $I->assertEquals(1, $I->grabCountFromDatabase('Cart', ['UserID' => $userId]));
        $cartId = $I->grabFromDatabase('Cart', 'CartID', ['UserID' => $userId]);
        $I->assertNotEmpty($cartId);
        $I->seeInDatabase('CartItem', ['CartID' => $cartId, 'TypeID' => AwPlusTrial::TYPE]);
        $I->amOnRoute('aw_profile_overview');
        $I->see('Plus Trial', ['css' => self::ACCOUNT_TYPE_SELECTOR]);
        // test logo
        $I->seeElement('.header-area .trial');
    }

    public function registeredFromBlogPrompt6MonthsTrial(\TestSymfonyGuy $I)
    {
        $refId = Sitead::BLOG_AWPLUS_6MONTHS_ID[array_rand(Sitead::BLOG_AWPLUS_6MONTHS_ID, 1)];
        $is = (int) $I->grabFromDatabase('SiteAd', 'SiteAdID', ['SiteAdID' => $refId]);

        if (!$is) {
            $I->haveInDatabase('SiteAd', ['SiteAdID' => $refId, 'StartDate' => date('Y-m-d') . ' 00:00:00']);
        }
        $I->amOnRoute('aw_register', [ReferalListener::SESSION_REF_KEY => $refId]);

        $this->registerUser($I, $this->generateEmail($I));
        $userId = $this->seeRegisteredUser($I);
        $I->seeInDatabase('Usr', ['UserID' => $userId, 'AccountLevel' => ACCOUNT_LEVEL_AWPLUS]);
        $I->assertEquals(1, $I->grabCountFromDatabase('Cart', ['UserID' => $userId]));
        $cartId = $I->grabFromDatabase('Cart', 'CartID', ['UserID' => $userId]);
        $I->assertNotEmpty($cartId);
        $I->seeInDatabase('CartItem', ['CartID' => $cartId, 'TypeID' => AwPlusTrial6Months::TYPE]);
        $I->amOnRoute('aw_profile_overview');
        $I->see('Plus Trial', ['css' => self::ACCOUNT_TYPE_SELECTOR]);
        // test logo
        $I->seeElement('.header-area .trial');
    }

    public function registeredUserWithInvalidCouponShouldGetTrial3Months(\TestSymfonyGuy $I)
    {
        $this->registerUser($I, $this->generateEmail($I), 'invalid');
        $userId = $this->seeRegisteredUser($I);
        $I->seeInDatabase('Usr', ['UserID' => $userId, 'AccountLevel' => ACCOUNT_LEVEL_AWPLUS]);
        $I->assertEquals(1, $I->grabCountFromDatabase('Cart', ['UserID' => $userId]));
        $cartId = $I->grabFromDatabase('Cart', 'CartID', ['UserID' => $userId]);
        $I->assertNotEmpty($cartId);
        $I->seeInDatabase('CartItem', ['CartID' => $cartId, 'TypeID' => AwPlusTrial::TYPE]);
        $I->amOnRoute('aw_profile_overview');
        $I->see('Plus Trial', ['css' => self::ACCOUNT_TYPE_SELECTOR]);
        // test logo
        $I->seeElement('.header-area .trial');
    }

    public function registeredUserWithValidCouponShouldGetAwPlus6Months(\TestSymfonyGuy $I)
    {
        $coupon = $this->createCoupon($I);
        $this->registerUser($I, $email = $this->generateEmail($I), $coupon);
        $userId = $this->seeRegisteredUser($I);
        $I->amOnRoute('aw_users_usecoupon', ['Code' => $coupon]);
        $I->click('Apply Coupon');
        $I->see('Coupon Requires Subscription');
        $I->click('Set Up Subscription');
        $I->seeInCurrentRoute('aw_cart_common_paymenttype');
        $I->payWithStripeIntent($userId, $email, true, 0, 49.99, false);
        $I->seeInDatabase('Usr', ['UserID' => $userId, 'AccountLevel' => ACCOUNT_LEVEL_AWPLUS]);
        $I->assertEquals(1, $I->grabCountFromDatabase('Cart', ['UserID' => $userId]));
        $cartId = $I->grabFromDatabase('Cart', 'CartID', [
            'UserID' => $userId,
            'CouponCode' => $coupon,
        ]);
        $I->assertNotEmpty($cartId);
        $I->seeInDatabase('CartItem', ['CartID' => $cartId, 'TypeID' => AwPlus::TYPE]);
        $I->seeInDatabase('CartItem', ['CartID' => $cartId, 'TypeID' => Discount::TYPE]);
        $I->amOnRoute('aw_profile_overview');
        $I->dontSee('Plus Trial', ['css' => self::ACCOUNT_TYPE_SELECTOR]);
        $I->see('Plus', ['css' => self::ACCOUNT_TYPE_SELECTOR]);
        // test logo
        $I->seeElement('.header-area .plus');
    }

    public function userWithTrialShouldGetAwPlus6MonthsAfterCoupon(\TestSymfonyGuy $I)
    {
        $coupon = $this->createCoupon($I);
        $this->registerUser($I, $email = $this->generateEmail($I));
        $userId = $this->seeRegisteredUser($I);
        $I->amOnRoute('aw_profile_overview');
        $I->see('Plus Trial', ['css' => self::ACCOUNT_TYPE_SELECTOR]);
        $I->seeElement('.header-area .trial');

        $I->amOnRoute('aw_users_usecoupon');
        $I->fillField(['name' => 'desktop_profile_coupon[coupon]'], $coupon);
        $I->click('Apply Coupon');
        $I->see('Coupon Requires Subscription');
        $I->click('Set Up Subscription');
        $I->seeInCurrentRoute('aw_cart_common_paymenttype');
        $I->payWithStripeIntent($userId, $email, true, 0, 49.99, false);
        $I->seeInDatabase('Usr', ['UserID' => $userId, 'AccountLevel' => ACCOUNT_LEVEL_AWPLUS]);
        $I->assertEquals(1, $I->grabCountFromDatabase('Cart', ['UserID' => $userId]));
        $cartId = $I->grabFromDatabase('Cart', 'CartID', [
            'UserID' => $userId,
            'CouponCode' => $coupon,
        ]);
        $I->assertNotEmpty($cartId);
        $I->seeInDatabase('CartItem', ['CartID' => $cartId, 'TypeID' => AwPlus::TYPE]);
        $I->seeInDatabase('CartItem', ['CartID' => $cartId, 'TypeID' => Discount::TYPE]);
        $I->amOnRoute('aw_profile_overview');
        $I->dontSee('Plus Trial', ['css' => self::ACCOUNT_TYPE_SELECTOR]);
        $I->see('Plus', ['css' => self::ACCOUNT_TYPE_SELECTOR]);
        // test logo
        $I->seeElement('.header-area .plus');
    }

    public function expiredTrial(\TestSymfonyGuy $I)
    {
        $this->registerUser($I, $this->generateEmail($I));
        $userId = $this->seeRegisteredUser($I);
        $I->assertEquals(1, $I->grabCountFromDatabase('Cart', ['UserID' => $userId]));
        $cartId = $I->grabFromDatabase('Cart', 'CartID', ['UserID' => $userId]);
        $I->assertNotEmpty($cartId);
        $I->seeInDatabase('CartItem', ['CartID' => $cartId, 'TypeID' => AwPlusTrial::TYPE]);

        $I->amOnRoute('aw_profile_overview');
        $I->see('Plus Trial', ['css' => self::ACCOUNT_TYPE_SELECTOR]);
        // test logo
        $I->seeElement('.header-area .trial');

        $I->executeQuery("UPDATE Cart SET PayDate = NOW() - INTERVAL 4 MONTH WHERE CartID = " . $cartId);
        $I->executeQuery("UPDATE Usr SET AccountLevel = " . ACCOUNT_LEVEL_FREE . " WHERE UserID = " . $userId);
        $I->amOnRoute('aw_profile_overview');
        $I->dontSee('Plus Trial', ['css' => self::ACCOUNT_TYPE_SELECTOR]);
        $I->see('Free', ['css' => self::ACCOUNT_TYPE_SELECTOR]);
        // test logo
        $I->dontSeeElement('.header-area .trial');
    }

    private function registerUser(
        \TestSymfonyGuy $I,
        string $email,
        string $coupon = ''
    ) {
        $I->amOnRoute('aw_register');
        $I->saveCsrfToken();
        $I->sendPost('/user/register', [
            'coupon' => $coupon,
            'user' => [
                'email' => $email,
                'firstname' => 'Ragnar',
                'lastname' => 'Petrovich',
                'pass' => self::USER_PASS,
            ],
        ]);
    }

    private function createCoupon(\TestSymfonyGuy $I, bool $firstTimeOnly = true): string
    {
        $coupon = StringUtils::getRandomCode(10);
        $I->executeQuery("DELETE FROM Coupon WHERE Code = '" . $coupon . "'");
        $I->executeQuery("
            INSERT INTO Coupon (Name, Code, Discount, MaxUses, FirstTimeOnly) 
            VALUES ('" . $coupon . "', '" . $coupon . "', 100, 1, '" . intval($firstTimeOnly) . "')
        ");

        return $coupon;
    }

    private function seeRegisteredUser(\TestSymfonyGuy $I): int
    {
        $I->seeResponseContainsJson(['success' => true]);
        $userId = $I->grabDataFromJsonResponse('userId');
        $I->assertNotEmpty($userId);

        return $userId;
    }

    private function generateEmail(\TestSymfonyGuy $I, bool $unique = true): string
    {
        $email = StringUtils::getRandomCode(20) . '@test.com';

        if ($unique) {
            $I->executeQuery("DELETE FROM Usr WHERE Email = '" . $email . "'");
        }

        return $email;
    }
}
