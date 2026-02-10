<?php

namespace AwardWallet\Tests\FunctionalSymfony\Lookup;

/**
 * @group frontend-functional
 */
class MerchantCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function checkDataLess3SymbolsQuery(\TestSymfonyGuy $I)
    {
        $routeIndex = $I->grabService('router')->generate('aw_merchant_lookup');
        $routeData = $I->grabService('router')->generate('aw_merchant_lookup_data');

        $I->amOnPage($routeIndex);

        $I->saveCsrfToken();

        $I->sendPOST($routeData, ['query' => 'we']);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([]);
        $I->seeResponseCodeIs(200);

        $I->sendPOST($routeData, ['query' => 'wegm']);
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
    }
}
