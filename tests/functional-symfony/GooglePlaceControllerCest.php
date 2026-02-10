<?php

namespace AwardWallet\Tests\FunctionalSymfony;

use AwardWallet\Common\Geo\Google\GeoCodeResponse;
use AwardWallet\Common\Geo\Google\GoogleApi;
use AwardWallet\Common\Geo\Google\PlaceDetailsResponse;
use AwardWallet\Common\Geo\Google\PlaceSearchResponse;
use AwardWallet\Tests\FunctionalSymfony\Traits\LoggedIn;
use AwardWallet\Tests\FunctionalSymfony\Traits\StaffUser;

class GooglePlaceControllerCest extends BaseTraitCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use StaffUser;
    use LoggedIn;

    public function _before(\TestSymfonyGuy $I)
    {
        parent::_before($I);
        $I->resetLockout('google_request', $I->getClientIp());
    }

    public function hotelAddressSearch(\TestSymfonyGuy $I)
    {
        $I->sendAjaxGetRequest('/google/hotels/moscow');
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'formatted_address' => "Tverskaya St, 26/1, Moscow, Russia, 125009",
            'name' => "Moscow Marriott Grand Hotel",
        ]);
    }

    public function geoCode(\TestSymfonyGuy $I)
    {
        $I->sendAjaxGetRequest('/google/geo_code/Moscow');
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['formatted_address' => "Moscow, Russia"]);
        $I->seeResponseContainsJson([
            'location' => [
                'lat' => 55.755826,
                'lng' => 37.6172999,
            ], ]);
    }

    public function placeDetails(\TestSymfonyGuy $I)
    {
        $I->sendAjaxGetRequest('/google/place_details/ChIJybDUc_xKtUYRTM9XV8zWRD0');
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['formatted_address' => "Moscow, Russia"]);
        $I->seeResponseContainsJson([
            'location' => [
                'lat' => 55.755826,
                'lng' => 37.6172999,
            ], ]);
    }

    public function hotelAddressLockout(\TestSymfonyGuy $I)
    {
        $I->mockService('aw.geo.google_api', $I->stubMakeEmpty(GoogleApi::class, ['placeTextSearch' => new PlaceSearchResponse(PlaceSearchResponse::STATUS_OK)]));

        for ($i = 0; $i < 100; $i++) {
            $I->sendAjaxGetRequest('/google/hotels/moscow');
        }
        $I->seeResponseCodeIs(200);
        // done 100 requests, and now the 101th:
        $I->sendAjaxGetRequest('/google/hotels/moscow');
        $I->seeResponseCodeIs(429);
    }

    public function geoCodeLockout(\TestSymfonyGuy $I)
    {
        $I->mockService('aw.geo.google_api', $I->stubMakeEmpty(GoogleApi::class, ['geoCode' => new GeoCodeResponse(GeoCodeResponse::STATUS_OK)]));

        for ($i = 0; $i < 100; $i++) {
            $I->sendAjaxGetRequest('/google/geo_code/moscow');
        }
        $I->seeResponseCodeIs(200);
        // done 100 requests, and now the 101th:
        $I->sendAjaxGetRequest('/google/hotels/moscow');
        $I->seeResponseCodeIs(429);
    }

    public function placeDetailsLockout(\TestSymfonyGuy $I)
    {
        $I->mockService('aw.geo.google_api', $I->stubMakeEmpty(GoogleApi::class, ['placeDetails' => new PlaceDetailsResponse(PlaceDetailsResponse::STATUS_OK)]));

        for ($i = 0; $i < 100; $i++) {
            $I->sendAjaxGetRequest('/google/place_details/ChIJybDUc_xKtUYRTM9XV8zWRD0');
        }
        $I->seeResponseCodeIs(200);
        // done 100 requests, and now the 101th:
        $I->sendAjaxGetRequest('/google/hotels/ChIJybDUc_xKtUYRTM9XV8zWRD0');
        $I->seeResponseCodeIs(429);
    }
}
