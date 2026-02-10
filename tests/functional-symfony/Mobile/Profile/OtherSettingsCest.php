<?php

namespace AwardWallet\Tests\FunctionalSymfony\Mobile\Profile;

use AwardWallet\Tests\FunctionalSymfony\BaseTraitCest;
use AwardWallet\Tests\FunctionalSymfony\Traits\FreeUser;
use AwardWallet\Tests\FunctionalSymfony\Traits\JsonForm;
use AwardWallet\Tests\FunctionalSymfony\Traits\JsonHeaders;
use AwardWallet\Tests\FunctionalSymfony\Traits\LoggedIn;

/**
 * @group frontend-functional
 * @group security
 * @group mobile
 */
class OtherSettingsCest extends BaseTraitCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use FreeUser;
    use LoggedIn;
    use JsonHeaders;
    use JsonForm;

    public const ROUTE = '/m/api/profile/other-settings';

    public function free(\TestSymfonyGuy $I)
    {
        $I->assertFalse($this->user->isAwPlus());
        $I->sendGET(self::ROUTE);

        $I->assertTrue($I->grabDataFromResponseByJsonPath("$.children[?(@.name=='splashAdsDisabled')].disabled")[0]);
        $I->assertTrue($I->grabDataFromResponseByJsonPath("$.children[?(@.name=='linkAdsDisabled')].disabled")[0]);
        $I->assertTrue($I->grabDataFromResponseByJsonPath("$.children[?(@.name=='listAdsDisabled')].disabled")[0]);
        $I->assertTrue($I->grabDataFromResponseByJsonPath("$.children[?(@.name=='isBlogPostAds')].disabled")[0]);

        $I->assertTrue($I->grabDataFromResponseByJsonPath("$.children[?(@.name=='splashAdsDisabled')].value")[0]);
        $I->assertTrue($I->grabDataFromResponseByJsonPath("$.children[?(@.name=='linkAdsDisabled')].value")[0]);
        $I->assertTrue($I->grabDataFromResponseByJsonPath("$.children[?(@.name=='listAdsDisabled')].value")[0]);
        $I->assertTrue($I->grabDataFromResponseByJsonPath("$.children[?(@.name=='isBlogPostAds')].value")[0]);

        $I->sendPUT(self::ROUTE, [
            'splashAdsDisabled' => false,
            'linkAdsDisabled' => false,
            'listAdsDisabled' => false,
            'isBlogPostAds' => false,
            '_token' => $this->grabFormCsrfToken($I),
        ]);
        $I->seeResponseContainsJson(['success' => true]);

        // no disabled field should be changed
        $I->seeInDatabase("Usr", ["UserID" => $this->user->getId(), "SplashAdsDisabled" => 0, "LinkAdsDisabled" => 0, "ListAdsDisabled" => 0, "IsBlogPostAds" => 1]);
    }

    public function freeAllDisabled(\TestSymfonyGuy $I)
    {
        $I->updateInDatabase(
            "Usr",
            [
                "SplashAdsDisabled" => 1,
                "LinkAdsDisabled" => 1,
                "ListAdsDisabled" => 1,
                "IsBlogPostAds" => 0,
            ],
            ["UserID" => $this->user->getId()]);
        $I->assertFalse($this->user->isAwPlus());
        $I->sendGET(self::ROUTE);

        $I->assertTrue($I->grabDataFromResponseByJsonPath("$.children[?(@.name=='splashAdsDisabled')].disabled")[0]);
        $I->assertTrue($I->grabDataFromResponseByJsonPath("$.children[?(@.name=='linkAdsDisabled')].disabled")[0]);
        $I->assertTrue($I->grabDataFromResponseByJsonPath("$.children[?(@.name=='listAdsDisabled')].disabled")[0]);
        $I->assertTrue($I->grabDataFromResponseByJsonPath("$.children[?(@.name=='isBlogPostAds')].disabled")[0]);

        // we should see checked checkboxes because we are not plus
        $I->assertTrue($I->grabDataFromResponseByJsonPath("$.children[?(@.name=='splashAdsDisabled')].value")[0]);
        $I->assertTrue($I->grabDataFromResponseByJsonPath("$.children[?(@.name=='linkAdsDisabled')].value")[0]);
        $I->assertTrue($I->grabDataFromResponseByJsonPath("$.children[?(@.name=='listAdsDisabled')].value")[0]);
        $I->assertTrue($I->grabDataFromResponseByJsonPath("$.children[?(@.name=='isBlogPostAds')].value")[0]);
    }

    public function plus(\TestSymfonyGuy $I)
    {
        $I->updateInDatabase("Usr", ["AccountLevel" => ACCOUNT_LEVEL_AWPLUS], ["UserID" => $this->user->getId()]);
        $I->sendGET(self::ROUTE);

        $I->assertFalse($I->grabDataFromResponseByJsonPath("$.children[?(@.name=='splashAdsDisabled')].disabled")[0]);
        $I->assertFalse($I->grabDataFromResponseByJsonPath("$.children[?(@.name=='linkAdsDisabled')].disabled")[0]);
        $I->assertFalse($I->grabDataFromResponseByJsonPath("$.children[?(@.name=='listAdsDisabled')].disabled")[0]);
        $I->assertFalse($I->grabDataFromResponseByJsonPath("$.children[?(@.name=='isBlogPostAds')].disabled")[0]);

        $I->assertTrue($I->grabDataFromResponseByJsonPath("$.children[?(@.name=='splashAdsDisabled')].value")[0]);
        $I->assertTrue($I->grabDataFromResponseByJsonPath("$.children[?(@.name=='linkAdsDisabled')].value")[0]);
        $I->assertTrue($I->grabDataFromResponseByJsonPath("$.children[?(@.name=='listAdsDisabled')].value")[0]);
        $I->asserttrue($I->grabDataFromResponseByJsonPath("$.children[?(@.name=='isBlogPostAds')].value")[0]);

        $I->sendPUT(self::ROUTE, [
            'splashAdsDisabled' => false,
            'linkAdsDisabled' => false,
            'listAdsDisabled' => false,
            'isBlogPostAds' => false,
            '_token' => $this->grabFormCsrfToken($I),
        ]);
        $I->seeResponseContainsJson(['success' => true]);
        $I->seeInDatabase("Usr", ["UserID" => $this->user->getId(), "SplashAdsDisabled" => 1, "LinkAdsDisabled" => 1, "ListAdsDisabled" => 1, "IsBlogPostAds" => 0]);
    }

    public function plusAllDisabled(\TestSymfonyGuy $I)
    {
        $I->updateInDatabase(
            "Usr",
            [
                "AccountLevel" => ACCOUNT_LEVEL_AWPLUS,
                "SplashAdsDisabled" => 1,
                "LinkAdsDisabled" => 1,
                "ListAdsDisabled" => 1,
                "IsBlogPostAds" => 0,
            ],
            ["UserID" => $this->user->getId()]);
        $I->sendGET(self::ROUTE);

        $I->assertFalse($I->grabDataFromResponseByJsonPath("$.children[?(@.name=='splashAdsDisabled')].disabled")[0]);
        $I->assertFalse($I->grabDataFromResponseByJsonPath("$.children[?(@.name=='linkAdsDisabled')].disabled")[0]);
        $I->assertFalse($I->grabDataFromResponseByJsonPath("$.children[?(@.name=='listAdsDisabled')].disabled")[0]);
        $I->assertFalse($I->grabDataFromResponseByJsonPath("$.children[?(@.name=='isBlogPostAds')].disabled")[0]);

        $I->assertFalse($I->grabDataFromResponseByJsonPath("$.children[?(@.name=='splashAdsDisabled')].value")[0]);
        $I->assertFalse($I->grabDataFromResponseByJsonPath("$.children[?(@.name=='linkAdsDisabled')].value")[0]);
        $I->assertFalse($I->grabDataFromResponseByJsonPath("$.children[?(@.name=='listAdsDisabled')].value")[0]);
        $I->assertFalse($I->grabDataFromResponseByJsonPath("$.children[?(@.name=='isBlogPostAds')].value")[0]);

        $I->sendPUT(self::ROUTE, [
            'splashAdsDisabled' => true,
            'linkAdsDisabled' => true,
            'listAdsDisabled' => true,
            'isBlogPostAds' => true,
            '_token' => $this->grabFormCsrfToken($I),
        ]);
        $I->seeResponseContainsJson(['success' => true]);
        $I->seeInDatabase("Usr", ["UserID" => $this->user->getId(), "SplashAdsDisabled" => 0, "LinkAdsDisabled" => 0, "ListAdsDisabled" => 0, "IsBlogPostAds" => 1]);
    }
}
