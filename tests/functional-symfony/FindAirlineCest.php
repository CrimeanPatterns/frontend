<?php

namespace AwardWallet\Tests\FunctionalSymfony;

use AwardWallet\Tests\FunctionalSymfony\Traits\LoggedIn;
use AwardWallet\Tests\FunctionalSymfony\Traits\StaffUser;

class FindAirlineCest extends BaseTraitCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use StaffUser;
    use LoggedIn;

    public function findResults(\TestSymfonyGuy $I)
    {
        $I->wantTo('Find airlines');

        $airline1Response = [
            "name" => "American Airlines",
            "code" => "AA",
        ];
        $airline2Response = [
            "name" => "Caribbean Airlines",
            "code" => "BW",
        ];

        $I->sendAjaxGetRequest('/airline/find/AA');
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson($airline1Response);
        $I->dontSeeResponseContainsJson($airline2Response);

        $I->sendAjaxGetRequest('/airline/find/bb');
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->dontSeeResponseContainsJson($airline1Response);
        $I->seeResponseContainsJson($airline2Response);
    }

    public function FindNoResults(\TestSymfonyGuy $I)
    {
        $I->wantTo('Find no airlines');

        $I->sendAjaxGetRequest('/airline/find/NON EXISTENT AIRLINE');
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([]);
    }
}
