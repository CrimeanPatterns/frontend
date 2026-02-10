<?php

namespace AwardWallet\Tests\FunctionalSymfony;

use AwardWallet\Tests\FunctionalSymfony\Traits\LoggedIn;
use AwardWallet\Tests\FunctionalSymfony\Traits\StaffUser;

class FindAirportCest extends BaseTraitCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use StaffUser;
    use LoggedIn;

    public function findResults(\TestSymfonyGuy $I)
    {
        $I->wantTo('Find airports');

        $airport1Response = [
            "aircode" => "AAA",
            "airname" => "Anaa Airport",
        ];
        $airport2Response = [
            "aircode" => "ALA",
            "airname" => "Almaty Airport",
        ];
        $airport3Response = [
            "aircode" => "AAB",
            "airname" => "Arrabury Airport",
        ];

        $I->sendAjaxGetRequest('/airport/find/aaa');
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson($airport1Response);
        $I->seeResponseContainsJson($airport2Response);
        $I->dontSeeResponseContainsJson($airport3Response);

        $I->sendAjaxGetRequest('/airport/find/AAB');
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->dontSeeResponseContainsJson($airport1Response);
        $I->dontSeeResponseContainsJson($airport2Response);
        $I->seeResponseContainsJson($airport3Response);
    }

    public function findNoResults(\TestSymfonyGuy $I)
    {
        $I->wantTo('Find no airports');

        $I->sendAjaxGetRequest('/airport/find/NON EXISTENT AIRPORT');
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->cantSeeResponseJsonMatchesJsonPath('$[0]');
    }

    public function findOrder(\TestSymfonyGuy $I)
    {
        $I->wantTo('Test find result order');

        $airport1Response = [
            "aircode" => "AIR",
            "airname" => "Aripuana Airport",
        ];

        $I->sendAjaxGetRequest('/airport/find/AIR');
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson($airport1Response);
        $I->assertSame([$airport1Response], $I->grabDataFromResponseByJsonPath('$[0]'));
    }
}
