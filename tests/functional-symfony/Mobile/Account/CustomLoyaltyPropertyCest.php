<?php

namespace AwardWallet\tests\FunctionalSymfony\Mobile\Account;

use AwardWallet\MainBundle\Globals\Headers\MobileHeaders;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\Tests\FunctionalSymfony\Mobile\AbstractCest;
use Codeception\Module\Aw;

use function PHPUnit\Framework\assertJsonStringEqualsJsonString;

/**
 * @group mobile
 * @group frontend-functional
 */
class CustomLoyaltyPropertyCest extends AbstractCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    public const ROUTE_PREFIX = '/m/api/customLoyaltyProperty';
    /**
     * @var int
     */
    protected $providerId;

    public function _before(\TestSymfonyGuy $I)
    {
        parent::_before($I);
        parent::createUserAndLogin($I, 'custllt-', Aw::DEFAULT_PASSWORD, []);

        $this->providerId = $I->createAwProvider(
            $providerCode = 'clp' . $I->grabRandomString(5),
            $providerCode
        );
        $I->haveHttpHeader(MobileHeaders::MOBILE_VERSION, '3.20.0+100500');
    }

    public function validAccountPropertyShouldBeSaved(\TestSymfonyGuy $I)
    {
        $accountId = $I->createAwAccount($this->userId, $this->providerId, 'somelogin');
        $this->saveWithSuccess(
            $I,
            $this->getAccountUrl($accountId),
            'POST',
            ['BarCodeType' => '123'],
            ['AccountID' => $accountId]
        );
    }

    public function validSubccountPropertyShouldBeSaved(\TestSymfonyGuy $I)
    {
        $accountId = $I->createAwAccount($this->userId, $this->providerId, 'somelogin');
        $subaccountId = $I->createAwSubAccount($accountId);
        $this->saveWithSuccess(
            $I,
            $this->getSubaccountUrl($accountId, $subaccountId),
            'POST',
            ['BarCodeType' => '123'],
            ['SubAccountID' => $subaccountId]
        );
    }

    public function validCouponPropertyShouldBeSaved(\TestSymfonyGuy $I)
    {
        $couponId = $I->createAwCoupon($this->userId, 'somecoupon', 'value', 'desc');
        $this->saveWithSuccess(
            $I,
            $this->getCouponUrl($couponId),
            'POST',
            ['BarCodeType' => '123'],
            ['ProviderCouponID' => $couponId]
        );
    }

    public function invalidAccountPropertyShouldNotBeSaved(\TestSymfonyGuy $I)
    {
        $accountId = $I->createAwAccount($this->userId, $this->providerId, 'somelogin');
        $this->saveWithError(
            $I,
            $this->getAccountUrl($accountId),
            'POST',
            ['BlaBLadddd' => '123'],
            ['AccountID' => $accountId]
        );
    }

    public function unauthorizedAccountAccessShouldNotProceed(\TestSymfonyGuy $I)
    {
        $userId = $I->createAwUser('clp' . StringHandler::getRandomCode(10));
        $accountId = $I->createAwAccount($userId, $this->providerId, 'somelogin');
        $this->saveWithError(
            $I,
            $this->getAccountUrl($accountId),
            'POST',
            ['BarCodeType' => '123'],
            ['AccountID' => $accountId]
        );
    }

    public function unauthorizedSubaccountAccessShouldNotProceed(\TestSymfonyGuy $I)
    {
        $userId = $I->createAwUser('clp' . StringHandler::getRandomCode(10));
        $accountId = $I->createAwAccount($userId, $this->providerId, 'somelogin');
        $subaccountId = $I->createAwSubAccount($accountId);
        $this->saveWithError(
            $I,
            $this->getSubaccountUrl($accountId, $subaccountId),
            'POST',
            ['BarCodeType' => '123'],
            ['SubAccountID' => $accountId]
        );
    }

    public function unauthorizedCouponAccessShouldNotProceed(\TestSymfonyGuy $I)
    {
        $userId = $I->createAwUser('clp' . StringHandler::getRandomCode(10));
        $couponId = $I->createAwCoupon($userId, 'somecoupon', 'value', 'desc');
        $this->saveWithError(
            $I,
            $this->getCouponUrl($couponId),
            'POST',
            ['BarCodeType' => '123'],
            ['ProviderCouponID' => $couponId]
        );
    }

    public function testSubaccountFormat(\TestSymfonyGuy $I)
    {
        $accountId = $I->createAwAccount($this->userId, null, 'somelogin', null, ['SubAccounts' => 1]);
        $subaccountId = $I->createAwSubAccount($accountId, ['Code' => 'SomeSubaccount', 'DisplayName' => 'some subaccount']);
        $I->createCustomLoyaltyProperty('BarCodeData', 'SomeValue', ['SubAccountID' => $subaccountId]);
        $I->createCustomLoyaltyProperty('BarCodeType', 'SomeType', ['SubAccountID' => $subaccountId]);
        $I->createAccountProperty('BarCode', '012345678912', ['AccountID' => $accountId, 'SubAccountID' => $subaccountId]);
        $I->createAccountProperty('BarCodeType', BAR_CODE_EAN_13, ['AccountID' => $accountId, 'SubAccountID' => $subaccountId]);
        $data = $this->accountSteps->loadData();
        $this->testLoyaltyProgramFormat('account', array_values($data['accounts'])[0]['SubAccountsArray'][0]);
    }

    public function testAccountFormat(\TestSymfonyGuy $I)
    {
        $accountId = $I->createAwAccount($this->userId, null, 'somelogin');
        $I->createCustomLoyaltyProperty('BarCodeData', 'SomeValue', ['AccountID' => $accountId]);
        $I->createCustomLoyaltyProperty('BarCodeType', 'SomeType', ['AccountID' => $accountId]);
        $I->createAccountProperty('BarCode', '012345678912', ['AccountID' => $accountId]);
        $I->createAccountProperty('BarCodeType', BAR_CODE_EAN_13, ['AccountID' => $accountId]);
        $data = $this->accountSteps->loadData();
        $this->testLoyaltyProgramFormat('account', array_values($data['accounts'])[0]);
    }

    public function testCouponFormat(\TestSymfonyGuy $I)
    {
        $couponId = $I->createAwCoupon($this->userId, 'somecoupon', 'value', 'desc');
        $I->createCustomLoyaltyProperty('BarCodeData', 'SomeValue', ['ProviderCouponID' => $couponId]);
        $I->createCustomLoyaltyProperty('BarCodeType', 'SomeType', ['ProviderCouponID' => $couponId]);
        $data = $this->accountSteps->loadData();
        $this->testLoyaltyProgramFormat('coupon', array_values($data['accounts'])[0]);
    }

    protected function testLoyaltyProgramFormat($file, $data)
    {
        $actual = array_merge(
            array_intersect_key($data, array_flip(['BarCode', 'BarCodeCustom', 'BarCodeParsed'])),
            [
                'Blocks' => array_values(array_filter($data['Blocks'], function (array $block) { return $block['Kind'] === 'barcode'; })),
            ]
        );

        assertJsonStringEqualsJsonString(file_get_contents(codecept_data_dir("account/mobile/customLoyaltyProperty/{$file}.json")), json_encode($actual, JSON_PRETTY_PRINT));
    }

    protected function saveWithError(\TestSymfonyGuy $I, $url, $method, array $data, $containerCriteria)
    {
        $method = "send{$method}";

        $I->$method($url, $data);
        $I->dontSeeResponseContainsJson(['success' => true]);

        foreach (['SubAccountID', 'AccountID', 'ProviderCouponID'] as $column) {
            if (!array_key_exists($column, $containerCriteria)) {
                $containerCriteria[$column] = null;
            }
        }

        foreach ($data as $key => $value) {
            $I->dontSeeInDatabase('CustomLoyaltyProperty', array_merge($containerCriteria, ['Name' => $key, 'Value' => $value]));
        }
    }

    protected function saveWithSuccess(\TestSymfonyGuy $I, $url, $method, array $data, $containerCriteria)
    {
        $method = "send{$method}";

        $I->$method($url, $data);
        $I->seeResponseContainsJson(['success' => true]);

        foreach (['SubAccountID', 'AccountID', 'ProviderCouponID'] as $column) {
            if (!array_key_exists($column, $containerCriteria)) {
                $containerCriteria[$column] = null;
            }
        }

        foreach ($data as $key => $value) {
            $I->seeInDatabase('CustomLoyaltyProperty', array_merge($containerCriteria, ['Name' => $key, 'Value' => $value]));
        }
    }

    protected function getAccountUrl($accountId)
    {
        return self::ROUTE_PREFIX . "/account/{$accountId}";
    }

    protected function getCouponUrl($couponId)
    {
        return self::ROUTE_PREFIX . "/coupon/{$couponId}";
    }

    protected function getSubaccountUrl($accountId, $subaccountId)
    {
        return self::ROUTE_PREFIX . "/account/{$accountId}/{$subaccountId}";
    }
}
