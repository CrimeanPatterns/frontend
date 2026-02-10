<?php

use AwardWallet\Tests\Acceptance\_steps\UserSteps;
use Codeception\Scenario;

/**
 * @group frontend-acceptance
 * @group acceptance1
 */
class RegisterCest
{
    protected $couponCode;

    public function _before(WebGuy $I)
    {
        $this->couponCode = uniqid('Coupon-');
        $I->shouldHaveInDatabase('Coupon', [
            'Name' => 'UsefulCoupon',
            'Code' => $this->couponCode,
            'Discount' => 100,
            'EndDate' => date("Y-m-d H:i:s", strtotime('+1 year')),
            'MaxUses' => 1,
        ]);
    }

    /**
     * @group auth
     * @throws Exception
     */
    public function index(WebGuy $I, Scenario $scenario)
    {
        $I->wantTo("check new registration");

        // remove test user
        $userSteps = new UserSteps($scenario);
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $I->grabService('doctrine')->getManager();

        $I->amOnPage($I->grabService('router')->generate(\CouponPage::$route) . "?Code=" . $this->couponCode);
        $I->waitForElementVisible(\RegisterPage::$selector_popup, 10);
        $I->click(\RegisterPage::$selector_quickreg_button);
        $I->waitForText('Password');
        $I->fillField(\RegisterPage::$selector_email, bin2hex(random_bytes(10)));
        $I->click(\RegisterPage::$selector_submit);
        $I->waitForText('not a valid email address');
        $I->clearField(\RegisterPage::$selector_email);
        $email = bin2hex(random_bytes(10)) . '@email.com';
        $I->fillField(\RegisterPage::$selector_email, $email);
        $I->fillField(\RegisterPage::$selector_password, CommonUser::$user_password);
        $I->fillField(\RegisterPage::$selector_fn, CommonUser::$user_firstname);
        $I->fillField(\RegisterPage::$selector_ln, CommonUser::$user_lastname);
        $I->seeInField(\RegisterPage::$selector_coupon, $this->couponCode);
        $I->click(\RegisterPage::$selector_submit);
        $I->waitForText('Please enter a coupon code', 60);
        $I->seeInField('//input[@id="desktop_profile_coupon_coupon"]', $this->couponCode);
        $I->click('Apply Coupon');
        $I->waitForText('Coupon Requires Subscription', 60);
    }
}
