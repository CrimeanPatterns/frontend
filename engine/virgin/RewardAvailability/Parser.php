<?php

namespace AwardWallet\Engine\virgin\RewardAvailability;

use AwardWallet\Common\Selenium\FingerprintFactory;
use AwardWallet\Common\Selenium\FingerprintRequest;
use AwardWallet\Engine\ProxyList;
use CheckRetryNeededException;
use Facebook\WebDriver\Exception\InvalidSelectorException;
use SeleniumFinderRequest;

class Parser extends \TAccountChecker
{
    // parser is almost the same as delta
    use \SeleniumCheckerHelper;
    use \PriceTools;
    use ProxyList;

    private const ATTEMPTS_CNT = 4;
    private const BROWSER_STATISTIC_KEY = 'ra_virgin_statistBr2';
    private $supportedCurrencies = ['USD'];

    private $cacheKey;
    private $airportDetails = [];
    private $config;

    public static function GetAccountChecker($accountInfo)
    {
//        $debugMode = $accountInfo['DebugState'] ?? false;

//        if ($debugMode) {
//            require_once __DIR__ . "/ParserOld.php";
//
//            return new ParserOld();
//        }

        return new static();
    }

    public static function getRASearchLinks(): array
    {
        return ['https://www.virginatlantic.com/us/en' => 'search page'];
    }

    public function getRewardAvailabilitySettings()
    {
        return [
            'supportedCurrencies'      => $this->supportedCurrencies,
            'supportedDateFlexibility' => 0,
            'defaultCurrency'          => 'USD',
            'priceCalendarCabins'      => ["firstClass", "business", "premiumEconomy", "economy", "unknown"],
        ];
    }

    public function InitBrowser()
    {
        \TAccountChecker::InitBrowser();
        $this->UseSelenium();
        $this->KeepState = false;
        $this->http->setHttp2(true);
        $this->http->setDefaultHeader("Upgrade-Insecure-Requests", "1");
        $this->http->setDefaultHeader("Connection", "keep-alive");
        $this->http->setDefaultHeader('Accept-Encoding', 'gzip, deflate, br');

        $this->debugMode = $this->AccountFields['DebugState'] ?? false;

        $useMac = 0;

        switch (rand(2, 3)) {
            case 0:
                $this->useChromePuppeteer(SeleniumFinderRequest::CHROME_PUPPETEER_103);
                $request = FingerprintRequest::chrome();
                $this->config = 'chr_ppt_103';
                $useMac = 1;

                break;

            case 1:
                $this->useGoogleChrome(SeleniumFinderRequest::CHROME_95);
                $request = FingerprintRequest::chrome();
                $this->config = 'chr_95';
//                $useMac = 1;

                break;

            case 2:
                $this->useFirefox(SeleniumFinderRequest::FIREFOX_59);
                $request = FingerprintRequest::firefox();
                $this->config = 'ff_59';

                break;

            default:
                $this->useGoogleChrome(SeleniumFinderRequest::CHROME_94);
                $request = FingerprintRequest::chrome();
                $this->config = 'chr_94';
//                $useMac = rand(0, 1);

                break;
        }

        $this->seleniumOptions->addHideSeleniumExtension = false;
//        $this->seleniumOptions->addPuppeteerStealthExtension = true;
        $this->usePacFile(false);
//        $this->useCache();

        $this->http->saveScreenshots = true;
        $array = ['de', 'us', 'ca', 'fi', 'au', 'fr'];
        $targeting = $array[array_rand($array)];
        $this->setProxyGoProxies(null, $targeting);

        if ($useMac == 1) {
            $this->http->setUserAgent(null);
            $this->seleniumRequest->setOs(\SeleniumFinderRequest::OS_MAC);
        } else {
            $request->browserVersionMin = \HttpBrowser::BROWSER_VERSION_MIN;
            $request->platform = (random_int(0, 1)) ? 'MacIntel' : 'Win32';
            $fingerprint = $this->services->get(FingerprintFactory::class)->getOne([$request]);

            if (isset($fingerprint)) {
                $this->seleniumOptions->fingerprint = $fingerprint->getFingerprint();
                $this->http->setUserAgent($fingerprint->getUseragent());
                $this->seleniumOptions->userAgent = $fingerprint->getUseragent();
            } else {
                $this->http->setRandomUserAgent(null, false, true, false, false, false);
            }
        }

        $resolutions = [
            [1360, 768],
            [1366, 768],
        ];
        $this->setScreenResolution($resolutions[array_rand($resolutions)]);
        //TODO try off HotSession
        $this->seleniumRequest->setHotSessionPool(self::class, $this->AccountFields['ProviderCode']);
    }

    public function IsLoggedIn()
    {
        return false;
    }

    public function LoadLoginForm()
    {
        $this->http->removeCookies();
        $this->cacheKey = $this->getUuid();

        return true;
    }

    public function Login()
    {
        return true;
    }

    public function ParseCalendar(array $fields)
    {
        $this->logger->info("Parse Calendar", ['Header' => 2]);
        $this->logger->debug('Params: ' . var_export($fields, true));

        if ($fields['DepDate'] > strtotime('+331 day')) {
            $this->SetWarning('Ah - something\'s not right here. We can only show you flights up to 331 days in advance - can you also check the return date is after the departure date');

            return [];
        }

        if (!$this->validRouteAll($fields)) {
            return ['fares' => []];
        }

        $payload = $this->getPayloadFlexDate($fields);
        $this->loadStartPage($fields);

        try {
            $this->loadPayload($payload);
            $response = $this->searchXMLHttpFlexDate($payload);

            if (strpos($response, 'Access Denied') !== false) {
                $this->loggerStateBrowser();

                throw new CheckRetryNeededException(5, 0);
            }

            if (is_string($response)) {
                $response = $this->http->JsonLog($response);
            }
        } catch (InvalidSelectorException | \InvalidSelectorException
        | \UnexpectedResponseException | \WebDriverCurlException $e) {
            $this->logger->error($e->getMessage());

            $this->loggerStateBrowser();

            throw new CheckRetryNeededException(5, 0);
        }
//
//            if (($pl = $this->getPayloadFlexDate($fields, $brandID))) {
//        //                        $this->loadPayload($selenium, $pl);
//                $this->flexDate = $this->searchXMLHttpFlexDate($selenium, $pl);
//            }

        return ["fares" => $this->parseCalendarData($response)];
    }

    public function ParseRewardAvailability(array $fields)
    {
        $this->logger->info("Parse Reward Availability", ['Header' => 2]);
        $this->logger->debug('Params: ' . var_export($fields, true));

        if ($fields['Adults'] > 9) {
            $this->logger->info('Error in params');
            $this->logger->error("It's too much travellers");

            return ['routes' => []];
        }

        if ($fields['DepDate'] > strtotime('+331 day')) {
            $this->SetWarning('Ah - something\'s not right here. We can only show you flights up to 331 days in advance - can you also check the return date is after the departure date');

            return [];
        }

        if (!$fields['ParseCalendar']) {
            if (!$this->validRouteAll($fields)) {
                return ['routes' => []];
            }
        }

        $fields['DepDate'] = date("Y-m-d", $fields['DepDate']);

        $payload = $this->getPayload($fields);

        if (!$fields['ParseCalendar']) {
            $this->loadStartPage($fields);
        }

        try {
            $this->loadPayload($payload);
            $response = $this->search($payload);

            if (strpos($response, 'Access Denied') !== false) {
                $this->loggerStateBrowser();

                throw new CheckRetryNeededException(5, 0);
            }

            if (is_string($response)) {
                $response = $this->http->JsonLog($response, 1);
            }
        } catch (InvalidSelectorException | \InvalidSelectorException
        | \UnexpectedResponseException | \WebDriverCurlException $e) {
            $this->logger->error($e->getMessage());

            $this->loggerStateBrowser();

            throw new CheckRetryNeededException(5, 0);
        }

        if (!empty($response)) {
            $this->logger->notice('Data ok, saving session');

            $this->loggerStateBrowser($response);
            //TODO try off HotSession
            $this->keepSession(true);
        }

        $this->saveResponse();

        return ["routes" => $this->parseRewardFlights($response, $fields)];
    }

    public function getUuid()
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),

            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,

            // 48 bits for "node"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    private function loadStartPage($fields)
    {
        $this->logger->notice(__METHOD__);

        try {
            // do not request page on hot session
            $this->http->GetURL("https://www.virginatlantic.com/en-EU");

            if ($btnCookies = $this->waitForElement(\WebDriverBy::xpath("//button[contains(text(),'Yes, I Agree')]"),
                5, true)) {
                $btnCookies->click();
            }

            if ($reward = $this->waitForElement(\WebDriverBy::xpath("//input[@aria-labelledby='Search for Reward Flights']"), 15)) {
                $reward->click();
            } elseif ($this->waitForElement(
                \WebDriverBy::xpath("
                            //div[contains(@class,'homepageError')]//h1[contains(text(),'Site unavailable')]
                            | //h1[contains(.,'This site can’t be reached')]
                            | //h1[contains(.,'This page isn’t working')]
                            | //h1[contains(.,'Access Denied')]"
                ), 0)
            ) {
                $retry = true;
                $accessDenied = true;

                return [];
            }

            if ($roundTrip = $this->waitForElement(\WebDriverBy::xpath("//input[@value='Round trip']"),
                5)) {
                $roundTrip->click();
                $oneway = $this->waitForElement(\WebDriverBy::xpath("//li[./button[./span[normalize-space()='One way']]]"),
                    3);

                if (!$oneway) {
                    $this->logger->error('bad load');
                    $this->loggerStateBrowser();
                    // сайт тупит. лучше сразу на рестрат иначе watchdog прибьет
                    throw new \CheckRetryNeededException(5, 0);
                }
                $oneway->click();

                if ($from = $this->waitForElement(\WebDriverBy::xpath("//input[@id='flights_from']"), 0)) {
                    $from->sendKeys('ZZZ');
                    $from->sendKeys(\WebDriverKeys::ENTER);
                    $from->sendKeys(\WebDriverKeys::ESCAPE);
                }

                if ($to = $this->waitForElement(\WebDriverBy::xpath("//input[@id='flights_to']"), 0)) {
                    $to->sendKeys($fields['ArrCode']);

                    if ($this->waitForElement(\WebDriverBy::xpath("//section[@id='popover-flying-to']"),
                        3)) {
                        $to->sendKeys(\WebDriverKeys::ARROW_DOWN);
                        $to->sendKeys(\WebDriverKeys::ENTER);
                    }
                }

                if ($search = $this->waitForElement(\WebDriverBy::xpath("//button[.//span[normalize-space()='Search flights']]"))) {
                    $search->click();
                }
            }

            $this->saveResponse();
        } catch (\Exception $e) {
            $this->logger->error('Exception: ' . $e->getMessage());
        }
    }

    private function getPayloadFlexDate($fields): ?string
    {
        if (!isset($this->airportDetails[$fields['DepCode']]) || !isset($this->airportDetails[$fields['ArrCode']])) {
            return null;
        }

        $brandID = $this->encodeCabin($fields['Cabin']);
        $dateLabel = date('Y-m-d', $fields['DepDate']);

        $payload = '{"isEdocApplied":false,"tripType":"ONE_WAY","shopType":"MILES","priceType":"Award","nonstopFlightsOnly":"false","bookingPostVerify":"RTR_YES","bundled":"off","segments":[{"origin":"' . $fields['DepCode'] . '","destination":"' . $fields['ArrCode'] . '","originCountryCode":"' . $this->airportDetails[$fields['DepCode']]["country"] . '","destinationCountryCode":"' . $this->airportDetails[$fields['ArrCode']]["country"] . '","departureDate":"' . $dateLabel . '","connectionAirportCode":null}],"destinationAirportRadius":{"measure":100,"unit":"MI"},"originAirportRadius":{"measure":100,"unit":"MI"},"flexAirport":false,"flexDate":true,"flexDaysWeeks":"FLEX_DAYS","passengers":[{"count":' . $fields['Adults'] . ',"type":"ADT"}],"meetingEventCode":"","bestFare":"' . $brandID . '","searchByCabin":true,"cabinFareClass":null,"refundableFlightsOnly":false,"deltaOnlySearch":"false","initialSearchBy":{"fareFamily":"' . $brandID . '","cabinFareClass":null,"meetingEventCode":"","refundable":false,"flexAirport":false,"flexDate":true,"flexDaysWeeks":"FLEX_DAYS","deepLinkVendorId":null},"searchType":"flexDateSearch","searchByFareClass":null,"pageName":"FLEX_DATE","requestPageNum":"","action":"findFlights","actionType":"","priceSchedule":"AWARD","schedulePrice":"miles","shopWithMiles":"on","awardTravel":"true","datesFlexible":true,"flexCalendar":false,"upgradeRequest":false,"is_Flex_Search":true}';
//        $payload = "{\"isEdocApplied\":false,\"tripType\":\"ONE_WAY\",\"shopType\":\"MILES\",\"priceType\":\"Award\",\"nonstopFlightsOnly\":\"false\",\"bookingPostVerify\":\"RTR_YES\",\"bundled\":\"off\",\"segments\":[{\"origin\":\"" . $fields['DepCode'] . "\",\"destination\":\"" . $fields['ArrCode'] . "\",\"originCountryCode\":\"" . $this->airportDetails[$fields['DepCode']]["country"] . "\",\"destinationCountryCode\":\"" . $this->airportDetails[$fields['ArrCode']]["country"] . "\",\"departureDate\":\"" . $fields['DepDate'] . "\",\"connectionAirportCode\":null}],\"destinationAirportRadius\":{\"measure\":100,\"unit\":\"MI\"},\"originAirportRadius\":{\"measure\":100,\"unit\":\"MI\"},\"flexAirport\":false,\"flexDate\":true,\"flexDaysWeeks\":\"FLEX_DAYS\",\"passengers\":[{\"count\":" . $fields['Adults'] . ",\"type\":\"ADT\"}],\"meetingEventCode\":\"\",\"bestFare\":\"{$brandID}\",\"searchByCabin\":true,\"cabinFareClass\":null,\"refundableFlightsOnly\":false,\"deltaOnlySearch\":\"false\",\"initialSearchBy\":{\"fareFamily\":\"{$brandID}\",\"cabinFareClass\":null,\"meetingEventCode\":\"\",\"refundable\":false,\"flexAirport\":false,\"flexDate\":true,\"flexDaysWeeks\":\"FLEX_DAYS\",\"deepLinkVendorId\":null},\"searchType\":\"flexDateSearch\",\"searchByFareClass\":null,\"pageName\":\"FLEX_DATE\",\"requestPageNum\":\"\",\"action\":\"findFlights\",\"actionType\":\"\",\"priceSchedule\":\"AWARD\",\"schedulePrice\":\"miles\",\"shopWithMiles\":\"on\",\"awardTravel\":\"true\",\"datesFlexible\":true,\"flexCalendar\":false,\"upgradeRequest\":false,\"is_Flex_Search\":true}";

        return $payload;
    }

    private function getPayload($fields)
    {
        $payload = '{"action":"findFlights","destinationAirportRadius":{"unit":"MI","measure":100},"deltaOnlySearch":false,"originAirportRadius":{"unit":"MI","measure":100},"passengers":[{"type":"ADT","count":' . $fields['Adults'] . '},{"type":"GBE","count":0},{"type":"CNN","count":0},{"type":"INF","count":0}],"searchType":"search","segments":[{"origin":"' . $fields['DepCode'] . '","destination":"' . $fields['ArrCode'] . '","departureDate":"' . $fields['DepDate'] . '","returnDate":null}],"shopType":"MILES","tripType":"ONE_WAY","priceType":"Award","priceSchedule":"price","awardTravel":true,"refundableFlightsOnly":false,"nonstopFlightsOnly":false,"datesFlexible":false,"flexCalendar":false,"flexAirport":false,"upgradeRequest":false,"pageName":"FLIGHT_SEARCH","cacheKey":"' . $this->cacheKey . '","actionType":"flexDateSearch","initialSearchBy":{"meetingEventCode":"","refundable":false,"flexAirport":false,"flexDate":false,"flexDaysWeeks":"FLEX_DAYS"},"vendorDetails":{"vendorReferrerUrl":"https://www.virginatlantic.com/en-US"},"sortableOptionId":"priceAward","requestPageNum":"1","filter":null}';

        return $payload;
    }

    private function loadPayload($payload)
    {
        $this->logger->notice($payload);
        $script = "
                var nn = 'postData'+localStorage.getItem('cacheKeySuffix');
                localStorage.removeItem(nn);
                localStorage.removeItem('cacheKeySuffix');
                localStorage.setItem('cacheKeySuffix', '{$this->cacheKey}');
                localStorage.setItem('postData{$this->cacheKey}', '{$payload}');
                localStorage.setItem('paymentType', 'miles');
                ";
        $this->logger->debug("[run script]");
        $this->logger->debug($script, ['pre' => true]);

        try {
            $this->driver->executeScript($script);
        } catch (\WebDriverException | \UnexpectedResponseException
        | \WebDriverCurlException | \Facebook\WebDriver\Exception\WebDriverException $e) {
            $this->logger->error($e->getMessage());
            $this->logger->error($e->getTraceAsString());
            $this->markProxyAsInvalid();
            $this->loggerStateBrowser();

            throw new CheckRetryNeededException(self::ATTEMPTS_CNT, 0);
        }
    }

    private function search($payload)
    {
        $payload = preg_replace('/cacheKey":"([^"]+)"/', 'cacheKey":"' . $this->cacheKey . '"', $payload);
        $result = $this->searchXMLHttp($payload);

        return $result;
    }

    private function parseCalendarData($data): array
    {
        $this->logger->notice(__METHOD__);
        $result = [];

        $this->checkErrorMessage($data);

        if ($this->ErrorCode == 9) {
            return [];
        }

        if (empty($data->departureDetail)) {
            $this->logger->warning('Block!');

            throw new \CheckRetryNeededException(5, 0);
        }

        foreach ($data->departureDetail as $num => $item) {
            if (empty($item->cellDetail)) {
                continue;
            }

            $result[] = [
                'date'        => $data->arrivalLocalTs[$num],
                'redemptions' => ['miles' => $item->cellDetail[0]->totalPrice->miles->miles],
                'payments'    => [
                    'currency' => $item->cellDetail[0]->totalPrice->currency->code,
                    'taxes'    => $item->cellDetail[0]->totalPrice->currency->roundedAmount,
                    'fees'     => null,
                ],
                'cabin'            => $this->decodeCabin($data->searchCriteria->request->bestFare) ?? null,
                'brandedCabin'     => $this->brandID2Award($data->searchCriteria->request->bestFare) ?? null,
            ];
        }

        $this->logger->debug(var_export($result, true), ['pre' => true]);

        if (empty($result)) {
            $this->SetWarning('There are no flights for the next 7 days');
        }

        return $result;
    }

    private function parseRewardFlights($data, array $fields): array
    {
        $this->logger->info("parseRewardFlights [" . $fields['DepDate']
            . "-" . $fields['DepCode'] . "-" . $fields['ArrCode'] . "]",
            ['Header' => 2]);
        $routes = [];

        $this->checkErrorMessage($data);

        if ($this->ErrorCode == 9) {
            return [];
        }

        if (!isset($data->itinerary) || !is_array($data->itinerary)) {
            throw new \CheckException('itinerary not found. other format json', ACCOUNT_ENGINE_ERROR);
        }

        $this->logger->debug("Found " . count($data->itinerary) . " routes");

        foreach ($data->itinerary as $numRoot => $it) {
            if (count($it->trip) !== 1) {
                $this->logger->error("check itinerary $numRoot");
            }

            $trip = $it->trip[0];
            $this->logger->notice("Start route " . $numRoot);
            // for debug
            $this->http->JsonLog(json_encode($it), 1);

            $itOffers = [];

            foreach ($it->fare as $fare) {
                if ($fare->soldOut === false && $fare->offered === true) {
                    $itOffers[] = $fare;
                }
            }

            foreach ($itOffers as $itOffer) {
                if ($itOffer->dominantSegmentBrandId === 'LYPE') {
                    continue;
                }

                $segDominantSegmentBrandId = $itOffer->dominantSegmentBrandId;
                $result = [
                    'distance'  => null,
                    'num_stops' => $trip->stopCount,
                    'times'     => [
                        'flight'  => null,
                        'layover' => null,
                    ],
                    'redemptions' => [
                        // totalPriceByPTC - price for all, totalPrice - for ane
                        'miles'   => $itOffer->totalPrice->miles->miles,
                        'program' => $this->AccountFields['ProviderCode'],
                    ],
                    'payments' => [
                        'currency' => $itOffer->totalPrice->currency->code,
                        'taxes'    => $itOffer->totalPrice->currency->amount,
                        'fees'     => null,
                    ],
                    'tickets'        => $itOffer->seatsAvailableCount ?? null,
                    'classOfService' => $this->clearCOS($this->getBrandName($itOffer->dominantSegmentBrandId,
                        $itOffer->brandByFlightLeg)),
                ];

                $result['connections'] = [];

                foreach ($trip->flightSegment as $flightSegment) {
                    $flightSegmentId = $flightSegment->id;

                    foreach ($flightSegment->flightLeg as $numLeg => $flightLeg) {
                        $flightLegId = $flightLeg->id;
                        $seg = [
                            'departure' => [
                                'date'     => date('Y-m-d H:i', strtotime($flightLeg->schedDepartLocalTs)),
                                'dateTime' => strtotime($flightLeg->schedDepartLocalTs),
                                'airport'  => $flightLeg->originAirportCode,
                            ],
                            'arrival' => [
                                'date'     => date('Y-m-d H:i', strtotime($flightLeg->schedArrivalLocalTs)),
                                'dateTime' => strtotime($flightLeg->schedArrivalLocalTs),
                                'airport'  => $flightLeg->destAirportCode,
                            ],
                            'meal'       => null,
                            'cabin'      => null,
                            'fare_class' => null,
                            'distance'   => $flightLeg->distance->measure . ' ' . $flightLeg->distance->unit,
                            'aircraft'   => $flightLeg->aircraft->fleetTypeCode,
                            'flight'     => [$flightLeg->viewSeatUrl->fltNumber],
                            'airline'    => $flightLeg->marketingCarrier->code,
                            'operator'   => $flightLeg->operatingCarrier->code,
                            'times'      => [
                                'flight'  => null,
                                'layover' => null,
                            ],
                        ];

                        foreach ($flightLeg->viewSeatUrl->fareOffer->itineraryOfferList as $list) {
                            if ($list->dominantSegmentBrandId === $segDominantSegmentBrandId) {
                                foreach ($list->brandInfoByFlightLegs as $brandByLeg) {
                                    if ($brandByLeg->flightSegmentId === $flightSegmentId && $brandByLeg->flightLegId === $flightLegId) {
                                        if ($brandByLeg->brandId === 'UNKNOWN') {
                                            $seg['cabin'] = $this->decodeCabin($list->dominantSegmentBrandId);
                                        } else {
                                            $seg['cabin'] = $this->decodeCabin($brandByLeg->brandId);
                                        }
                                        $seg['fare_class'] = $brandByLeg->cos;

                                        if (empty($itOffer->brandByFlightLeg[$numLeg]->brandName)) {
                                            $brandName = $this->getBrandName($itOffer->dominantSegmentBrandId,
                                                $itOffer->brandByFlightLeg);
                                        } else {
                                            $brandName = $itOffer->brandByFlightLeg[$numLeg]->brandName;
                                        }

                                        $seg['classOfService'] = $this->clearCOS($brandName);
//                                        $seg['classOfService'] = $brandName . ' (' . $brandByLeg->cos . ')'; // no full name with fare class
                                    }
                                }
                            }
                        }

                        $result['connections'][] = $seg;
                    }
                }

                $routes[] = $result;
            }
        }

        $this->logger->debug('Parsed data:');
        $this->logger->debug(var_export($routes, true), ['pre' => true]);

        return $routes;
    }

    private function searchXMLHttp($payload)
    {
        $this->logger->notice(__METHOD__);

        $script = '
                    var xhttp = new XMLHttpRequest();
                    xhttp.withCredentials = true;
                    xhttp.open("POST", "https://www.virginatlantic.com/shop/ow/search", false);
                    xhttp.setRequestHeader("Accept", "application/json");
                    xhttp.setRequestHeader("Content-type", "application/json; charset=utf-8");
                    xhttp.setRequestHeader("X-APP-CHANNEL", "sl-sho");
                    xhttp.setRequestHeader("X-APP-ROUTE", "SL-RSB");
                    xhttp.setRequestHeader("X-APP-REFRESH", "");
                    xhttp.setRequestHeader("Sec-Fetch-Dest", "empty");
                    xhttp.setRequestHeader("Sec-Fetch-Mode", "cors");
                    xhttp.setRequestHeader("Sec-Fetch-Site", "same-origin");
                    xhttp.setRequestHeader("CacheKey", "' . $this->cacheKey . '");
                    var resData = null; 
                    xhttp.onreadystatechange = function() {
                        resData = this.responseText;
                    };
                    xhttp.send(\'' . $payload . '\');
                    return resData;
                ';
        $this->logger->debug("[run script]");
        $this->logger->debug($script, ['pre' => true]);
        sleep(2);

        try {
            $response = $this->driver->executeScript($script);
        } catch (\WebDriverException | \UnexpectedResponseException
        | \WebDriverCurlException | \Facebook\WebDriver\Exception\WebDriverException $e) {
            $this->logger->error('WebDriverException: ' . $e->getMessage());
            $this->checkWebDriverException($e->getMessage());
            sleep(2);

            try {
                $response = $this->driver->executeScript($script);
            } catch (\WebDriverException | \UnexpectedResponseException
            | \WebDriverCurlException | \Facebook\WebDriver\Exception\WebDriverException $e) {
                $this->logger->error('Exception: ' . $e->getMessage());

                throw new CheckRetryNeededException(5, 0);
            }
        }

        if (strpos($response, "{") !== 0) {
            $this->logger->debug($response);
        }

        if (strpos($response, 'cpr_chlge') !== false) {
            $this->logger->error("To many request!");

            throw new \CheckRetryNeededException(5, 5);
        }

        return $response;
    }

    private function searchXMLHttpFlexDate($payload)
    {
        $this->logger->notice(__METHOD__);

        $script = '
                    var xhttp = new XMLHttpRequest();
                    xhttp.withCredentials = true;
                    xhttp.open("POST", "https://www.virginatlantic.com/shop/ow/flexdatesearch", false);
                    xhttp.setRequestHeader("Accept", "application/json");
                    xhttp.setRequestHeader("Content-type", "application/json; charset=utf-8");
                    xhttp.setRequestHeader("X-APP-CHANNEL", "sl-sho");
                    xhttp.setRequestHeader("X-APP-ROUTE", "SL-RSB");
                    xhttp.setRequestHeader("X-APP-REFRESH", "");
                    xhttp.setRequestHeader("Sec-Fetch-Dest", "empty");
                    xhttp.setRequestHeader("Sec-Fetch-Mode", "cors");
                    xhttp.setRequestHeader("Sec-Fetch-Site", "same-origin");
                    xhttp.setRequestHeader("CacheKey", "' . $this->cacheKey . '");
                    xhttp.setRequestHeader("Referer", "https://www.virginatlantic.com/advanced-search");
                    var resData = null; 
                    xhttp.onreadystatechange = function() {
                        resData = this.responseText;
                    };
                    xhttp.send(\'' . $payload . '\');
                    return resData;
                ';
        $this->logger->debug("[run script]");
        $this->logger->debug($script, ['pre' => true]);
        sleep(5);

        try {
            $response = $this->driver->executeScript($script);
        } catch (\WebDriverException | \UnexpectedResponseException
        | \WebDriverCurlException | \Facebook\WebDriver\Exception\WebDriverException $e) {
            $this->logger->error('WebDriverException: ' . $e->getMessage());
            $this->checkWebDriverException($e->getMessage());
            sleep(2);

            try {
                $response = $this->driver->executeScript($script);
            } catch (\WebDriverException | \UnexpectedResponseException
            | \WebDriverCurlException | \Facebook\WebDriver\Exception\WebDriverException $e) {
                throw new CheckRetryNeededException(5, 0);
            }
        }

        if (strpos($response, "{") !== 0) {
            $this->logger->debug($response);
        }

        return $response;
    }

    private function checkWebDriverException($message)
    {
        if (strpos($message, 'JSON decoding of remote response failed') !== false
            && $this->http->FindPreg("/\bError code: 4\b/", false, $message)
        ) {
            throw new CheckRetryNeededException(self::ATTEMPTS_CNT, 0);
        }
    }

    private function validRouteAll($fields)
    {
        $this->logger->notice(__METHOD__);

        $airports = \Cache::getInstance()->get('ra_virgin_airports');
        $airportDesc = \Cache::getInstance()->get('ra_virgin_airportDesc');
        $airportCountryCode = \Cache::getInstance()->get('ra_virgin_airportCountry');

        if (!$airports || !is_array($airports) || !$airportDesc || !is_array($airportDesc) || !$airportCountryCode || !is_array($airportCountryCode)) {
            $airports = [];
            $airportDesc = [];
            $airportCountryCode = [];

            $browser = new \HttpBrowser("none", new \CurlDriver());

            $browser->SetProxy("{$this->http->getProxyAddress()}:{$this->http->getProxyPort()}");
            $browser->setProxyAuth($this->http->getProxyLogin(), $this->http->getProxyPassword());
            $browser->setUserAgent($this->http->getDefaultHeader("User-Agent"));

            $this->http->brotherBrowser($browser);

            $browser->setDefaultHeader("Upgrade-Insecure-Requests", "1");
            $browser->setDefaultHeader("Connection", "keep-alive");
            $browser->setDefaultHeader('Accept-Encoding', 'gzip, deflate, br');

            $browser->GetURL("https://www.virginatlantic.com/util/airports/ALL/asc", [], 20);

            if ($browser->currentUrl() === 'https://www.virginatlantic.com/gb/en/error/system-unavailable1.html') {
                // it's work
                $browser->GetURL("https://www.virginatlantic.com/util/airports/ALL/asc", [], 20);
            }
            $data = $browser->JsonLog(null, 1);

            if ($browser->currentUrl() === 'https://www.virginatlantic.com/gb/en/error/system-unavailable1.html') {
                $this->markProxyAsInvalid();

                throw new \CheckRetryNeededException(self::ATTEMPTS_CNT, 0);
            }

            if (strpos($browser->Error,
                    'Network error 56 - Received HTTP code 407 from proxy after CONNECT') !== false
                || strpos($browser->Error,
                    'Network error 56 - Received HTTP code 400 from proxy after CONNECT') !== false
                || strpos($browser->Error, 'Network error 28 - Operation timed out after ') !== false
                || $browser->Response['code'] == 403
            ) {
                $this->markProxyAsInvalid();

                throw new CheckRetryNeededException(self::ATTEMPTS_CNT, 0);
            }

            if (!isset($data->listOfCities) || !is_array($data->listOfCities)) {
                return true;
            }

            if (empty($data->listOfCities)) {
                return true;
            }

            foreach ($data->listOfCities as $city) {
                $airports[] = $city->airportCode;
                $airportDesc[$city->airportCode] = $city->cityName . ', ' . $city->region;
                $airportCountryCode[$city->airportCode] = $city->countryCode;
            }

            if (!empty($airports)) {
                \Cache::getInstance()->set('ra_virgin_airports', $airports, 60 * 60 * 24);
                \Cache::getInstance()->set('ra_virgin_airportDesc', $airportDesc, 60 * 60 * 24);
                \Cache::getInstance()->set('ra_virgin_airportCountry', $airportCountryCode, 60 * 60 * 24);
            }

            $browser->cleanup();
        }

        if (!empty($airports) && !in_array($fields['DepCode'], $airports)) {
            $this->SetWarning('no flights from ' . $fields['DepCode']);

            return false;
        }

        if (!empty($airports) && !in_array($fields['ArrCode'], $airports)) {
            $this->SetWarning('no flights to ' . $fields['ArrCode']);

            return false;
        }

        $this->airportDetails = [
            $fields['DepCode'] => [
                'desc'    => $airportDesc[$fields['DepCode']],
                'country' => $airportCountryCode[$fields['DepCode']],
            ],
            $fields['ArrCode'] => [
                'desc'    => $airportDesc[$fields['ArrCode']],
                'country' => $airportCountryCode[$fields['ArrCode']],
            ],
        ];

        return true;
    }

    private function encodeCabin($cabin)
    {
        switch ($cabin) {
            case 'economy':
                return 'VSLT';

            case 'premiumEconomy':
                return 'VSPE';

            case 'business':
                return 'VSUP';

            case 'firstClass':
                return 'VSUP';
        }
        $this->sendNotification('new cabin ' . $cabin . ' // ZM');

        return null;
    }

    private function loggerStateBrowser($result = null)
    {
        /** @var \SeleniumDriver seleniumDriver */
        $seleniumDriver = $this->http->driver;

        $memStatBrowsers = \Cache::getInstance()->get(self::BROWSER_STATISTIC_KEY);

        if (!is_array($memStatBrowsers)) {
            $memStatBrowsers = [];
        }
        $browserInfo = $seleniumDriver->getBrowserInfo();
        $key = $this->getKeyConfig($browserInfo);

        if (!isset($memStatBrowsers[$key])) {
            $memStatBrowsers[$key] = ['success' => 0, 'failed' => 0];
        }

        if (empty($result) && $this->ErrorCode !== ACCOUNT_WARNING) {
            $this->logger->info("marking config {$this->config} as bad");
            \Cache::getInstance()->set('aeroplan_config_' . $this->config, 0);
        } else {
            $this->logger->info("marking config {$this->config} as successful");
            \Cache::getInstance()->set('aeroplan_config_' . $this->config, 1);
        }

        if (empty($result) && $this->ErrorCode !== ACCOUNT_WARNING) {
            $memStatBrowsers[$key]['failed']++;
        } else {
            $memStatBrowsers[$key]['success']++;
        }

        if (!isset($noStat)) {
            \Cache::getInstance()->set(self::BROWSER_STATISTIC_KEY, $memStatBrowsers, 60 * 60 * 24);
        }
        $this->logger->warning(var_export($memStatBrowsers, true), ['pre' => true]);
    }

    private function getKeyConfig(array $info)
    {
        return $info[\SeleniumStarter::CONTEXT_BROWSER_FAMILY] . '-' . $info[\SeleniumStarter::CONTEXT_BROWSER_VERSION] . '-' . $this->seleniumRequest->getOs();
    }

    private function checkErrorMessage($data): void
    {
        if (isset($data->shoppingError, $data->shoppingError->error, $data->shoppingError->error->message, $data->shoppingError->error->message->message)) {
            $this->logger->error($data->shoppingError->error->message->message);

            if (strpos($data->shoppingError->error->message->message,
                    'No results were found for your search. You may have better results if you use flexible dates and airports or if you search for a One-Way') !== false) {
                $this->SetWarning("Sorry, no reward flights are available for your search, some flights don't operate every day. Try selecting flexible dates to see more availability.#101638_A");
            } elseif (strpos($data->shoppingError->error->message->message,
                    'No results were found for your search. Try changing your cities or dates. Some itineraries may not be offered') !== false
                || strpos($data->shoppingError->error->message->message,
                    'for the date selected is not available. The next available flight departs on') !== false) {
                $this->SetWarning("Sorry, no flights are available for that search. As some flights don't operate every day, try selecting flexible dates or nearby airports to see more availability.#101639A");
            } elseif (strpos($data->shoppingError->error->message->message,
                    "We're sorry, there was a problem processing your request. Please go back and try the entry again.") !== false) {
                // bad route
                $this->SetWarning("We're sorry, there was a problem processing your request. Please go back and try the entry again.");
            } elseif (strpos($data->shoppingError->error->message->message,
                    "We're sorry, but we are unable to find a flight with Delta or our partner airlines that currently services this route. Please try again") !== false) {
                $this->SetWarning("Sorry, no flights are available for that search. As some flights don't operate every day, try selecting flexible dates or nearby airports to see more availability.");
            } elseif (strpos($data->shoppingError->error->message->message,
                    "Please try searching for airports within a 100 mile radius of") !== false
                || strpos($data->shoppingError->error->message->message,
                    "We're sorry, but there are not enough seats available on this flight to complete your booking") !== false
                || strpos($data->shoppingError->error->message->message,
                    "Travel for the date you selected is not offered or sold out. ") !== false
            ) {
                $this->SetWarning("Sorry, there are no flights available for that search. Please try again");
            } elseif (strpos($data->shoppingError->error->message->message,
                    "There are no flights available for the date(s) requested. Please change your cities or dates") !== false
                || strpos($data->shoppingError->error->message->message,
                    "There are no flights available for the search criteria provided.") !== false
                || strpos($data->shoppingError->error->message->message,
                    "re sorry, but we do not fly this route on the selected day. Some of our flights operate seasonally") !== false
                || strpos($data->shoppingError->error->message->message,
                    "re sorry, but flights to this destination have either departed for the day or are departing too soon to be booked. Please try again by selecting a different date") !== false
                || strpos($data->shoppingError->error->message->message,
                    "re sorry, but we do not fly this route on the selected day. Some of our routes operate seasonally or on select days of the week") !== false
                || strpos($data->shoppingError->error->message->message,
                    "re sorry, but we are unable to find a flight that meets your current search criteria. Please try again by") !== false
                || strpos($data->shoppingError->error->message->message,
                    "re sorry, but we are unable to find a flight that meets your current search criteria. Please try again.") !== false
                || strpos($data->shoppingError->error->message->message,
                    "We are unable to find a flight on the selected date with enough available seats") !== false
                || strpos($data->shoppingError->error->message->message,
                    "We're sorry, but online bookings for this route require an advance purchase") !== false
            ) {
                $this->SetWarning($data->shoppingError->error->message->message);
            } else {
                $this->sendNotification('check error msg // ZM');
            }

            return;
        }

        if (isset($data->shoppingError, $data->shoppingError->error, $data->shoppingError->error->message, $data->shoppingError->error->message->message)) {
            $this->logger->error($data->shoppingError->error->message->message);

            return;
        }
    }

    private function clearCOS(string $cos): string
    {
        if (preg_match("/^(.+\w+) (?:cabin|class|standard|reward)$/i", $cos, $m)) {
            $cos = $m[1];
        }

        return $cos;
    }

    private function getBrandName($id, $list): ?string
    {
        foreach ($list as $item) {
            if ($item->brandId === $id && isset($item->brandName)) {
                return $item->brandName;
            }
        }

        return $this->brandID2Award($id);
    }

    private function brandID2Award(string $brandID): ?string
    {
        $array = [
            'MAIN'  => 'Economy Classic',
            'E'     => 'Economy Classic',
            'AFST'  => 'Economy Classic',
            'KLEC'  => 'Economy Classic',
            'VSCL'  => 'Economy Classic',
            'VSLT'  => 'Economy Classic',
            'VSPE'  => 'Premium',
            'VSUP'  => 'Upper Class',
            'BU'    => 'Upper Class',
            'FIRST' => 'Upper Class',
        ];

        if (!isset($array[$brandID])) {
            $this->sendNotification('check brandId: ' . $brandID);
        }

        return $array[$brandID] ?? null;
    }

    private function decodeCabin($cabin)
    {
        switch ($cabin) {
            case 'VSLT':
            case 'VSCL':
            case 'VSDT':
            case 'E':
            case 'AFST': // AirFrance
            case 'DCP': // Delta
            case 'DPPS': // Delta
            case 'KLEC': // KLM
            case 'MAIN':
                return 'economy';

            case 'VSPE':
            case 'PE':
            case 'AFPE':
            case 'KLPE':
                return 'premiumEconomy';

            case 'VSUP':
            case 'BU':
            case 'AFBU': // AirFrance
            case 'KLBU': // KLM
                return 'business';

            case 'FIRST':
            case 'D1':
            case 'D1S':
                return 'firstClass';
        }
        $this->sendNotification('new cabin ' . $cabin . ' // ZM');

        return null;
    }
}
