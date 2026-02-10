<?php

namespace AwardWallet\Engine\etihad\RewardAvailability;

use AwardWallet\Common\Parsing\Web\Proxy\Provider\MountRotatingRequest;
use AwardWallet\Engine\ProxyList;
use Cache;
use CheckException;
use CheckRetryNeededException;
use ErrorException;
use ScriptTimeoutException;
use TimeOutException;
use UnexpectedJavascriptException;
use UnexpectedResponseException;
use WebDriverBy;
use WebDriverCurlException;
use WebDriverException;

class ParserNew extends \TAccountChecker
{
    use \SeleniumCheckerHelper;
    use \PriceTools;
    use ProxyList;

    public $isRewardAvailability = true;
    private $debugMode = false;
    private $dynamicContentPath;

    public static function getRASearchLinks(): array
    {
        return ['https://www.etihad.com/en-us/' => 'search page'];
    }

    public function InitBrowser(): void
    {
        \TAccountChecker::InitBrowser();
        $this->UseSelenium();
//        $this->useGoogleChrome(\SeleniumFinderRequest::CHROME_95);   //  For Local Debug
        $this->useChromePuppeteer(\SeleniumFinderRequest::CHROME_PUPPETEER_103);
        $this->debugMode = isset($this->AccountFields['DebugState']) && $this->AccountFields['DebugState'];

        $this->http->saveScreenshots = true;
        $this->seleniumRequest->setHotSessionPool(self::class, $this->AccountFields['ProviderCode']);

        $array = ['fr', 'es', 'de', 'us', 'au', 'gb', 'pt', 'ca'];
        $targeting = $array[random_int(0, count($array) - 1)];


        if ($this->AccountFields['Partner'] == 'awardwallet') {
            $this->setProxyMount();
        } else {
            $this->setProxyGoProxies(null, $targeting);
        }

        $this->disableImages();
        $this->useCache();
        $this->usePacFile(false);
    }

    public function LoadLoginForm(): bool
    {
        return true;
    }

    public function IsLoggedIn(): bool
    {
        return false;
    }

    public function Login(): bool
    {
        return true;
    }

    public function getRewardAvailabilitySettings(): array
    {
        return [
            'supportedCurrencies'      => ['USD'],
            'supportedDateFlexibility' => 0, // 1
            'defaultCurrency'          => 'USD',
            'priceCalendarCabins'      => ["economy", 'business', 'firstClass', "unknown"],
        ];
    }

    public function ParseCalendar(array $fields): array
    {
        $this->logger->info("Parse Calendar", ['Header' => 2]);
        $this->logger->debug("params: " . var_export($fields, true));

        $fields['Cabin'] = $this->getCabinFields(false)[$fields['Cabin']];
        $supportedCurrencies = $this->getRewardAvailabilitySettings()['supportedCurrencies'];

        if (!in_array($fields['Currencies'][0], $supportedCurrencies)) {
            $fields['Currencies'][0] = $this->getRewardAvailabilitySettings()['defaultCurrency'];
            $this->logger->notice("parse with defaultCurrency: " . $fields['Currencies'][0]);
        }

        $url = $this->createHttpQuery($fields);

        try {
            $this->loadPage($url);
        } catch (WebDriverCurlException | WebDriverException $e) {
            $this->logger->error($e->getMessage());

            throw new CheckRetryNeededException(5, 0);
        } catch (ErrorException $e) {
            if (strpos($e->getMessage(), 'Array to string conversion') !== false
                || strpos($e->getMessage(), 'strlen() expects parameter 1 to be string, array given') !== false
            ) {
                // TODO bug selenium
                throw new CheckRetryNeededException(5, 0);
            }

            throw $e;
        }

        $calendarData = $this->getCalendarData($fields);

        if (empty($calendarData)) {
            $this->logger->error('CALENDAR NO HAS DATA!');

            throw new CheckRetryNeededException(5, 0);
        }

        $fares = $this->parseCalendarData($calendarData, $fields);

        $this->logger->debug(var_export($fares, true), ['pre' => true]);

        return ['fares' => $fares];
    }

    public function ParseRewardAvailability(array $fields): array
    {
        $this->logger->info("Parse Reward Availability", ['Header' => 2]);
        $this->logger->debug("params: " . var_export($fields, true));

        $fields['Cabin'] = $this->getCabinFields(false)[$fields['Cabin']];
        $supportedCurrencies = $this->getRewardAvailabilitySettings()['supportedCurrencies'];

        if (!in_array($fields['Currencies'][0], $supportedCurrencies)) {
            $fields['Currencies'][0] = $this->getRewardAvailabilitySettings()['defaultCurrency'];
            $this->logger->notice("parse with defaultCurrency: " . $fields['Currencies'][0]);
        }

        $url = $this->createHttpQuery($fields);

        if (!$fields['ParseCalendar']) {
            try {
                $this->loadPage($url);
            } catch (WebDriverCurlException | WebDriverException $e) {
                $this->logger->error($e->getMessage());

                throw new CheckRetryNeededException(5, 0);
            } catch (ErrorException $e) {
                if (strpos($e->getMessage(), 'Array to string conversion') !== false
                    || strpos($e->getMessage(), 'strlen() expects parameter 1 to be string, array given') !== false
                ) {
                    // TODO bug selenium
                    throw new CheckRetryNeededException(5, 0);
                }

                throw $e;
            }
        }

        $responses = $this->getFlightData($fields);

        if (is_null($responses)) {
            if ($this->ErrorCode != ACCOUNT_WARNING) {
                throw new CheckRetryNeededException(5, 0);
            }
            $this->keepSession(true);

            return ['routes' => []];
        }

        return ['routes' => $this->parseFlightData($responses, $fields)];
    }

    protected function incapsula($incapsula, $src): bool
    {
        $this->logger->notice(__METHOD__);
        $referer = $this->http->currentUrl();

        $this->driver->switchTo()->frame($incapsula);

        if (isset($incapsula)) {
            sleep(2);
            $this->logger->debug("parse captcha form");
            $this->saveResponse();

            $action = $this->http->FindPreg("/xhr2.open\(\"POST\", \"([^\"]+)/");
            $dataUrl = $this->http->FindPreg('#"(/_Incapsula_Resource\?SWCNGEEC=.+?)"#');
            $this->driver->switchTo()->defaultContent();
            $this->saveResponse();

            if (!$dataUrl || !$action) {
                return false;
            }
            $this->http->NormalizeURL($dataUrl);
            $this->http->GetURL($dataUrl);
            $json = $this->waitForElement(WebDriverBy::xpath('//pre[not(@id)]'), 20);

            if (!$json) {
                throw new CheckRetryNeededException(5, 0);
            }
            $json = str_replace(['<pre>', '</pre>'], '', $json->getText());
            $data = $this->http->JsonLog($json);

            if (!isset($data->gt, $data->challenge)) {
                return false;
            }

            $recognizer = $this->getCaptchaRecognizer();
            $recognizer->RecognizeTimeout = 120;

            $parameters = [
                "pageurl"    => $referer,
                "proxy"      => $this->http->GetProxy(),
                'challenge'  => $data->challenge,
                'method'     => 'geetest',
            ];

            $request = $this->recognizeByRuCaptcha($recognizer, $data->gt, $parameters);

            if (is_string($request)) {
                $request = $this->http->JsonLog($request, 1);
            } elseif ((is_bool($request) && $request === false)) {
                throw new CheckException('bad captcha', ACCOUNT_ENGINE_ERROR);
            } elseif (empty($request->challenge)) {
                throw new CheckRetryNeededException(5, 0);
            }

            $this->driver->executeScript("
                fetch(\"{$action}\", {
                  \"headers\": {
                    \"accept\": \"*/*\",
                    \"accept-language\": \"en-US,en;q=0.9\",
                    \"content-type\": \"application/x-www-form-urlencoded\",
                  },
                  \"referrer\": \"https://digital.etihad.com{$src}\",
                  \"referrerPolicy\": \"strict-origin-when-cross-origin\",
                  \"body\": \"geetest_challenge={$request->geetest_challenge}&geetest_validate={$request->geetest_validate}&geetest_seccode={$request->geetest_seccode}\",
                  \"method\": \"POST\",
                  \"mode\": \"cors\",
                  \"credentials\": \"include\"
                }).then( result => {
                    let script = document.createElement(\"script\");
                    let id = \"challenge\";
                    script.id = id;
                    document.querySelector(\"body\").append(script);
                });
            ");

            $this->waitForElement(WebDriverBy::xpath('//script[@id="challenge"]'), 10, false);

            $this->http->GetURL($referer);
        }

        return true;
    }

    protected function getPayloadFromFlight($fields, $cabinSearch): string
    {
        $travelers = [];

        for ($i = 0; $i < $fields['Adults']; $i++) {
            $travelers[] = ["passengerTypeCode" => "ADT"];
        }

        $dateLabel = date('Y-m-d', $fields['DepDate']);

        return json_encode([
            "commercialFareFamilies" => $cabinSearch,
            "itineraries"            => [
                [
                    "originLocationCode"      => $fields['DepCode'],
                    "destinationLocationCode" => $fields['ArrCode'],
                    "departureDateTime"       => "{$dateLabel}T00:00:00.000",
                    "isRequestedBound"        => true,
                ],
            ],
            "travelers"         => $travelers,
            "searchPreferences" => [
                "showMilesPrice"                => true,
                "showSoldOut"                   => true,
                "maxFlightCombinationsPerBound" => 25,
            ],
            "corporateCodes" => ["264154"],
        ]);
    }

    protected function getPayloadFromCalendar($fields): string
    {
        $travelers = [];

        for ($i = 0; $i < $fields['Adults']; $i++) {
            $travelers[] = ["passengerTypeCode" => "ADT"];
        }

        $dateLabel = date('Y-m-d', $fields['DepDate']);

        return json_encode([
            "travelers"   => $travelers,
            "itineraries" => [
                [
                    "originLocationCode"      => $fields['DepCode'],
                    "destinationLocationCode" => $fields['ArrCode'],
                    "departureDateTime"       => "{$dateLabel}T00:00:00.000",
                    "isRequestedBound"        => true,
                ],
            ],
            "commercialFareFamilies" => [$this->getStringFormaterCabin($fields["Cabin"]['class'])],
            "searchPreferences"      => [
                "showMilesPrice"         => true,
                "showUnavailableEntries" => true,
            ],
            "corporateCodes" => ["264154"],
        ]);
    }

    private function getCabinFields(bool $onlyKeys = true): array
    {
        $cabins = [
            'economy'        => ['class' => 'Economy', 'execution' => 'e2s1', 'query' => 'E'],
            'premiumEconomy' => ['class' => 'Economy', 'execution' => 'e3s1', 'query' => 'E'], // has no
            'firstClass'     => ['class' => 'First', 'execution' => 'e2s1', 'query' => 'F'],
            // it has the residence ['class' => 'First', 'execution' => 'e1s1']
            'business' => ['class' => 'Business', 'execution' => 'e3s1', 'query' => 'B'],
        ];

        if ($onlyKeys) {
            return array_keys($cabins);
        }

        return $cabins;
    }

    private function createHttpQuery(array $fields): string
    {
        $params = [
            'LANGUAGE'                => 'EN',
            'CHANNEL'                 => 'DESKTOP',
            'B_LOCATION'              => $fields['DepCode'],
            'E_LOCATION'              => $fields['ArrCode'],
            'TRIP_TYPE'               => 'O',
            'CABIN'                   => $fields['Cabin']['query'],
            'TRIP_FLOW_TYPE'          => 'AVAILABILITY',
            'DATE_1'                  => date("Ymd", $fields['DepDate']) . '0000',
            'WDS_ENABLE_MILES_TOGGLE' => 'TRUE',
            'FLOW'                    => 'AWARD',
        ];

        $travelers = '';

        for ($i = 0; $i < $fields['Adults']; $i++) {
            $travelers .= 'ADT,';
        }
        $params['TRAVELERS'] = substr($travelers, 0, -1);

        $query = http_build_query($params);

        return "https://digital.etihad.com/book/search?{$query}";
    }

    private function getFlightData($fields): ?array
    {
        $responses = [];
        $noFlights = $this->waitForElement(WebDriverBy::xpath("//span[contains(.,'No flight available')]/../span[contains(@class,'description')]"),
            0);

        if ($noFlights) {
            $this->SetWarning($noFlights->getText());

            return null;
        }

        $url = 'https://api-des.etihad.com/airlines/EY/v2/search/air-bounds?guestOfficeId=&language=en&useTest=false';

        foreach ($this->getArrayCabinForSearchFlight($fields) as $cabinSearch) {
            $payload = $this->getPayloadFromFlight($fields, $cabinSearch);
            $responses[] = $this->runXHR($url, $payload);
        }

        if (empty($responses)) {
            $this->logger->error("No flights found. Need retry!");

            throw new CheckRetryNeededException(5, 0);
        }

        return $responses;
    }

    private function getCalendarData($fields): array
    {
        $url = 'https://api-des.etihad.com/airlines/EY/v2/search/air-calendars?guestOfficeId=&language=en&useTest=false';
        $payload = $this->getPayloadFromCalendar($fields);
        $response = $this->runXHR($url, $payload);

        return $response;
    }

    private function runXHR($url, $payload)
    {
        [$XDToken, $bearerToken] = $this->getToken();
        $script = /** @lang JavaScript */ '
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "' . $url . '", false);
            xhr.setRequestHeader("Accept", "application/json");
            xhr.setRequestHeader("Accept-Language", "en-US,en;q=0.9");
            xhr.setRequestHeader("Authorization", "Bearer ' . $bearerToken . '");
            xhr.setRequestHeader("Content-Type", "application/json");
            xhr.setRequestHeader("X-D-Token", "' . $XDToken . '");

            var data = \'' . $payload . '\';
            var responseText = null;
            xhr.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    responseText = this.responseText;
                }
            };
            xhr.send(data);
            return responseText;
            ';
        $this->logger->info($script, ['pre' => true]);

        try {
            $responseJSON = $this->driver->executeScript($script);
            $response = $this->http->JsonLog($responseJSON, 1, true);
        } catch (WebDriverCurlException | WebDriverException $e) {
            $this->logger->info($e->getMessage());

            throw new CheckRetryNeededException(5, 0);
        }

        return $response;
    }

    private function parseCalendarData(array $response, array $fields): array
    {
        $this->logger->notice(__METHOD__);
        $result = [];

        $this->saveResponse();

        if (isset($response['warnings']) && ($response['warnings'][0]['code'] === "40834")) {
            $this->SetWarning('There are no flights for the next 7 days for this class cabin');

            return [];
        }

        foreach ($response['data'] as $day) {
            if (!empty($day['status']) && $day['status']['value'] === "unavailable") {
                continue;
            }

            $currencyProvider = $day['prices']['totalPrices'][0]['currencyCode'];
            $decimalPlaces = $response['dictionaries']['currency'][$currencyProvider]['decimalPlaces'] ?? 1;
            $taxes = round($day['prices']['totalPrices'][0]['totalTaxes'] / $fields['Adults']) / (10 ** $decimalPlaces);

            $brandedCabin = $response['dictionaries']['fareFamilyWithServices'][$day['fareFamilyCode']]['commercialFareFamily'];

            $result[] = [
                'date'        => $day['departureDate'],
                'redemptions' => ['miles' => round($day['prices']['milesConversion']['convertedMiles']['base'] / $fields['Adults'])],
                'payments'    => [
                    'currency' => $day['prices']['totalPrices'][0]['currencyCode'],
                    'taxes'    => $taxes,
                    'fees'     => null,
                ],
                'cabin'            => ($brandedCabin !== 'FIRST') ? strtolower($brandedCabin) : 'firstClass',
                'brandedCabin'     => $brandedCabin,
            ];
        }

        if (empty($result)) {
            $this->SetWarning('There are no flights for the next 7 days for this class cabin');
        }

        return $result;
    }

    private function parseFlightData(array $responses, array $fields): array
    {
        $this->logger->notice(__METHOD__);
        $tmpResults = [];
        $noFlight = 0;
        $this->saveResponse();

        if (empty($responses[0]) || empty($responses[1])) {
            $this->logger->error('DATA NOT FOUND');

            throw new CheckRetryNeededException(5, 0);
        }

        foreach ($responses as $response) {
            if (isset($response['errors']) && !isset($response['data'])) {
                $errors = $response['errors'][0];
                $noFlight++;

                if ($noFlight >= 2) {
                    $this->SetWarning('There is no available flight for this date, please choose another date or restart your search');

                    return [];
                }

                if ($errors['title'] === "UNKNOWN CITY/AIRPORT") {
                    $this->SetWarning('UNKNOWN CITY/AIRPORT');

                    return [];
                }

                if (($errors['title'] === "NO FARES")
                    || ($errors['title'] === "NO FLIGHTS FOUND")) {
                    continue;
                }
                $this->logger->error($errors['title']);
            }

            $airBoundGroups = $response['data']['airBoundGroups'];
            $flight = $response['dictionaries']['flight'];
            $airline = $response['dictionaries']['airline'];
            $fareFamilyWithServices = $response['dictionaries']['fareFamilyWithServices'];
            $currency = $response['dictionaries']['currency'];

            try {
                $dynamicContent = $this->getDynamicContent();
            } catch (WebDriverCurlException | UnexpectedResponseException $e) {
                $this->logger->error($e->getMessage());
                $this->logger->error($e->getTraceAsString());

                throw new CheckRetryNeededException(5, 0);
            }

            foreach ($airBoundGroups as $boundGroup) {
                $tmp = [
                    'distance'  => null,
                    'num_stops' => count($boundGroup['boundDetails']['segments']) - 1,
                ];

                foreach ($boundGroup['boundDetails']['segments'] as $segment) {
                    $segmentInfo = $flight[$segment['flightId']];
                    $depDateTime = str_replace('T', ' ', substr($segmentInfo['departure']['dateTime'], 0, 16));
                    $arrDateTime = str_replace('T', ' ', substr($segmentInfo['arrival']['dateTime'], 0, 16));

                    $tmp['connections'][] = [
                        'departure' => [
                            'date'     => $depDateTime,
                            'dateTime' => strtotime($depDateTime),
                            'airport'  => $segmentInfo['departure']['locationCode'],
                            'terminal' => $segmentInfo['departure']['terminal'] ?? null,
                        ],
                        'arrival' => [
                            'date'     => $arrDateTime,
                            'dateTime' => strtotime($arrDateTime),
                            'airport'  => $segmentInfo['arrival']['locationCode'],
                            'terminal' => $segmentInfo['arrival']['terminal'] ?? null,
                        ],
                        'meal'     => null,
                        'flight'   => [$segmentInfo['marketingAirlineCode'] . $segmentInfo['marketingFlightNumber']],
                        'airline'  => $segmentInfo['marketingAirlineCode'],
                        'operator' => (isset($segmentInfo['operatingAirlineCode']))
                            ? $airline[$segmentInfo['operatingAirlineCode']]
                            : 'Another airline',
                        'aircraft' => $segmentInfo['aircraftCode'],
                        'flightId' => $segment['flightId'],
                    ];
                }

                foreach ($boundGroup['airBounds'] as $numAirBound => $airBound) {
                    if (isset($airBound['status']) && $airBound['status']['value'] === 'soldOut') {
                        $this->logger->info("skip airBound $numAirBound: soldOut");
                        $soldOut = true;

                        continue;
                    }
                    $prices = $airBound['prices'];

                    if (!isset($prices['milesConversion']['convertedMiles'])) {
                        throw new CheckRetryNeededException(5, 0);
                    }

                    if ($airBound['prices']['milesConversion']['convertedMiles']['base'] === 0) {
                        $this->logger->info("skip airBound $numAirBound: no miles");

                        continue;
                    }
                    $fareFamilyCode = $airBound['fareFamilyCode'];
                    $commercialFareFamily = $fareFamilyWithServices[$fareFamilyCode]['commercialFareFamily'];

                    $currencyProvider = $prices['totalPrices'][0]['currencyCode'];
                    $decimalPlaces = $currency[$currencyProvider]['decimalPlaces'] ?? 1;
                    $taxes = round($prices['totalPrices'][0]['totalTaxes'] / $fields['Adults']) / (10 ** $decimalPlaces);

                    $bookingClass = [];
                    $quotaTickets = [];

                    foreach ($airBound['availabilityDetails'] as $value) {
                        $bookingClass[$value['flightId']] = $value['bookingClass'];
                        $quotaTickets[$value['flightId']] = $value['quota'];
                    }
                    $tickets = min(array_values($quotaTickets));

                    if ($tickets === 0) {
                        $skipZeroTickets = true;
                        $this->logger->info("skip airBound $numAirBound: no tickets");

                        continue;
                    }

                    foreach ($tmp['connections'] as $index => $connection) {
                        $tmp['connections'][$index] = $connection + [
                            'fare_class' => $bookingClass[$connection['flightId']],
                            'tickets'    => $quotaTickets[$connection['flightId']] > 0 ? $quotaTickets[$connection['flightId']] : null,
                        ];

                        $tmp['connections'][$index]['cabin'] = $commercialFareFamily !== 'FIRST' ? strtolower($commercialFareFamily) : 'firstClass';
                    }

                    $tmp = [
                        'classOfService' => $commercialFareFamily,
                        'tickets'        => $tickets,
                        'award_type'     => $dynamicContent[$fareFamilyCode] ?? null,
                        'redemptions'    => [
                            'miles'   => round(($prices['milesConversion']['convertedMiles']['base'] / $fields['Adults'])),
                            'program' => $this->AccountFields['ProviderCode'],
                        ],
                        'payments' => [
                            'currency' => $currencyProvider,
                            'taxes'    => $taxes,
                            'fees'     => null,
                        ],
                    ] + $tmp;
                    $this->logger->debug(var_export($tmp, true));

                    $tmpResults[] = $tmp;
                }
            }

            if (isset($skipZeroTickets)) {
                $this->sendNotification('check skip zero tickets // ZM');
            }

            foreach ($tmpResults as $i => $result) {
                foreach ($result['connections'] as $j => $connection) {
                    if (isset($connection['flightId'])) {
                        unset($tmpResults[$i]['connections'][$j]['flightId']);
                    }
                }
            }
        }
        $this->keepSession(true);

        if (isset($soldOut) && $soldOut && empty($tmpResults)) {
            $this->SetWarning('There is no available flight for this date, please choose another date or restart your search');
        }

        return $tmpResults;
    }

    private function getDynamicContent()
    {
        $this->logger->notice(__METHOD__);
        $dynamicContent = Cache::getInstance()->get('dynamic_content');

        if (!$dynamicContent) {
            try {
                $json = $this->driver->executeScript('
                    var xhr = new XMLHttpRequest();
                    xhr.open("GET", "https://digital.etihad.com' . $this->dynamicContentPath . 'en.json", false);
                    xhr.onreadystatechange = function() {
                        if (this.readyState == 4 && this.status == 200) {
                            responseText = this.responseText;
                        }
                    };
                    xhr.send();
                    return xhr.responseText;'
                );
            } catch (WebDriverException $e) {
                $this->logger->debug($e->getMessage(), ['pre' => true]);

                throw new CheckRetryNeededException(5, 0);
            }

            $dynamicContent = $this->http->JsonLog($json, 0, true);

            if (!$dynamicContent) {
                if (Cache::getInstance()->get('dynamic_content_error') === false) {
                    $this->sendNotification('check dynamicContent (necessary update url)');
                    Cache::getInstance()->set('dynamic_content_error', 1, 60 * 60 * 24);
                }

                return [];
            }

            foreach ($dynamicContent as $key => $value) {
                if (strpos($key, 'ALLP.text.Common.FareFamily.') === false || strpos($key, 'Description') !== false) {
                    unset($dynamicContent[$key]);

                    continue;
                }

                $newKey = str_replace('ALLP.text.Common.FareFamily.', '', $key);
                $dynamicContent[$newKey] = $value;
                unset($dynamicContent[$key]);
            }

            $this->logger->debug(var_export($dynamicContent, true), ['pre' => true]);
            Cache::getInstance()->set('dynamic_content', $dynamicContent, 60 * 60 * 24);
        }

        return $dynamicContent;
    }

    private function loadPage($url)
    {
        try {
            if (strpos($this->http->currentUrl(), 'etihad.com') === false) {
                $this->http->GetURL('https://www.etihad.com/en-us/');

                if ($cookieButton = $this->waitForElement(WebDriverBy::xpath('//button[@id="onetrust-accept-btn-handler"]'),
                    5)) {
                    $cookieButton->click();
                }
            }
            $this->http->GetURL($url);

            $this->saveResponse();

            $badProxy = $this->waitForElement(WebDriverBy::xpath("
                //h1[contains(., 'This site can’t be reached')]
                | //span[contains(text(), 'This page isn’t working')]
                | //p[contains(text(), 'There is something wrong with the proxy server, or the address is incorrect.')]
            "), 0);

            if ($badProxy) {
                throw new CheckRetryNeededException(5, 0);
            }

            if ($this->waitForElement(WebDriverBy::xpath("//h1[contains(., '502 Bad Gateway')]"), 0)) {
                sleep(1);
                $this->http->GetURL($url);

                $badProxy = $this->waitForElement(WebDriverBy::xpath("
                    //h1[contains(., 'This site can’t be reached')]
                    | //span[contains(text(), 'This page isn’t working')]
                    | //p[contains(text(), 'There is something wrong with the proxy server, or the address is incorrect.')]
                "), 0);

                if ($badProxy) {
                    throw new CheckRetryNeededException(5, 0);
                }
            }
        } catch (ScriptTimeoutException | TimeOutException $e) {
            $this->logger->error("ScriptTimeoutException: " . $e->getMessage());

            throw new CheckRetryNeededException(5, 0);
        }

        if ($this->waitForElement(WebDriverBy::id('onetrust-accept-btn-handler'), 0)) {
            $this->driver->executeScript('document.querySelector("#onetrust-accept-btn-handler").click()');
        }

        $this->waitFor(function () {
            return $this->waitForElement(WebDriverBy::xpath("
                    //text()[contains(.,\"As you were browsing something about your browser made us think you were a bot\")]/ancestor::*[1]
                    | //h1[contains(.,\"Service Unavailable\")]
                    | //h1[contains(.,'Pardon Our Interruption') or contains(., 'This site can’t be reached')]
                    | //h1[contains(.,'Choose your flight')]
                    | //span[contains(.,'No flight available')]
                    | //ey-bounds-new//div[contains(text(),'Outbound flight')]
                    | //iframe[contains(@src, '/_Incapsula_Resource?')]
                "), 0);
        }, 10);

        $iframe = $this->waitForElement(WebDriverBy::xpath("//iframe[contains(@src, '/_Incapsula_Resource?')]"), 0,
            true);

        if ($iframe) {
            $this->incapsula($iframe, $iframe->getAttribute('src'));
        }

        if ($this->waitForElement(WebDriverBy::xpath("
                    //span[contains(.,'No flight available')]
                "), 0)) {
            $this->http->GetURL($url);
        }

        if ($body = $this->waitForElement(WebDriverBy::xpath("//body[@class='main-background']"), 1, false)) {
            $this->dynamicContentPath = $body->getAttribute('data-dynamiccontentpath');
            $this->logger->debug($this->dynamicContentPath);
        }

        $iframe = $this->waitForElement(WebDriverBy::xpath("//iframe[contains(@src, '/_Incapsula_Resource?')]"), 0,
            true);

        if ($iframe) {
            throw new CheckRetryNeededException(5, 0);
        }

        $this->saveResponse();

        return true;
    }

    private function getToken(): array
    {
        try {
            $cookies = $this->driver->manage()->getCookies();

            foreach ($cookies as $cookie) {
                if ($cookie['name'] === 'reese84') {
                    $XDToken = $cookie['value'];
                }
            }

            $temp = $this->driver->executeScript('return sessionStorage.getItem("gateway-auth-tokens");');

            if (empty($temp)) {
                throw new CheckRetryNeededException(5, 0);
            } else {
                $temp = $this->http->JsonLog($temp, 0, true);
                $firstKey = array_key_first($temp);

                $bearerToken = $temp[$firstKey]["token"];
            }
        } catch (UnexpectedJavascriptException | WebDriverException | WebDriverCurlException | ErrorException $e) {
            $this->logger->error("Exception: " . $e->getMessage());

            throw new CheckRetryNeededException(5, 0);
        }

        if (empty($bearerToken) || empty($XDToken)) {
            $this->logger->error("Not get token!");

            throw new CheckRetryNeededException(5, 0);
        }

        return [$XDToken, $bearerToken];
    }

    private function getStringFormaterCabin($cabin): string
    {
        switch ($cabin) {
            case 'Business':
                return 'BUSINESS';

            case 'First':
                return 'FIRST';

            default:
                return 'ECONOMY';
        }
    }

    private function getArrayCabinForSearchFlight($cabin): array
    {
        switch ($cabin) {
            case 'First':
                return [
                    ["BUSINESS", "FIRST"],
                    ["ECONOMY"],
                ];

            default:
                return [
                    ["ECONOMY", "BUSINESS"],
                    ["FIRST"],
                ];
        }
    }
}
