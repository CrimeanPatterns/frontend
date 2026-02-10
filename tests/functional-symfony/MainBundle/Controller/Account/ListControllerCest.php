<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\Account;

use AwardWallet\Tests\Modules\Access\Action;
use AwardWallet\Tests\Modules\Access\CouponReadScenario;
use Codeception\Example;
use PHPUnit\Framework\ExpectationFailedException;

/**
 * @group frontend-functional
 */
class ListControllerCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    /**
     * @dataProvider couponReadDataProvider
     */
    public function testReadCoupon(\TestSymfonyGuy $I, Example $example)
    {
        /** @var CouponReadScenario $scenario */
        $scenario = $example['scenario'];
        $scenario->create($I);
        $scenario->wantToTest($I);

        $I->amOnRoute("aw_account_list");
        $I->seeResponseCodeIs(200);

        try {
            $I->seeCurrentRouteIs('aw_account_list');
            $accountsData = $I->grabAttributeFrom('//div[@id="update-all-account-container"]', 'data-accountsdata');
        } catch (ExpectationFailedException $e) {
            // in case of redirect to provider-select page
            $accountsData = '';
        }

        switch ($scenario->expectedAction) {
            case Action::ALLOWED:
                $I->assertStringContainsString($scenario->couponValue, $accountsData);
                $I->assertStringContainsString($scenario->couponNumber, $accountsData);
                $I->assertStringContainsString((string) $scenario->expirationDate->getTimestamp(), $accountsData);
                $I->assertStringContainsString('"ExpirationDate":"in 1 month"', $accountsData);
                $I->assertStringContainsString('"ExpirationDateTip":"' . $scenario->expirationDate->format("n\\\/j\\\/y") . '"', $accountsData);
                $I->assertStringContainsString('"ExpirationMode":"warn"', $accountsData);

                break;

            default:
                $I->assertStringNotContainsString($scenario->couponValue, $accountsData);
                $I->assertStringNotContainsString($scenario->couponNumber, $accountsData);
                $I->assertStringNotContainsString((string) $scenario->expirationDate->getTimestamp(), $accountsData);
                $I->assertStringNotContainsString('"ExpirationDate":"in 1 month"', $accountsData);
                $I->assertStringNotContainsString('"ExpirationDateTip":"' . $scenario->expirationDate->format("n\\\/j\\\/y") . '"', $accountsData);
                $I->assertStringNotContainsString('"ExpirationMode":"warn"', $accountsData);
        }
    }

    private function couponReadDataProvider()
    {
        return CouponReadScenario::dataProvider();
    }
}
