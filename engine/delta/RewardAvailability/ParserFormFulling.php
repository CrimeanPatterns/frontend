<?php

namespace AwardWallet\Engine\delta\RewardAvailability;

use AwardWallet\Common\Selenium\BrowserCommunicatorException;
use AwardWallet\Common\Selenium\FingerprintFactory;
use AwardWallet\Common\Selenium\FingerprintRequest;
use AwardWallet\Engine\ProxyList;
use CheckRetryNeededException;
use Facebook\WebDriver\Exception\WebDriverCurlException;
use Facebook\WebDriver\Exception\WebDriverException;
use WebDriverBy;

class ParserFormFulling extends \TAccountChecker
{
    use \SeleniumCheckerHelper;
    use ProxyList;
    public const ATTEMPTS_CNT = 5;

    private $bookingCodes;
    private $routeNotChecked;
    private $cacheKey;

    private $depFullName;
    private $arrFullName;
    private $depCountryCode;
    private $arrCountryCode;
    private $config;
    private $newSession;
    private $responses;
    private $fingerprint;

    public static function getRASearchLinks(): array
    {
        return ['https://www.delta.com/'=>'search page'];
    }

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->UseSelenium();

        $this->KeepState = false;
        $this->keepCookies(false);
        $this->debugMode = $this->AccountFields['DebugState'] ?? false;

        $array = ['es', 'us', 'ca'];
        $targeting = $array[array_rand($array)];
        $this->setProxyGoProxies(null, $targeting, null, null, 'https://www.delta.com');

        $this->http->setHttp2(true);
        $this->usePacFile(false);
//        $this->disableImages();
        $this->useCache();
        $this->http->saveScreenshots = true;
        $this->seleniumOptions->recordRequests = true;
        $this->seleniumOptions->addHideSeleniumExtension = true;

        $this->useChromeExtension();
//                $request = FingerprintRequest::chrome();

//        $chosenResolution = $resolutions[array_rand($resolutions)];
//        $this->setScreenResolution($chosenResolution);
//
//        $request->browserVersionMin = \HttpBrowser::BROWSER_VERSION_MIN;
//        $request->platform = (random_int(0, 1)) ? 'MacIntel' : 'Win32';
//        $fingerprint = $this->services->get(FingerprintFactory::class)->getOne([$request]);
//
//        if (isset($fingerprint)) {
//            $this->http->setUserAgent($fingerprint->getUseragent());
//            $this->seleniumOptions->userAgent = $fingerprint->getUseragent();
//        } else {
//            $this->http->setRandomUserAgent(null, true, false, false);
//        }
//
//        $this->seleniumRequest->setHotSessionPool(self::class, $this->AccountFields['ProviderCode']);
    }

    public function LoadLoginForm()
    {
        return true;
    }

    public function Login()
    {
        return true;
    }

    public function getRewardAvailabilitySettings()
    {
        return [
            'supportedCurrencies'      => ['USD'],
            'supportedDateFlexibility' => 0,
            'defaultCurrency'          => 'USD',
            'supportsPriceCalendar'    => true,
            'priceCalendarCabins'      => ["firstClass", "business", "premiumEconomy", "economy", "unknown"],
        ];
    }

    public function ParseCalendar(array $fields)
    {
        $this->logger->info("Parse Calendar", ['Header' => 2]);
        $this->logger->debug('Params: ' . var_export($fields, true));

        if ($fields['DepDate'] > strtotime('+331 day')) {
            $this->SetWarning('too late');

            return ['fares' => []];
        }

        if (!$this->validRoute($fields)) {
            return ['fares' => []];
        }

        $cabinList = $this->GetCabinFields(false);
        $fields['brandId'] = $cabinList[$fields['Cabin']]['brandID'];

        if ($fields['Currencies'][0] !== 'USD') {
            $fields['Currencies'][0] = $this->getRewardAvailabilitySettings()['defaultCurrency'];
            $this->logger->notice("parse with defaultCurrency: " . $fields['Currencies'][0]);
        }

        $this->loadStartPage();
        $this->filingFlightForm($fields);
        $calendarResponse = $this->getCalendarResponse();

        $calendarResponse = $this->http->JsonLog($calendarResponse, 1, true);

        $this->keepSession(true);

        return ['fares' => $this->parseCalendarJson($calendarResponse, $fields)];
    }

    public function ParseRewardAvailability(array $fields)
    {
        $this->logger->info("Parse Reward Availability", ['Header' => 2]);
        $this->logger->debug("params: " . var_export($fields, true));

        $this->http->RetryCount = 0;

        if (!$fields['ParseCalendar']) {
            if ($fields['DepDate'] > strtotime('+331 day')) {
                $this->SetWarning('too late');

                return [];
            }

            if (empty($this->responses)) {
                if (!$this->validRoute($fields)) {
                    return ['routes' => []];
                }
            }
        }

        return ["routes" =>  []];

//        $cabinList = $this->GetCabinFields(false);
//        $fields['brandId'] = $cabinList[$fields['Cabin']]['brandID'];
//
//        $this->getDataAjax($fields);
//        $this->keepSession(true);

        return ["routes" =>  []];
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

    private function deltaSelectCabinType($cabin)
    {
        switch ($cabin) {
            case 'economy':
                return 'Delta Main Basic';

            case 'premiumEconomy':
                return 'Delta Premium Select';

            case 'business':
                return 'Delta One';

            case 'firstClass':
                return 'Delta First';
        }

        return null;
    }

    private function validRoute($fields)
    {
        $browser = new \HttpBrowser("none", new \CurlDriver());

        $browser->SetProxy("{$this->http->getProxyAddress()}:{$this->http->getProxyPort()}");
        $browser->setProxyAuth($this->http->getProxyLogin(), $this->http->getProxyPassword());
        $browser->setUserAgent($this->http->getDefaultHeader("User-Agent"));
        $this->http->brotherBrowser($browser);

        $browser->RetryCount = 0;

        $browser->GetURL("https://www.delta.com/predictivetext/getPredictiveCities?code=" . $fields['DepCode'], [], 20);

        $browser->setCookie('DELTA_ENSIGHTEN_PRIVACY_BANNER_VIEWED', '1', '.delta.com');
        $browser->setCookie('DELTA_ENSIGHTEN_PRIVACY_MODAL_VAL', '1', '.delta.com');
        $browser->setCookie('DELTA_ENSIGHTEN_PRIVACY_Advertising', '1', '.delta.com');
        $browser->setCookie('DELTA_ENSIGHTEN_PRIVACY_Required', '1', '.delta.com');

        if ($browser->currentUrl() === 'https://www.delta.com/content/www/en_US/system-unavailable1.html'
            || $browser->Response['code'] == 403) {
            // it's work
            $browser->GetURL("https://www.delta.com/predictivetext/getPredictiveCities?code=" . $fields['DepCode'], [], 20);
        }
        $data = $browser->JsonLog(null, 1, false, 'listOfCities');

        if (strpos($browser->Error, 'Network error 56 - Received HTTP code 407 from proxy after CONNECT') !== false
            || strpos($browser->Error, 'Network error 56 - Received HTTP code 503 from proxy after CONNECT') !== false
            || strpos($browser->Error, 'Network error 56 - Received HTTP code 400 from proxy after CONNECT') !== false
            || strpos($browser->Error, 'Network error 56 - Received HTTP code 403 from proxy after CONNECT') !== false
            || strpos($browser->Error, 'Network error 28 - Operation timed out after ') !== false
            || strpos($browser->Error, 'Network error 28 - Connection timed out after') !== false
            || strpos($browser->Error, 'Network error 7 - Failed to connect to') !== false
            || $browser->Response['code'] == 403
            || $browser->currentUrl() === 'https://www.delta.com/content/www/en_US/system-unavailable1.html'
        ) {
            $this->markProxyAsInvalid();

            throw new CheckRetryNeededException(self::ATTEMPTS_CNT, 0);
        }

        if (!isset($data->listOfCities) || !is_array($data->listOfCities)) {
            // try main request anyway
            $this->routeNotChecked = true;

            return true;
        }

        if (empty($data->listOfCities)) {
            $this->SetWarning('no flights from ' . $fields['DepCode']);

            return false;
        }
        $noCode = true;

        foreach ($data->listOfCities as $city) {
            if ($city->airportCode === $fields['DepCode']) {
                $noCode = false;
                $this->depFullName = $browser->FindPreg("/^(.+?)\s*\({$fields['DepCode']}\)$/", false, $city->airportFullName);
                $this->depCountryCode = $city->countryCode;
            }
        }

        if ($noCode) {
            $this->SetWarning('no flights from ' . $fields['DepCode']);

            return false;
        }
        //$this->http->removeCookies();
        $browser->GetURL("https://www.delta.com/predictivetext/getPredictiveCities?code=" . $fields['ArrCode']);
        $data = $browser->JsonLog(null, 1, false, 'listOfCities');

        if (!isset($data->listOfCities) || !is_array($data->listOfCities)) {
            // try main request anyway
            $this->routeNotChecked = true;

            return true;
        }

        if (empty($data->listOfCities)) {
            $this->SetWarning('no flights to ' . $fields['ArrCode']);

            return false;
        }
        $noCode = true;

        if (is_array($data->listOfCities)) {
            foreach ($data->listOfCities as $city) {
                if ($city->airportCode === $fields['ArrCode']) {
                    $noCode = false;
                    $this->arrFullName = $this->http->FindPreg("/^(.+?)\s*\({$fields['ArrCode']}\)$/", false, $city->airportFullName);
                    $this->arrCountryCode = $city->countryCode;
                }
            }
        }

        if ($noCode) {
            $this->SetWarning('no flights to ' . $fields['ArrCode']);

            return false;
        }

        return true;
    }

    private function GetCabinFields($onlyKeys = true): array
    {
        // если брать BE - то светит всё.. по другим от и выше
        $array = [
            'economy' => [
                // for requests
                'brandID'     => 'BE',
                'listBrandID' => ['BE', 'MAIN'],
                //just info
                'award' => ['Basic Economy' => ['brandID' => 'BE'], 'Main Cabin' => ['brandID' => 'MAIN']],
            ],
            'premiumEconomy' => [
                'brandID'     => 'BE',
                //'brandID'     => 'MAIN', // если надо будет всё ж отсекать, то лучше так
                'listBrandID' => ['DCP', 'DPPS'],
                'award'       => ['Delta Comfort+' => ['brandID' => 'DCP'], 'Premium Select' => ['brandID' => 'DPPS']],
            ],
            'firstClass' => [// выбор любого показывает 2 результата
                'brandID'     => 'BE',
                //'brandID'     => 'DPPS',
                'listBrandID' => ['FIRST', 'D1'],
                'award'       => ['First Class' => ['brandID' => 'FIRST'], 'Delta One' => ['brandID' => 'D1']],
            ],
            'business' => [// выбор любого показывает 2 результата
                'brandID'     => 'BE',
                //'brandID'     => 'DPPS',
                'listBrandID' => ['FIRST', 'D1'],
                'award'       => [],
            ],
        ];

        if ($onlyKeys) {
            return array_keys($array);
        }

        return $array;
    }

    private function loadStartPage()
    {
        $this->logger->notice(__METHOD__);

        try {
            try {
                $this->http->GetURL("https://www.delta.com/us/en");
            } catch (\UnexpectedAlertOpenException $e) {
                $this->logger->error("exception: " . $e->getMessage());
                $this->http->GetURL("https://www.delta.com/us/en");
            }

            if ($this->http->FindPreg('/(?:page isn’t working|There is no Internet connection|This site can’t be reached|Access Denied|No internet)/ims')) {
                throw new \CheckRetryNeededException(self::ATTEMPTS_CNT, 0);
            }
            $startTime = time();

            $logoImg = $this->waitForElement(\WebDriverBy::xpath("//img[@alt='Delta Air Lines']"), 20, false);

            if ((time() - $startTime) > 40) {
                $this->logger->warning('page hangs up');

                throw new \CheckRetryNeededException(self::ATTEMPTS_CNT, 0);
            }

            if (!$logoImg) {
                if ($message = $this->http->FindPreg("/An error occurred while processing your request\.<p>/")) {
                    $this->logger->error($message);

                    throw new \CheckException(self::PROVIDER_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
                }

                $this->driver->executeScript('window.stop();');
                $this->http->GetURL("https://www.delta.com/us/en");
            }

            if ($this->waitForElement(\WebDriverBy::id("gdpr-banner-blurred-background"), 0)) {
                $this->driver->executeScript("document.querySelector('#gdpr-banner-blurred-background').style.display = 'none'");
            }
        } catch (\ScriptTimeoutException | \TimeOutException $e) {
            $this->logger->error("ScriptTimeoutException: " . $e->getMessage());

            // retries
            if (ConfigValue(CONFIG_SITE_STATE) != SITE_STATE_DEBUG) {
                $this->logger->debug("[attempt]: {$this->attempt}");

                throw new \CheckRetryNeededException(self::ATTEMPTS_CNT, 0);
            }
        }

        $this->driver->manage()->window()->maximize();
    }

    private function filingFlightForm($fields): void
    {
        $this->logger->notice(__METHOD__);

        try {
            $this->logger->debug('Click "Advanced Search"');
            $this->driver->executeScript(/** @lang JavaScript */ "
                document.getElementById('adv-search').click();
            ");

            $this->waitForElement(WebDriverBy::xpath('//span[@id="faresFor-val"]'), 20);

            $this->logger->debug('Select Miles');
            $this->driver->executeScript(/** @lang JavaScript */ "
                document.getElementById('shopWithMiles').click();
            ");

            $this->selectAirport($fields['DepCode'], 'fromAirportName');
            $this->selectAirport($fields['ArrCode'], 'toAirportName');

            $this->logger->debug('Select tripType');
            $this->driver->executeScript(/** @lang JavaScript */ "
                document.getElementById('selectTripType-val').click();
                document.getElementById('ui-list-selectTripType1').click();
            ");

            $this->logger->debug('Select date');
            $dateLabel = date('m/d/Y', $fields['DepDate']);
            $this->driver->executeScript(/** @lang JavaScript */ "
                document.getElementById('calDepartLabelCont').click();
            ");

            while (!$this->waitForElement(WebDriverBy::xpath("//a[contains(@data-date, '{$dateLabel}')]"), 2)) {
                $this->driver->executeScript(/** @lang JavaScript */ "
                document.querySelector('a[aria-label=\"Next\"]').click();
            ");
            }
            $this->driver->executeScript(/** @lang JavaScript */ "
                document.evaluate('//a[contains(@data-date, \"{$dateLabel}\")]', document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue.click();
                document.querySelector('button[class=\"donebutton\"]').click();
            ");

            $this->logger->debug('Select Adults');
            $this->driver->executeScript(/** @lang JavaScript */ "
                document.getElementById('passengers-val').click();
                document.getElementById('ui-list-passengers" . ($fields['Adults'] - 1) . "').click();
            ");

            $this->logger->debug('Select Cabin');
            $this->driver->executeScript(/** @lang JavaScript */ "
                document.getElementById('faresFor-val').click();
            ");
            $this->driver->executeScript(/** @lang JavaScript */ "
            document.evaluate('//li[contains(text(), \"{$this->deltaSelectCabinType($fields['Cabin'])}\")]', document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue.click();
        ");
            $this->saveResponse();
        } catch (WebDriverException | WebDriverCurlException | \UnexpectedJavascriptException $e) {
            $this->logger->error($e->getMessage());

            throw new CheckRetryNeededException(5, 0);
        }
    }

    private function selectAirport($code, $id)
    {
        $this->logger->debug('Click "Airport"');
        $this->driver->executeScript(/** @lang JavaScript */ "
            document.getElementById('{$id}').click();
        ");

        if (!$inputAirport = $this->waitForElement(WebDriverBy::xpath('//input[@id="search_input"]'), 5)) {
            $this->logger->error('Element not Loaded', ['Header' => 4]);

            throw new CheckRetryNeededException(5, 0);
        }

        $inputAirport->clear();
        $inputAirport->sendKeys($code);

        $this->waitForElement(WebDriverBy::xpath("//span[contains(text(), '{$code}')]"), 5);
        $this->logger->debug('Click "Airport Name"');
        $this->driver->executeScript(/** @lang JavaScript */ "
            document.evaluate('//span[contains(text(), \"{$code}\")]', document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue.click();
        ");

        $this->saveResponse();
    }

    private function getCalendarResponse()
    {
        $this->logger->notice(__METHOD__);

        $this->logger->debug('SUBMIT!');
        $this->driver->executeScript(/** @lang JavaScript */ "
                document.getElementById('btnSubmit').click();
            ");

        $this->waitForElement(WebDriverBy::xpath('//a[@id="tab1"]'), 40);

        $this->saveResponse();
        $this->logger->debug('CALENDAR!');
        $this->driver->executeScript(/** @lang JavaScript */ "
                document.getElementById('tab1').click();
            ");

        $this->waitForElement(WebDriverBy::xpath('(//div[@class="miles-value"])[1]'), 30);
        $this->saveResponse();

        /** @var \SeleniumDriver $seleniumDriver */
        $seleniumDriver = $this->http->driver;

        try {
            $requests = $seleniumDriver->browserCommunicator->getRecordedRequests();
        } catch (BrowserCommunicatorException $e) {
            $this->logger->error('BrowserCommunicatorException: ' . $e->getMessage());

            throw new \CheckRetryNeededException(5, 0);
        }
        $response = null;

        foreach ($requests as $n => $xhr) {
            if (strpos($xhr->request->getUri(), 'prd/rm-offer-gql') !== false
                && strpos(json_encode($xhr->request->getBody()), 'calenderDateRequest') == false) {
                $response = json_encode($xhr->response->getBody());

                continue;
            }

            if (isset($response)) {
                break;
            }
        }

        $this->logger->debug(var_export($response, true), ['pre' => true]);

        return $response;
    }

    private function parseCalendarJson($calendar, $fields)
    {
        $this->logger->notice(__METHOD__);
        $result = [];

        if (isset($calendar['errors'])) {
            if ($calendar['errors'][0]['message'] == 'RetailOfferError') {
                $this->SetWarning('(Calendar) ' . $calendar['errors'][0]['extensions']['errors']['message']);

                return [];
            }
        }

        if (empty($calendar['data'])) {
            $this->logger->warning("No has data");

            throw new \CheckRetryNeededException(5, 0);
        }

        foreach ($calendar['data']['gqlSearchOffers']['gqlOffersSets'] as $data) {
            foreach ($data['offers'] as $offer) {
                if ($offer['soldOut'] || $offer['additionalOfferProperties']['offered'] === false) {
                    continue;
                }

                $dateTime = new \DateTime($offer['offerItems'][0]['retailItems'][0]['retailItemMetaData']['fareInformation'][0]['priceCalendar']['priceCalendarDate']);

                $result[] = [
                    'date'        => $dateTime->format('Y-m-d'),
                    'redemptions' => ['miles' => $offer['offerPricing'][0]['totalAmt']['milesEquivalentPrice']['mileCnt']],
                    'payments'    => [
                        'currency' => $calendar['data']['gqlSearchOffers']['offerDataList']['pricingOptions'][0]['pricingOptionDetail']['currencyCode'],
                        'taxes'    => $offer['offerPricing'][0]['totalAmt']['currencyEquivalentPrice']['roundedCurrencyAmt'],
                        'fees'     => null,
                    ],
                    'cabin'            => $fields['Cabin'],
                    'brandedCabin'     => $this->deltaSelectCabinType($fields['Cabin']),
                ];
            }
        }

        $this->logger->debug(var_export($result, true), ['pre' => true]);

        if (empty($result)) {
            $this->SetWarning('There are no flights for the this month');
        }

        return $result;
    }
}
