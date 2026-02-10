<?php

namespace AwardWallet\Engine\hhonors\RewardAvailability;

use AwardWallet\Common\Selenium\FingerprintFactory;
use AwardWallet\Common\Selenium\FingerprintRequest;
use AwardWallet\Engine\ProxyList;
use SeleniumFinderRequest;
use AwardWallet\Engine\Settings;

class HotelParser extends \TAccountChecker
{
    use ProxyList;
    use \SeleniumCheckerHelper;

    private $downloadPreview;
    private string $token;
    private array $hotelsIds;
    private string $country;
    private string $referer;

    public static function getRASearchLinks(): array
    {
        return ['https://www.hilton.com/en/search/find-hotels/' => 'search page'];
    }

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->UseSelenium();
        $this->useGoogleChrome(\SeleniumFinderRequest::CHROME_95);

//        $this->seleniumOptions->userAgent = null;

//        $this->disableImages();
        $this->setScreenResolution([1280, 800]);

        $this->http->saveScreenshots = true;
        $array = ['us']; // Массив с кодами стран.
        $this->country = $array[random_int(0, count($array) - 1)]; // Случайный выбор страны для установки proxy.
        //$this->country = 'us';
        $this->setProxyGoProxies(null, $this->country); // Установка proxy из выбранной страны.
        $this->country = strtoupper($this->country);
        $this->logger->info('Страна: ' . $this->country);

        $request = FingerprintRequest::chrome();
        $request->browserVersionMin = \HttpBrowser::BROWSER_VERSION_MIN;
        $request->platform = (random_int(0, 1)) ? 'MacIntel' : 'Win32';
        $fingerprint = $this->services->get(FingerprintFactory::class)->getOne([$request]);

        if (isset($fingerprint)) {
            $this->http->setUserAgent($fingerprint->getUseragent());
            $this->seleniumOptions->userAgent = $fingerprint->getUseragent();
        }

    }

    public function IsLoggedIn()
    {
        return false;
    }

    public function LoadLoginForm()
    {
        return true;
    }

    public function Login()
    {
        return true;
    }

    public function ParseRewardAvailability(array $fields): array
    {
        $this->logger->info("Parse Reward Availability", ['Header' => 2]);
        $this->logger->notice(__METHOD__);

        $checkInStr = date('Y-m-d', $fields['CheckIn']);
        $checkOutStr = date('Y-m-d', $fields['CheckOut']);

        $this->downloadPreview = $fields['DownloadPreview'] ?? false;

        if (!$this->isPreCheck($fields, $checkInStr, $checkOutStr)) {
            return ['hotels' => []];
        }

        $this->http->GetURL('https://www.hilton.com/en');



        $this->token = $this->getToken(
            'https://www.hilton.com/dx-customer/auth/applications/token?appName=dx_shop_search_ap',
            'https://www.hilton.com/en/',
        'window.__ENV.DX_AUTH_API_CUSTOMER_APP_ID');

        // Подготавливаем параметры GET-запроса
        $query = http_build_query([
            'query' => $fields['Destination'],
            'arrivalDate' => $checkInStr,
            'departureDate' => $checkOutStr,
            'flexibleDates' => 'false',
            'numRooms' => $fields['Rooms'],
            'numAdults' => $fields['Adults'],
            'numChildren' => $fields['Kids'],
            'room1ChildAges' => 14,
            'room1AdultAges' => '',
            'redeemPts' => 'true',
            //'specialRateTokens' => '',
            //'sortBy' => 'DISTANCE',
            //'sessionToken' => '9cf0350a-d3d3-4720-983e-c49dc284424a',
        ]);

        $url = "https://www.hilton.com/en/search/?{$query}";
        $this->referer = $url;
        $headers = [
            'Accept' => 'application/json; charset=utf-8',
            'Content-Type' => 'application/json; charset=utf-8',
            'Referer' => '$referrer',
        ];
        $this->http->GetURL($url, $headers);
        $this->saveResponse();


        if (!$this->isMainCheck()) {
            return ['hotels' => []];
        }

        $queryLimit = $this->waitForElement(\WebDriverBy::xpath("//h2[starts-with(normalize-space(),'Showing')]"), 0);

        if (!$queryLimit) {
            throw new \CheckRetryNeededException(5, 0);
        }
        preg_match('/of ([^\&]+) hotels/', $queryLimit->getText(), $matches);
        $queryLimit = (int) $matches[1];

        $hotelsInfo = $this->getHotelsInfo($fields, $checkInStr, $checkOutStr, $queryLimit);

        $this->saveResponse();

        $parsedHotels = $this->parseHotelsInfo($hotelsInfo);

        for ($i = 0; $i < count($parsedHotels); $i++) {
            $rooms = $this->parseRoomsAndRatesForHotel($this->hotelsIds[$i], $fields, $checkInStr, $checkOutStr);
            $parsedHotels[$i]['rooms'] = $rooms;
        }

        return ['hotels' => $parsedHotels];
    }

    private function isPreCheck($fields, $checkInStr, $checkOutStr) {

        if ($fields['Rooms'] > 9) {
            $this->SetWarning('Maximum 9 rooms');

            return false;
        }

        if ($checkInStr == $checkOutStr) {
            $this->SetWarning('You can’t book a day-use room.');

            return false;
        }

        $diffInDays = ($fields['CheckOut'] - $fields['CheckIn']) / (60 * 60 * 24);

        if ($diffInDays > 90) {
            $this->SetWarning('Maximum 90 days for book.');

            return false;
        }

        return true;
    }

    private function isMainCheck()
    {
        if ($this->waitForElement(\WebDriverBy::xpath('
            //h2[contains(text(),"find the page you are looking")]
            | //h2[starts-with(normalize-space(),"Showing")]
            | //div[contains(text(),"entries contained an error")]
            | //h1[contains(text(),"WE\'RE SORRY!")]
        '), 10)) {
            $this->saveResponse();
            if ($this->waitForElement(\WebDriverBy::xpath("//h2[starts-with(normalize-space(),'Showing')]"),
                0)) {
                return true; // no errors
            }
            throw new \CheckException('Invalid search data. Please verify your entries and try again', ACCOUNT_PROVIDER_ERROR);
        }

        if ($err = $this->waitForElement(\WebDriverBy::xpath('//h2[contains(text(),"something went wrong.")]'), 0)) {
            $this->logger->error($err->getText());

            throw new \CheckException($err->getText(), ACCOUNT_PROVIDER_ERROR);
        }

        return true;
    }

    private function getToken($url, $referrer, $apiId): string
    {
        $this->logger->notice(__METHOD__);

        $script = "
            function fetchWithTimeout(url, options, timeout = 10000) {
                const controller = new AbortController();
                const id = setTimeout(() => controller.abort(), timeout);
                return fetch(url, {...options, signal: controller.signal})
                    .finally(() => clearTimeout(id));
            };
            
            async function getToken() {
                try {
                    let response = await fetchWithTimeout('$url', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json; charset=utf-8',
                            'Content-Type': 'application/json; charset=utf-8',
                            'Referer': '$referrer'
                        },
                        body: JSON.stringify({'app_id': $apiId})
                    });
                                
                    let result = await response.json();
                    return JSON.stringify(result);
                }
                catch (err) {
                    return JSON.stringify(err);
                }
            }
            
            return getToken();
        ";


        $this->logger->debug("Execute script:");
        $this->logger->debug($script, ['pre' => true]);

        $jsonStr = $this->driver->executeScript($script);

        $this->saveResponse();

        $tokenData = $this->http->JsonLog($jsonStr, 1);
        if (!isset($tokenData->access_token)) {
            throw new \CheckException('no token', ACCOUNT_ENGINE_ERROR);
        }

        $this->logger->debug(var_export($tokenData, true));
        return $tokenData->token_type . ' ' . $tokenData->access_token;
    }

    private function getMainHotelsInfo(array $fields, $queryLimit = 100): array
    {
        $this->logger->notice(__METHOD__);

        $apiUrl = 'https://www.hilton.com/graphql/customer?operationName=geocode_hotelSummaryOptions&originalOpName=geocode_hotelSummaryOptions&appName=dx_shop_search_app&bl=en';

        $script = "
            function fetchWithTimeout(url, options, timeout = 10000) {
                const controller = new AbortController();
                const id = setTimeout(() => controller.abort(), timeout);
                return fetch(url, {...options, signal: controller.signal})
                    .finally(() => clearTimeout(id));
            };
            
            // функция для получения списка доступных отелей
            async function getHotels() {
                let payload = {
                    'query': 'query geocode_hotelSummaryOptions(\$address: String, \$distanceUnit: HotelDistanceUnit, \$language: String!, \$placeId: String, \$queryLimit: Int!, \$sessionToken: String) {  geocode(    language: \$language    address: \$address    placeId: \$placeId    sessionToken: \$sessionToken  ) {    match {      id      address {        city        country        state        postalCode      }      name      type      geometry {        location {          latitude          longitude        }        bounds {          northeast {            latitude            longitude          }          southwest {            latitude            longitude          }        }      }    }    hotelSummaryOptions(distanceUnit: \$distanceUnit, sortBy: distance) {      bounds {        northeast {          latitude          longitude        }        southwest {          latitude          longitude        }      }      amenities {        id        name        hint      }      amenityCategories {        name        id        amenityIds      }      brands {        code        name      }      hotels(first: \$queryLimit) {        _id: ctyhocn        amenityIds        brandCode        ctyhocn        distance        distanceFmt        facilityOverview {          allowAdultsOnly          homeUrlTemplate        }        name        display {          open          openDate          preOpenMsg          resEnabled          resEnabledDate          treatments        }        contactInfo {          phoneNumber        }        address {          addressLine1          city          country          state        }        localization {          coordinate {            latitude            longitude          }        }        images {          master(ratios: [threeByTwo]) {            altText            ratios {              size              url            }          }        }        tripAdvisorLocationSummary {          numReviews          ratingFmt(decimal: 1)          ratingImageUrl        }        leadRate {          hhonors {            lead {              dailyRmPointsRate              dailyRmPointsRateNumFmt: dailyRmPointsRateFmt(hint: number)              ratePlan {                ratePlanName @toTitleCase                ratePlanDesc              }            }            max {              rateAmount              rateAmountFmt              dailyRmPointsRate              dailyRmPointsRateRoundFmt: dailyRmPointsRateFmt(hint: round)              dailyRmPointsRateNumFmt: dailyRmPointsRateFmt(hint: number)              ratePlan {                ratePlanCode              }            }            min {              rateAmount(decimal: 1)              rateAmountFmt              dailyRmPointsRate              dailyRmPointsRateRoundFmt: dailyRmPointsRateFmt(hint: round)              dailyRmPointsRateNumFmt: dailyRmPointsRateFmt(hint: number)              ratePlan {                ratePlanCode              }            }          }        }      }    }    ctyhocnList: hotelSummaryOptions(distanceUnit: \$distanceUnit, sortBy: distance) {      hotelList: hotels(first: 150) {        ctyhocn      }    }  }  geocodeEn: geocode(    language: \"en\"    address: \$address    placeId: \$placeId    sessionToken: \$sessionToken  ) {    match {      name    }  }}',
                    'operationName': 'geocode_hotelSummaryOptions',
                    'variables': {
                        'address': '$fields[Destination]', // здесь указываем город, для которого ищем отели
                        'language': 'en',
                        'placeId': null,
                        'queryLimit': $queryLimit // здесь указываем сколько отелей должно быть в ответе
                    }
                }
                
                try {
                    let response = await fetchWithTimeout('$apiUrl', {
                        'headers': {
                            'Accept': '*/*',
                            'Authorization': '$this->token',
                            'Content-Type': 'application/json',
                            'Referrer': 'https://www.hilton.com/en/search/',
                        },
                        'body': JSON.stringify(payload),
                        'method': 'POST',
                    });
            
                    let result = await response.json();
                    return JSON.stringify(result.data.geocode.hotelSummaryOptions.hotels);
                }
                catch {
                    return null;
                }
            }

            return getHotels();
        ";

        $this->logger->debug("Execute script:");
        $this->logger->debug($script, ['pre' => true]);

        $jsonStr = $this->driver->executeScript($script);

        $this->saveResponse();

        if (!$jsonStr) {
            return [];
        }

        $mainHotelsInfo = $this->http->JsonLog($jsonStr, 1, true);

        $indexedMainHotelsInfo = [];

        foreach ($mainHotelsInfo as $hotel) {
            $indexedMainHotelsInfo[$hotel['_id']] = $hotel;
        }

        return $indexedMainHotelsInfo;
    }

    private function getPriceHotelsInfo(string $hotelsId, object $browser, array $fields, string $checkInStr, string $checkOutStr): array
    {
        $this->logger->notice(__METHOD__);

        $requestHotelId = substr($hotelsId, 0 , -2);
        $url = "https://www.hilton.com/graphql/customer?appName=dx-res-ui&appVersion=dx-res-ui:469753&operationName=hotel_shopAvailOptions_shopPropAvail&originalOpName=getShopAvail&bl=en&ctyhocn={$requestHotelId}";
        $payload = '{"query":"query hotel_shopAvailOptions_shopPropAvail($arrivalDate: String!, $ctyhocn: String!, $departureDate: String!, $language: String!, $guestLocationCountry: String, $numAdults: Int!, $numChildren: Int!, $numRooms: Int!, $displayCurrency: String, $guestId: BigInt, $specialRates: ShopSpecialRateInput, $rateCategoryTokens: [String], $selectedRoomRateCodes: [ShopRoomRateCodeInput!], $ratePlanCodes: [String], $pnd: String, $offerId: BigInt, $cacheId: String!, $knownGuest: Boolean, $modifyingReservation: Boolean, $currentlySelectedRoomTypeCode: String, $currentlySelectedRatePlanCode: String, $childAges: [Int], $adjoiningRoomStay: Boolean, $programAccountId: BigInt, $roomTypeSortInput: [ShopRoomTypeSortInput!]) {\n  hotel(ctyhocn: $ctyhocn, language: $language) {\n    ctyhocn\n    roomTypes(filter: {accommodationCode_in: [\"STD\", \"EXEC\", \"STE\", \"ACCS\"]}) {\n      roomTypeCode\n      adaAccessibleRoom\n      numBeds\n      roomTypeName\n      roomTypeDesc\n      roomOccupancy: maxOccupancy\n      executive\n      suite\n      code: roomTypeCode\n      name: roomTypeName\n      thumbnail: carousel(first: 1) {\n        _id\n        altText\n        variants {\n          size\n          url\n        }\n      }\n    }\n    shopAvailOptions(input: {offerId: $offerId, pnd: $pnd}) {\n      maxNumChildren\n      altCorporateAccount {\n        corporateId\n        name\n      }\n      contentOffer {\n        name\n      }\n    }\n    shopAvail(\n      cacheId: $cacheId\n      input: {guestLocationCountry: $guestLocationCountry, arrivalDate: $arrivalDate, departureDate: $departureDate, displayCurrency: $displayCurrency, numAdults: $numAdults, numChildren: $numChildren, numRooms: $numRooms, guestId: $guestId, specialRates: $specialRates, rateCategoryTokens: $rateCategoryTokens, selectedRoomRateCodes: $selectedRoomRateCodes, ratePlanCodes: $ratePlanCodes, knownGuest: $knownGuest, modifyingReservation: $modifyingReservation, childAges: $childAges, adjoiningRoomStay: $adjoiningRoomStay, programAccountId: $programAccountId}\n    ) {\n      currentlySelectedRoom: roomTypes(\n        filter: {roomTypeCode: $currentlySelectedRoomTypeCode}\n      ) {\n        adaAccessibleRoom\n        roomTypeCode\n        roomRates(filter: {ratePlanCode: $currentlySelectedRatePlanCode}) {\n          ratePlanCode\n          rateAmount\n          rateAmountFmt(decimal: 0, strategy: ceiling)\n          rateAmountUSD: rateAmount(currencyCode: \"USD\")\n          amountAfterTaxFmt(decimal: 0, strategy: ceiling)\n          fullAmountAfterTax: amountAfterTaxFmt\n          rateChangeIndicator\n          ratePlan {\n            ratePlanName\n            commissionable\n            confidentialRates\n            specialRateType\n            hhonorsMembershipRequired\n            redemptionType\n          }\n          pointDetails {\n            pointsRateFmt\n          }\n        }\n      }\n      statusCode\n      summary {\n        specialRates {\n          specialRateType\n          roomCount\n        }\n        requestedRates {\n          ratePlanCode\n          ratePlanName\n          roomCount\n        }\n      }\n      notifications {\n        subText\n        subType\n        title\n        text\n      }\n      addOnsAvailable\n      currencyCode\n      roomTypes(sort: $roomTypeSortInput) {\n        roomTypeCode\n        adaAccessibleRoom\n        numBeds\n        roomTypeName\n        roomTypeDesc\n        roomOccupancy\n        executive\n        suite\n        code: roomTypeCode\n        name: roomTypeName\n        adjoiningRoom\n        thumbnail: carousel(first: 1) {\n          _id\n          altText\n          variants {\n            size\n            url\n          }\n        }\n        virtualMedia(filter: {assetType_in: [photo_360, model_3d]}) {\n          assetType\n          assetUrl\n        }\n        quickBookRate {\n          cashRatePlan\n          roomTypeCode\n          rateAmount\n          rateAmountUSD: rateAmount(currencyCode: \"USD\")\n          rateChangeIndicator\n          feeTransparencyIndicator\n          cmaTotalPriceIndicator\n          ratePlanCode\n          rateAmountFmt(decimal: 0, strategy: ceiling)\n          roomTypeCode\n          amountAfterTaxFmt(decimal: 0, strategy: ceiling)\n          fullAmountAfterTax: amountAfterTaxFmt\n          ratePlan {\n            commissionable\n            confidentialRates\n            ratePlanName\n            specialRateType\n            hhonorsMembershipRequired\n            redemptionType\n            serviceChargesAndTaxesIncluded\n          }\n          serviceChargeDetails\n          pointDetails(perNight: true) {\n            pointsRate\n            pointsRateFmt\n          }\n        }\n        moreRatesFromRate {\n          rateChangeIndicator\n          feeTransparencyIndicator\n          cmaTotalPriceIndicator\n          roomTypeCode\n          rateAmount\n          rateAmountFmt(decimal: 0, strategy: ceiling)\n          rateAmountUSD: rateAmount(currencyCode: \"USD\")\n          amountAfterTaxFmt(decimal: 0, strategy: ceiling)\n          fullAmountAfterTax: amountAfterTaxFmt\n          serviceChargeDetails\n          ratePlanCode\n          ratePlan {\n            confidentialRates\n            serviceChargesAndTaxesIncluded\n          }\n        }\n        bookNowRate {\n          roomTypeCode\n          rateAmount\n          rateChangeIndicator\n          feeTransparencyIndicator\n          cmaTotalPriceIndicator\n          ratePlanCode\n          rateAmountFmt(decimal: 0, strategy: ceiling)\n          amountAfterTaxFmt(decimal: 0, strategy: ceiling)\n          fullAmountAfterTax: amountAfterTaxFmt\n          roomTypeCode\n          ratePlan {\n            commissionable\n            confidentialRates\n            ratePlanName\n            specialRateType\n            hhonorsMembershipRequired\n            disclaimer {\n              diamond48\n            }\n            serviceChargesAndTaxesIncluded\n          }\n          serviceChargeDetails\n        }\n        redemptionRoomRates(first: 1) {\n          rateChangeIndicator\n          pointDetails(perNight: true) {\n            pointsRate\n            pointsRateFmt\n          }\n          sufficientPoints\n          pamEligibleRoomRate {\n            ratePlan {\n              ratePlanCode\n              rateCategoryToken\n              redemptionType\n            }\n            roomTypeCode\n            sufficientPoints\n          }\n        }\n      }\n      lowestPointsInc\n    }\n  }\n}","operationName":"hotel_shopAvailOptions_shopPropAvail","variables":{"guestLocationCountry":"' . $this->country .'","arrivalDate":"' . $checkInStr . '","departureDate":"' . $checkOutStr . '","numAdults":' . $fields['Adults'] . ',"numChildren":' . $fields['Kids'] . ',"numRooms":' . $fields['Rooms'] . ',"displayCurrency":null,"ctyhocn":"' . $requestHotelId . '","language":"en","guestId":null,"specialRates":{"aaa":false,"governmentMilitary":false,"hhonors":true,"pnd":"","senior":false,"teamMember":false,"owner":false,"ownerHGV":false,"familyAndFriends":false,"travelAgent":false,"smb":false,"specialOffer":false,"specialOfferName":null},"pnd":null,"cacheId":"3ea6ef18-4839-4bc5-9e1f-d4951b961cad","offerId":null,"knownGuest":false,"modifyingReservation":false,"currentlySelectedRoomTypeCode":null,"currentlySelectedRatePlanCode":null,"childAges":null,"adjoiningRoomStay":false,"roomTypeSortInput":[]}}';

        $headers = [
            'Accept' => '*/*',
            'Content-Type' => 'application/json',
            'Referrer' => 'https://www.hilton.com/en/search/',
            'referer' => 'https://www.hilton.com/en/book/reservation/rooms/',
            'Origin' => 'https://www.hilton.com',
        ];


        $browser->PostURL($url, $payload, $headers);

        $reaponse = $browser->JsonLog(null, 1, true);


//
//        $this->saveResponse();
//
//        $priceHotelsInfo = $this->http->JsonLog($jsonStr, 1, true)['data']['shopMultiPropAvail'];
//
//        $indexedPriceHotelsInfo = [];
//
//        foreach ($priceHotelsInfo as $hotel) {
//            $indexedPriceHotelsInfo[$hotel['ctyhocn']] = $hotel;
//        }
//
//        return $indexedPriceHotelsInfo;
        return $reaponse;
    }

    private function getHotelsInfo($fields, $checkInStr, $checkOutStr, $queryLimit = 20): array
    {
        $this->logger->notice(__METHOD__);

        $mainHotelsInfo = $this->getMainHotelsInfo($fields, $queryLimit);

        $priceHotelsInfo = [];
        $this->hotelsIds = array_keys($mainHotelsInfo);
        $hotelIdsChunks = array_keys($mainHotelsInfo);


        $browser = new \HttpBrowser("none", new \CurlDriver());

        $browser->SetProxy("{$this->http->getProxyAddress()}:{$this->http->getProxyPort()}");
        $browser->setProxyAuth($this->http->getProxyLogin(), $this->http->getProxyPassword());
        $browser->setUserAgent($this->http->getDefaultHeader("User-Agent"));

        $this->http->brotherBrowser($browser);
        $cookies = $this->driver->manage()->getCookies();

        foreach ($cookies as $cookie) {
            $browser->setCookie($cookie['name'], $cookie['value'], $cookie['domain'], $cookie['path'],
                $cookie['expiry'] ?? null);
        }


        foreach ($hotelIdsChunks as $hotelId) {
            $priceHotelsInfo = array_merge($priceHotelsInfo, $this->getPriceHotelsInfo($hotelId, $browser, $fields, $checkInStr, $checkOutStr));
        }

        $hotelsInfo = [];

        foreach(array_map(null, $mainHotelsInfo, $priceHotelsInfo) as list($mainHotelInfo, $priceHotelInfo)) {
            $hotelsInfo[] = array_merge($mainHotelInfo, $priceHotelInfo);
        }

        return $hotelsInfo;
    }

    private function parseHotelsInfo($hotelsInfo): array
    {
        $this->logger->notice(__METHOD__);
        $parsedData = [];

        foreach ($hotelsInfo as $hotelInfo) {
            if ($hotelInfo['summary']['status']['type'] !== 'AVAILABLE' || !isset($hotelInfo['summary']['hhonors'])) {
                continue;
            }

            $preview = null;

            try {
                if (isset($hotelInfo['images']['master']['ratios'][0]) && $this->downloadPreview) {
                    $urlImg = $hotelInfo['images']['master']['ratios'][0]['url'] ?? null;
                    $preview = $this->getBase64FromImageUrl($urlImg);
                }
            }
            catch (\Exception $e) {
                $this->logger->error($e->getMessage());
            }

            $addressItemsOrder = ['addressLine1', 'city', 'state', 'country'];
            $address = '';

            foreach ($addressItemsOrder as $addressItem) {
                if (empty($hotelInfo['address'][$addressItem])) {
                    continue;
                }

                if (!empty($address)) {
                    $address = $address . ', ';
                }

                $address = $address . $hotelInfo['address'][$addressItem];
            }

            if ($address === '') {
                $address = null;
            }

            $parsedData[] = [
                'name' => $hotelInfo['name'] ?? null,
                'checkInDate' => date('Y-m-d H:i', $this->AccountFields['RaRequestFields']['CheckIn']),
                'checkOutDate' => date('Y-m-d H:i', $this->AccountFields['RaRequestFields']['CheckOut']),
                'rooms' => [],
                'hotelDescription' => null,
                'numberOfNights' => $hotelInfo['lengthOfStay'] ?? null,
                'pointsPerNight' => $hotelInfo['summary']['hhonors']['dailyRmPointsRate'],
                'fullCashPricePerNight' => $hotelInfo['summary']['lowest']['amountAfterTax'],
                'distance' => $hotelInfo['distanceFmt'] ?? null,
                'rating' => $hotelInfo['tripAdvisorLocationSummary']['ratingFmt'] ?? null,
                'awardCategory' => null,
                'numberOfReviews' => $hotelInfo['tripAdvisorLocationSummary']['numReviews'] ?? null,
                'address' => $address,
                'phone' => $hotelInfo['contactInfo']['phoneNumber'] ?? null,
                'url' => $hotelInfo['facilityOverview']['homeUrlTemplate'] ?? null,
                'preview' => $preview ?? null,
            ];
        }


        return $parsedData;
    }

    private function getHotelRoomsInfo(string $hotelId, array $fields, string $checkInStr, string $checkOutStr): array
    {
        $this->logger->notice(__METHOD__);

        $smallHotelId = substr($hotelId, 0, 5);

        $script = "
            function fetchWithTimeout(url, options, timeout = 10000) {
                const controller = new AbortController();
                const id = setTimeout(() => controller.abort(), timeout);
                return fetch(url, {...options, signal: controller.signal})
                    .finally(() => clearTimeout(id));
            };
            
            // функция для получения ценовой информации для списка отелей
            async function getHotelRoomsInfo() {
                let payload = {
                    'query': 'query hotel_shopAvailOptions_shopPropAvail(\$arrivalDate: String!, \$ctyhocn: String!, \$departureDate: String!, \$language: String!, \$guestLocationCountry: String, \$numAdults: Int!, \$numChildren: Int!, \$numRooms: Int!, \$displayCurrency: String, \$guestId: BigInt, \$specialRates: ShopSpecialRateInput, \$rateCategoryTokens: [String], \$selectedRoomRateCodes: [ShopRoomRateCodeInput!], \$ratePlanCodes: [String], \$pnd: String, \$offerId: BigInt, \$cacheId: String!, \$knownGuest: Boolean, \$modifyingReservation: Boolean, \$currentlySelectedRoomTypeCode: String, \$currentlySelectedRatePlanCode: String, \$childAges: [Int], \$adjoiningRoomStay: Boolean, \$programAccountId: BigInt, \$roomTypeSortInput: [ShopRoomTypeSortInput!]) {\\n  hotel(ctyhocn: \$ctyhocn, language: \$language) {\\n    ctyhocn\\n    shopAvailOptions(input: {offerId: \$offerId, pnd: \$pnd}) {\\n      maxNumChildren\\n      altCorporateAccount {\\n        corporateId\\n        name\\n      }\\n      contentOffer {\\n        name\\n      }\\n    }\\n    shopAvail(\\n      cacheId: \$cacheId\\n      input: {guestLocationCountry: \$guestLocationCountry, arrivalDate: \$arrivalDate, departureDate: \$departureDate, displayCurrency: \$displayCurrency, numAdults: \$numAdults, numChildren: \$numChildren, numRooms: \$numRooms, guestId: \$guestId, specialRates: \$specialRates, rateCategoryTokens: \$rateCategoryTokens, selectedRoomRateCodes: \$selectedRoomRateCodes, ratePlanCodes: \$ratePlanCodes, knownGuest: \$knownGuest, modifyingReservation: \$modifyingReservation, childAges: \$childAges, adjoiningRoomStay: \$adjoiningRoomStay, programAccountId: \$programAccountId}\\n    ) {\\n      currentlySelectedRoom: roomTypes(\\n        filter: {roomTypeCode: \$currentlySelectedRoomTypeCode}\\n      ) {\\n        adaAccessibleRoom\\n        roomTypeCode\\n        roomRates(filter: {ratePlanCode: \$currentlySelectedRatePlanCode}) {\\n          ratePlanCode\\n          rateAmount\\n          rateAmountFmt(decimal: 0, strategy: ceiling)\\n          rateAmountUSD: rateAmount(currencyCode: \"USD\")\\n          amountAfterTaxFmt(decimal: 0, strategy: ceiling)\\n          fullAmountAfterTax: amountAfterTaxFmt\\n          rateChangeIndicator\\n          ratePlan {\\n            ratePlanName\\n            commissionable\\n            confidentialRates\\n            specialRateType\\n            hhonorsMembershipRequired\\n            redemptionType\\n          }\\n          pointDetails {\\n            pointsRateFmt\\n          }\\n        }\\n      }\\n      statusCode\\n      summary {\\n        specialRates {\\n          specialRateType\\n          roomCount\\n        }\\n        requestedRates {\\n          ratePlanCode\\n          ratePlanName\\n          roomCount\\n        }\\n      }\\n      notifications {\\n        subText\\n        subType\\n        title\\n        text\\n      }\\n      addOnsAvailable\\n      currencyCode\\n      roomTypes(sort: \$roomTypeSortInput) {\\n        roomTypeCode\\n        adaAccessibleRoom\\n        numBeds\\n        roomTypeName\\n        roomTypeDesc\\n        roomOccupancy\\n        executive\\n        suite\\n        code: roomTypeCode\\n        name: roomTypeName\\n        adjoiningRoom\\n        thumbnail: carousel(first: 1) {\\n          _id\\n          altText\\n          variants {\\n            size\\n            url\\n          }\\n        }\\n        quickBookRate {\\n          cashRatePlan\\n          roomTypeCode\\n          rateAmount\\n          rateAmountUSD: rateAmount(currencyCode: \"USD\")\\n          rateChangeIndicator\\n          feeTransparencyIndicator\\n          cmaTotalPriceIndicator\\n          ratePlanCode\\n          rateAmountFmt(decimal: 0, strategy: ceiling)\\n          roomTypeCode\\n          amountAfterTaxFmt(decimal: 0, strategy: ceiling)\\n          fullAmountAfterTax: amountAfterTaxFmt\\n          ratePlan {\\n            commissionable\\n            confidentialRates\\n            ratePlanName\\n            specialRateType\\n            hhonorsMembershipRequired\\n            redemptionType\\n            serviceChargesAndTaxesIncluded\\n          }\\n          serviceChargeDetails\\n          pointDetails(perNight: true) {\\n            pointsRate\\n            pointsRateFmt\\n          }\\n        }\\n        moreRatesFromRate {\\n          rateChangeIndicator\\n          feeTransparencyIndicator\\n          cmaTotalPriceIndicator\\n          roomTypeCode\\n          rateAmount\\n          rateAmountFmt(decimal: 0, strategy: ceiling)\\n          rateAmountUSD: rateAmount(currencyCode: \"USD\")\\n          amountAfterTaxFmt(decimal: 0, strategy: ceiling)\\n          fullAmountAfterTax: amountAfterTaxFmt\\n          serviceChargeDetails\\n          ratePlanCode\\n          ratePlan {\\n            confidentialRates\\n            serviceChargesAndTaxesIncluded\\n          }\\n        }\\n        bookNowRate {\\n          roomTypeCode\\n          rateAmount\\n          rateChangeIndicator\\n          feeTransparencyIndicator\\n          cmaTotalPriceIndicator\\n          ratePlanCode\\n          rateAmountFmt(decimal: 0, strategy: ceiling)\\n          amountAfterTaxFmt(decimal: 0, strategy: ceiling)\\n          fullAmountAfterTax: amountAfterTaxFmt\\n          roomTypeCode\\n          ratePlan {\\n            commissionable\\n            confidentialRates\\n            ratePlanName\\n            specialRateType\\n            hhonorsMembershipRequired\\n            disclaimer {\\n              diamond48\\n            }\\n            serviceChargesAndTaxesIncluded\\n          }\\n          serviceChargeDetails\\n        }\\n        redemptionRoomRates(first: 1) {\\n          rateChangeIndicator\\n          pointDetails(perNight: true) {\\n            pointsRate\\n            pointsRateFmt\\n          }\\n          sufficientPoints\\n          pamEligibleRoomRate {\\n            ratePlan {\\n              ratePlanCode\\n              rateCategoryToken\\n              redemptionType\\n            }\\n            roomTypeCode\\n            sufficientPoints\\n          }\\n        }\\n      }\\n      lowestPointsInc\\n    }\\n  }\\n}',
                    'operationName': 'hotel_shopAvailOptions_shopPropAvail',
                    'variables': {
                        'guestLocationCountry': '$this->country',
                        'arrivalDate': '$checkInStr',
                        'departureDate': '$checkOutStr',
                        'numAdults': $fields[Adults],
                        'numChildren': $fields[Kids],
                        'numRooms': $fields[Rooms],
                        'displayCurrency': null,
                        'ctyhocn': '$smallHotelId',
                        'language': 'en',
                        'guestId': null,
                        'specialRates': {
                            'aaa': false,
                            'aarp': false,
                            'governmentMilitary': false,
                            'hhonors': true,
                            'pnd': '',
                            'senior': false,
                            'teamMember': false,
                            'owner': false,
                            'ownerHGV': false,
                            'familyAndFriends': false,
                            'travelAgent': false,
                            'smb': false,
                            'specialOffer': false,
                            'specialOfferName': null
                        },
                        'pnd': null,
                        'cacheId': 'fb3231c2-4eb0-401b-99d4-1a3a692c2517',
                        'offerId': null,
                        'knownGuest': false,
                        'modifyingReservation': false,
                        'currentlySelectedRoomTypeCode': null,
                        'currentlySelectedRatePlanCode': null,
                        'childAges': null,
                        'adjoiningRoomStay': false,
                        'roomTypeSortInput': []
                    }
                }
            
                let response = await fetchWithTimeout('https://www.hilton.com/graphql/customer?appName=dx-res-ui&operationName=hotel_shopAvailOptions_shopPropAvail&originalOpName=getShopAvail&bl=en&ctyhocn=$smallHotelId', {
                    'headers': {
                        'Accept': '*/*',
                        'Authorization': '$this->token',
                        'Content-Type': 'application/json',
                        'Referrer': 'https://www.hilton.com/en/book/reservation/rooms/',
                    },
                    'body': JSON.stringify(payload),
                    'method': 'POST',
                });
            
                let result = await response.json();
                return JSON.stringify(result);
            }

            return getHotelRoomsInfo();
        ";

        $this->logger->debug("Execute script:");
        $this->logger->debug($script, ['pre' => true]);

        $jsonStr = $this->driver->executeScript($script);

        return $this->http->JsonLog($jsonStr, 1, true)['data']['hotel']['shopAvail']['roomTypes'];
    }

    private function getRoomRatesInfo(string $hotelId, string $roomTypeCode, array $fields, string $checkInStr, string $checkOutStr): array
    {
        $this->logger->notice(__METHOD__);

        $smallHotelId = substr($hotelId, 0, 5);

        $script = "
            function fetchWithTimeout(url, options, timeout = 10000) {
                const controller = new AbortController();
                const id = setTimeout(() => controller.abort(), timeout);
                return fetch(url, {...options, signal: controller.signal})
                    .finally(() => clearTimeout(id));
            };
            
            // функция для получения ценовой информации для списка отелей
            async function getRoomRatesInfo() {
                let payload = {
                    'query': 'query hotel_shopAvailOptions_shopPropAvail(\$arrivalDate: String!, \$ctyhocn: String!, \$departureDate: String!, \$language: String!, \$guestLocationCountry: String, \$numAdults: Int!, \$numChildren: Int!, \$numRooms: Int!, \$displayCurrency: String, \$guestId: BigInt, \$specialRates: ShopSpecialRateInput, \$rateCategoryTokens: [String], \$selectedRoomRateCodes: [ShopRoomRateCodeInput!], \$ratePlanCodes: [String], \$pnd: String, \$offerId: BigInt, \$cacheId: String!, \$knownGuest: Boolean, \$selectedRoomTypeCode: String, \$childAges: [Int], \$adjoiningRoomStay: Boolean, \$modifyingReservation: Boolean, \$programAccountId: BigInt) {\\n  hotel(ctyhocn: \$ctyhocn, language: \$language) {\\n    ctyhocn\\n    shopAvailOptions(input: {offerId: \$offerId, pnd: \$pnd}) {\\n      maxNumChildren\\n      altCorporateAccount {\\n        corporateId\\n        name\\n      }\\n      contentOffer {\\n        name\\n      }\\n    }\\n    shopAvail(\\n      cacheId: \$cacheId\\n      input: {guestLocationCountry: \$guestLocationCountry, arrivalDate: \$arrivalDate, departureDate: \$departureDate, displayCurrency: \$displayCurrency, numAdults: \$numAdults, numChildren: \$numChildren, numRooms: \$numRooms, guestId: \$guestId, specialRates: \$specialRates, rateCategoryTokens: \$rateCategoryTokens, selectedRoomRateCodes: \$selectedRoomRateCodes, ratePlanCodes: \$ratePlanCodes, knownGuest: \$knownGuest, childAges: \$childAges, adjoiningRoomStay: \$adjoiningRoomStay, modifyingReservation: \$modifyingReservation, programAccountId: \$programAccountId}\\n    ) {\\n      statusCode\\n      addOnsAvailable\\n      summary {\\n        specialRates {\\n          specialRateType\\n          roomCount\\n        }\\n        requestedRates {\\n          ratePlanCode\\n          ratePlanName\\n          roomCount\\n        }\\n      }\\n      notifications {\\n        subText\\n        subType\\n        title\\n        text\\n      }\\n      currencyCode\\n      roomTypes(filter: {roomTypeCode: \$selectedRoomTypeCode}) {\\n        roomTypeCode\\n        adaAccessibleRoom\\n        adjoiningRoom\\n        numBeds\\n        roomTypeName\\n        roomTypeDesc\\n        roomOccupancy\\n        executive\\n        suite\\n        code: roomTypeCode\\n        name: roomTypeName\\n        thumbnail: carousel(first: 1) {\\n          _id\\n          altText\\n          variants {\\n            size\\n            url\\n          }\\n        }\\n        quickBookRate {\\n          ratePlan {\\n            specialRateType\\n            serviceChargesAndTaxesIncluded\\n          }\\n        }\\n        roomOnlyRates {\\n          roomTypeCode\\n          ratePlanCode\\n          rateAmount\\n          rateAmountFmt(decimal: 0, strategy: ceiling)\\n          rateAmountUSD: rateAmount(currencyCode: \"USD\")\\n          amountAfterTaxFmt(decimal: 0, strategy: ceiling)\\n          fullAmountAfterTax: amountAfterTaxFmt\\n          rateChangeIndicator\\n          feeTransparencyIndicator\\n          cmaTotalPriceIndicator\\n          guarantee {\\n            guarPolicyCode\\n            cxlPolicyCode\\n          }\\n          ratePlan {\\n            attributes\\n            commissionable\\n            confidentialRates\\n            ratePlanName\\n            ratePlanDesc\\n            ratePlanCode\\n            hhonorsMembershipRequired\\n            advancePurchase\\n            serviceChargesAndTaxesIncluded\\n          }\\n          hhonorsDiscountRate {\\n            rateChangeIndicator\\n            ratePlanCode\\n            roomTypeCode\\n            rateAmount\\n            rateAmountFmt(decimal: 0, strategy: ceiling)\\n            rateAmountUSD: rateAmount(currencyCode: \"USD\")\\n            amountAfterTaxFmt(decimal: 0, strategy: ceiling)\\n            fullAmountAfterTax: amountAfterTaxFmt\\n            guarantee {\\n              guarPolicyCode\\n              cxlPolicyCode\\n            }\\n            ratePlan {\\n              attributes\\n              commissionable\\n              confidentialRates\\n              ratePlanName\\n              ratePlanDesc\\n              ratePlanCode\\n              advancePurchase\\n              serviceChargesAndTaxesIncluded\\n            }\\n          }\\n          serviceChargeDesc: serviceChargeDetails\\n        }\\n        requestedRoomRates {\\n          ratePlanCode\\n          rateAmount\\n          rateAmountFmt(decimal: 0, strategy: ceiling)\\n          rateAmountUSD: rateAmount(currencyCode: \"USD\")\\n          amountAfterTaxFmt(decimal: 0, strategy: ceiling)\\n          fullAmountAfterTax: amountAfterTaxFmt\\n          rateChangeIndicator\\n          feeTransparencyIndicator\\n          cmaTotalPriceIndicator\\n          ratePlan {\\n            attributes\\n            commissionable\\n            confidentialRates\\n            ratePlanName\\n            ratePlanDesc\\n            hhonorsMembershipRequired\\n            serviceChargesAndTaxesIncluded\\n          }\\n          serviceChargeDesc: serviceChargeDetails\\n        }\\n        specialRoomRates {\\n          ratePlanCode\\n          rateAmount\\n          rateAmountFmt(decimal: 0, strategy: ceiling)\\n          rateAmountUSD: rateAmount(currencyCode: \"USD\")\\n          amountAfterTaxFmt(decimal: 0, strategy: ceiling)\\n          fullAmountAfterTax: amountAfterTaxFmt\\n          rateChangeIndicator\\n          feeTransparencyIndicator\\n          cmaTotalPriceIndicator\\n          ratePlan {\\n            attributes\\n            commissionable\\n            confidentialRates\\n            ratePlanName\\n            ratePlanDesc\\n            hhonorsMembershipRequired\\n            serviceChargesAndTaxesIncluded\\n          }\\n          serviceChargeDesc: serviceChargeDetails\\n        }\\n        packageRates {\\n          roomTypeCode\\n          rateAmount\\n          rateAmountFmt(decimal: 0, strategy: ceiling)\\n          rateAmountUSD: rateAmount(currencyCode: \"USD\")\\n          amountAfterTaxFmt(decimal: 0, strategy: ceiling)\\n          fullAmountAfterTax: amountAfterTaxFmt\\n          rateChangeIndicator\\n          feeTransparencyIndicator\\n          cmaTotalPriceIndicator\\n          ratePlanCode\\n          ratePlan {\\n            attributes\\n            commissionable\\n            confidentialRates\\n            ratePlanName\\n            ratePlanDesc\\n            ratePlanCode\\n            hhonorsMembershipRequired\\n            serviceChargesAndTaxesIncluded\\n          }\\n          guarantee {\\n            guarPolicyCode\\n            cxlPolicyCode\\n          }\\n          serviceChargeDesc: serviceChargeDetails\\n        }\\n        redemptionRoomRates(first: 1) {\\n          cashRatePlan\\n          rateChangeIndicator\\n          pointDetails(perNight: true) {\\n            effectiveDateFmt(format: \"medium\", language: \$language)\\n            effectiveDateFmtAda: effectiveDateFmt(format: \"long\", language: \$language)\\n            pointsRate\\n            pointsRateFmt\\n          }\\n          sufficientPoints\\n          pamEligibleRoomRate {\\n            ratePlan {\\n              ratePlanCode\\n              rateCategoryToken\\n            }\\n            roomTypeCode\\n          }\\n          roomTypeCode\\n          ratePlan {\\n            ratePlanDesc\\n            ratePlanName\\n            redemptionType\\n          }\\n          ratePlanCode\\n          totalCostPoints\\n          totalCostPointsFmt\\n        }\\n      }\\n      lowestPointsInc\\n    }\\n  }\\n}',
                    'operationName': 'hotel_shopAvailOptions_shopPropAvail',
                    'variables': {
                        'guestLocationCountry': '$this->country',
                        'arrivalDate': '$checkInStr',
                        'departureDate': '$checkOutStr',
                        'numAdults': $fields[Adults],
                        'numChildren': $fields[Kids],
                        'numRooms': $fields[Rooms],
                        'displayCurrency': null,
                        'ctyhocn': '$smallHotelId',
                        'language': 'en',
                        'guestId': null,
                        'specialRates': {
                            'aaa': false,
                            'aarp': false,
                            'governmentMilitary': false,
                            'hhonors': true,
                            'pnd': '',
                            'senior': false,
                            'teamMember': false,
                            'owner': false,
                            'ownerHGV': false,
                            'familyAndFriends': false,
                            'travelAgent': false,
                            'smb': false,
                            'specialOffer': false,
                            'specialOfferName': null
                        },
                        'pnd': null,
                        'cacheId': '7c94a02e-6700-438e-8cc7-53c41219178b',
                        'offerId': null,
                        'knownGuest': false,
                        'modifyingReservation': false,
                        'currentlySelectedRoomTypeCode': null,
                        'currentlySelectedRatePlanCode': null,
                        'childAges': null,
                        'adjoiningRoomStay': false,
                        'selectedRoomTypeCode': '$roomTypeCode'
                    }
                }
            
                let response = await fetchWithTimeout('https://www.hilton.com/graphql/customer?appName=dx-res-ui&operationName=hotel_shopAvailOptions_shopPropAvail&originalOpName=getRoomRates&bl=en&ctyhocn=$smallHotelId', {
                    'headers': {
                        'Accept': '*/*',
                        'Authorization': '$this->token',
                        'Content-Type': 'application/json',
                        'Referrer': 'https://www.hilton.com/en/book/reservation/rates/',
                    },
                    'body': JSON.stringify(payload),
                    'method': 'POST',
                });
            
                let result = await response.json();
                return JSON.stringify(result);
            }

            return getRoomRatesInfo();
        ";

        $this->logger->debug("Execute script:");
        $this->logger->debug($script, ['pre' => true]);

        $jsonStr = $this->driver->executeScript($script);

        return $this->http->JsonLog($jsonStr, 1, true)['data']['hotel']['shopAvail']['roomTypes'][0]['redemptionRoomRates'];
    }

    private function parseRoomsAndRatesForHotel(string $hotelId, $fields, $checkInStr, $checkOutStr): array
    {
        $this->logger->notice(__METHOD__);

        $hotelRoomsInfo = $this->getHotelRoomsInfo($hotelId, $fields, $checkInStr, $checkOutStr);
        $rooms = [];

        foreach ($hotelRoomsInfo as $hotelRoomInfo) {

            $description = strip_tags($hotelRoomInfo['roomTypeDesc']);
            $description = preg_replace('/\s+/', ' ', $description);

            $room = [
                'type' => $hotelRoomInfo['suite'] === true ? 'suite' : 'room',
                'name' => $hotelRoomInfo['roomTypeName'],
                'description' => $description,
            ];

            $roomRatesInfo = $this->getRoomRatesInfo($hotelId, $hotelRoomInfo['roomTypeCode'], $fields, $checkInStr, $checkOutStr);
            $rates = [];

            foreach ($roomRatesInfo as $roomRateInfo) {
                $rate = [
                    'name' => $roomRateInfo['ratePlan']['ratePlanName'],
                    'description' => $roomRateInfo['ratePlan']['ratePlanDesc'],
                    'pointsPerNight' => round($roomRateInfo['totalCostPoints'] / count($roomRateInfo['pointDetails']), 2),
                    'cashPerNight' => null,
                ];

                $rates[] = $rate;
            }



            $room['rates'] = $rates;
            $rooms[] = $room;
        }

        return $rooms;
    }

    private function getBase64FromImageUrl(?string $url): ?string
    {
        if (!$url) {
            return null;
        }
        $file = $this->http->DownloadFile($url);
        $imageSize = getimagesize($file);
        $imageData = base64_encode(file_get_contents($file));

        if (!empty($imageSize)) {
            $this->logger->debug("<img src='data:{$imageSize['mime']};base64,{$imageData}' {$imageSize[3]} />",
                ['HtmlEncode' => false]);
        }

        return $imageData;
    }
}