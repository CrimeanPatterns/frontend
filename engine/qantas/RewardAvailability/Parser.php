<?php

namespace AwardWallet\Engine\qantas\RewardAvailability;

use AwardWallet\Common\Selenium\BrowserCommunicatorException;
use AwardWallet\Common\Selenium\FingerprintFactory;
use AwardWallet\Common\Selenium\FingerprintRequest;
use CheckRetryNeededException;
use SeleniumFinderRequest;

class Parser extends \TAccountCheckerQantas
{
    use \PriceTools;
    use \SeleniumCheckerHelper;

    public $isRewardAvailability = true;
    public static $useNew = false;

    public $calendar;
    private $debugMode = false;
    private $otherRegion = false;

    // NB: пока не все маршруты без авторизации. (надо проверять)
    private $seleniumAuth = true;
    private $checkNewFormat;
    /** @var \HttpBrowser */
    private $curl;
    private $noFligths = false;
    private $proxyRegion;
    private $tabId = null;

    private $XMLRequest = true;

    private $enc;
    private $response;

    public static function getRASearchLinks(): array
    {
        return ['https://www.qantas.com/au/en.html' => 'search page'];
    }

    public static function GetAccountChecker($accountInfo)
    {
//        $debug = $accountInfo['DebugState'] ?? false;
//
//        if (!$debug) {
        return new static();
//        }

//        require_once __DIR__ . "/ParserOld.php";
//
//        return new ParserOld();
    }

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->UseSelenium();
        $this->debugMode = $this->AccountFields['DebugState'] ?? false;

        $countries = ['au'];
        $this->proxyRegion = $countries[array_rand($countries)];
        $this->setProxyGoProxies(null, $this->proxyRegion, null, null, 'https://www.qantas.com/au/en');

        $this->seleniumOptions->recordRequests = true;
        $this->http->saveScreenshots = true;
//        $this->disableImages();
//        $this->useCache();
        $this->keepCookies(false);
        $this->usePacFile(false);
        $this->KeepState = false;

        $offFingerPrint = false;

        $setBrowser = $this->debugMode ? 0 : rand(2, 4);

        switch ($setBrowser) {
            case 0:
                $this->useCamoufox();
                $offFingerPrint = true;
                $this->XMLRequest = true;
                $this->useCache();

                break;

            case 1:
                $this->useFirefoxPlaywright(SeleniumFinderRequest::FIREFOX_PLAYWRIGHT_101);
                $request = FingerprintRequest::firefox();

                $this->seleniumRequest->setOs(\SeleniumFinderRequest::OS_MAC);
                $this->seleniumOptions->addHideSeleniumExtension = false;
                $this->seleniumOptions->addPuppeteerStealthExtension = false;
//                $this->seleniumOptions->userAgent = null;
                $this->XMLRequest = true;

                break;

            case 2:
                $this->useChromePuppeteer(\SeleniumFinderRequest::CHROME_PUPPETEER_100);
                $request = FingerprintRequest::chrome();

                $this->seleniumRequest->setOs(\SeleniumFinderRequest::OS_MAC);
                $this->seleniumOptions->addHideSeleniumExtension = false;
                $this->seleniumOptions->addPuppeteerStealthExtension = false;
//                $this->seleniumOptions->userAgent = null;
                $this->XMLRequest = false;

                break;

            case 3:
                $this->useGoogleChrome(SeleniumFinderRequest::CHROME_94);
                $offFingerPrint = true;

                $this->seleniumRequest->setOs(\SeleniumFinderRequest::OS_MAC);
                $this->seleniumOptions->addHideSeleniumExtension = false;
                $this->seleniumOptions->addPuppeteerStealthExtension = false;
                $this->seleniumOptions->userAgent = null;
                $this->XMLRequest = false;

                break;

            default:
                $this->useChromePuppeteer();
                $request = FingerprintRequest::chrome();

                $this->seleniumRequest->setOs(\SeleniumFinderRequest::OS_MAC);
                $this->seleniumOptions->addHideSeleniumExtension = false;
                $this->seleniumOptions->addPuppeteerStealthExtension = false;
//                $this->seleniumOptions->userAgent = null;
                $this->XMLRequest = false;

                break;
        }

        if ($setBrowser > 0) {
            $resolutions = [
                [1360, 768],
                [1366, 768],
                [1920, 1080],
            ];

            $chosenResolution = $resolutions[array_rand($resolutions)];
            $this->setScreenResolution($chosenResolution);
        }

        if (!$offFingerPrint) {
            $request->browserVersionMax = 132;
//            $request->platform = 'Win32';
            $fingerprint = $this->services->get(FingerprintFactory::class)->getOne([$request]);

            if (isset($fingerprint)) {
                $this->seleniumOptions->fingerprint = $fingerprint->getFingerprint();
                $this->http->setUserAgent($fingerprint->getUseragent());
            }
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
        $this->checkBeforeStart($this->AccountFields['RaRequestFields']);

        if ($this->noFligths) {
            return true;
        }
        // TODO temp
        //throw new \CheckException('We are unable to process this request currently. Please try again later',
        //    ACCOUNT_PROVIDER_ERROR);

        if ($this->seleniumAuth) {
//            if ($this->debugMode) {
//                return $this->loginOldDesign();
//            }

            throw new \CheckException('We are unable to process this request currently. Please try again later', ACCOUNT_PROVIDER_ERROR);
        }

        return true;
    }

    public function getRewardAvailabilitySettings()
    {
        return [
            'supportedCurrencies'      => ['USD', 'AUD'],
            'supportedDateFlexibility' => 0, // 1
            'defaultCurrency'          => 'USD',
            'priceCalendarCabins'      => ["firstClass", "business", "premiumEconomy", "economy", "unknown"],
        ];
    }

    public function ParseCalendar(array $fields)
    {
        $this->logger->info("Parse Calendar", ['Header' => 2]);
        $this->logger->debug('Params: ' . var_export($fields, true));

        if ($fields['DepDate'] > strtotime("+353 days")) {
            $this->SetWarning('The requested departure date is too late.');

            return ['fares' => []];
        }

        $this->loadStartPage($fields);
        $this->parseData($fields);

        $this->logger->debug(var_export($this->calendar, true), ['pre' => true]);

        return ["fares" => $this->calendar];
    }

    public function ParseRewardAvailability(array $fields)
    {
        $this->logger->info("Parse Reward Availability", ['Header' => 2]);
        $this->logger->debug("params: " . var_export($fields, true));
        $this->logger->debug("noFligths: " . var_export($this->noFligths, true));

        if ($this->noFligths) {
            return ['routes' => []];
        }

        if ($fields['DepDate'] > strtotime("+353 days")) {
            $this->SetWarning('The requested departure date is too late.');

            return ['routes' => []];
        }

        try {
            if (strpos($this->http->currentUrl(),
                    'https://book.qantas.com/qf-booking/dyn/air/booking/FFCO') === false) {
                $this->loadStartPage($fields);
            }
        } catch (\WebDriverCurlException | \WebDriverException $e) {
            $this->logger->error($e->getMessage());

            throw new CheckRetryNeededException(5, 0);
        }

        try {
            if (empty($this->response)) {
                $data = $this->parseData($fields);
            } else {
                $data = $this->response;
            }
        } catch (\ErrorException $e) {
            $this->logger->error($e->getMessage());

            throw new \CheckRetryNeededException(5, 0);
        } catch (\StaleElementReferenceException | \Facebook\WebDriver\Exception\StaleElementReferenceException $e) {
            $this->logger->error('StaleElementReferenceException: ' . $e->getMessage());

            throw new \CheckRetryNeededException(5, 0);
        } catch (\UnrecognizedExceptionException | \WebDriverCurlException | \UnknownServerException | \TimeoutException
        | \NoSuchDriverException | \Facebook\WebDriver\Exception\WebDriverCurlException | \Facebook\WebDriver\Exception\WebDriverException  $e
        ) {
            $this->logger->error('Exception: ' . $e->getMessage());

            throw new \CheckRetryNeededException(5, 0);
        }

        if ($this->noFligths) {
            return ['routes' => []];
        }

        if (is_array($data) && empty($data) && $this->ErrorCode === ACCOUNT_WARNING) {
            return ['routes' => $data];
        }

        if (is_array($data) && !empty($data)) {
            return ['routes' => $data];
        }

        if (!$this->seleniumAuth && $data) {
            $dataPrice = $this->http->JsonLog($data, 1);

            if (is_array($this->checkBackupToSearch($dataPrice))) {
                return [];
            }

            $this->setCookieFromSelenium($this->curl);

            $res = $this->parseRewardFlights($fields, true, $data);

            return ['routes' => $res];
        }

        if ($this->seleniumAuth && is_null($data)) {
            $res = $this->parseRewardFlights($fields);

            if (empty($res)) {
                $this->SetWarning('This date no flights');
            }

            return ['routes' => $res];
        }

        throw new \CheckException('Something went wrong', ACCOUNT_ENGINE_ERROR);
    }

    public function isBadProxy($browser = null): bool
    {
        if (!isset($browser)) {
            $browser = $this->http;
        }

        return
            strpos($browser->Error, 'Network error 28 - Connection timed out after') !== false
            || strpos($browser->Error, 'Network error 56 - Proxy CONNECT aborted') !== false
            || strpos($browser->Error, 'Network error 56 - Unexpected EOF') !== false
            || strpos($browser->Error, 'Network error 56 - Received HTTP code 400 from proxy after CONNECT') !== false
            || strpos($browser->Error, 'Network error 56 - Received HTTP code 403 from proxy after CONNECT') !== false
            || strpos($browser->Error, 'Network error 56 - Received HTTP code 503 from proxy after CONNECT') !== false
            || strpos($browser->Error, 'Network error 56 - Received HTTP code 502 from proxy after CONNECT') !== false
            || strpos($browser->Error, 'Network error 0 -') !== false
            || $browser->Response['code'] == 403;
    }

    private function parseData($fields)
    {
        $this->logger->notice(__METHOD__);

        try {
            if ($this->http->FindPreg('/(?:page isn’t working|There is no Internet connection|This site can’t be reached|Access denied|>No internet<\/span>)/ims')) {
                throw new \CheckRetryNeededException(5, 0);
            }

            $port = $this->waitForElement(\WebDriverBy::xpath('//div[@data-testid="departure-port"]'), 25);

            if (!$port) {
                throw new \CheckRetryNeededException(5, 0);
            }

            try {
                $resForm = $this->searchFormScript($fields);

                if ($resForm === false) {
                    return [];
                }

                if ($this->XMLRequest) {
                    $data = $this->runFetch($fields);
                    $this->calendar = $this->runFetchCalendar($fields);

                    return $data;
                }

                if (!$this->seleniumAuth && !$this->noFligths) {
                    $loadedXpath = "(//strong[contains(normalize-space(),'Total duration')])[1] 
                        | //span[contains(normalize-space(), 'There are no flights within 6 days of your searched dates')] 
                        | (//span[contains(normalize-space(text()),'Flights are not available on this date. See other dates above.')])[1] 
                        | (//*[self::h5 or self::strong or self::div][contains(normalize-space(text()),'It looks like something went wrong there')])[1]
                        | //p[contains(., 'Sorry, an error seems to have occurred, and this page cannot be displayed')]
                        | //span[contains(., 'I agree to the collection and use of personal information as indicated above.')]
                        | (//div[@class = 'cheapest-fare-cell'])[1]";

                    try {
                        $isLoaded = $this->waitFor(function () use ($loadedXpath) {
                            return $this->waitForElement(\WebDriverBy::xpath($loadedXpath),
                                0);
                        }, 20);

                        if (strpos($this->http->currentUrl(), "errorCodes=") !== false) {
                            $this->SetWarning("We're having trouble finding flight options that match your search. Try another date.");

                            return [];
                        }

                        if ($this->waitForElement(\WebDriverBy::xpath('//span[contains(., "I agree to the collection and use of personal information as indicated above.")]'), 0)) {
                            $data = $this->runFetch($fields);
                            $this->calendar = $this->runFetchCalendar($fields);

                            return $data;
                        }

                        if ($msg = $this->http->FindSingleNode("
                            (//span[contains(normalize-space(text()),'Flights are not available on this date. See other dates above.')])[1]
                            | (//*[self::h5 or self::strong or self::div][contains(normalize-space(text()),'It looks like something went wrong there')])[1]
                        ")) {
                            $this->SetWarning($msg);

                            return [];
                        }

                        if ($this->http->FindSingleNode('//p[contains(., "Sorry, an error seems to have occurred, and this page cannot be displayed")]')) {
                            throw new \CheckRetryNeededException(2, 0);
                        }

                        if ($mess = $this->http->FindSingleNode('//span[@msg-src-key= "ALLP.text.NoFlightAvailable"] | //h3[@msg-src-key="ALLP.text.flightFilters.noMatchingFlights"] | //span[contains(normalize-space(), "There are no flights within 6 days of your searched dates")]')) {
                            $this->SetWarning($mess);

                            return [];
                        }

                        if ($fields['ParseCalendar']) {
                            $this->waitForElement(\WebDriverBy::xpath('(//span[@class="rewards-date date"])[1]'), 30);
                        }

                        if ($isLoaded) {
                            $result = $this->getXhrRequests($fields);
                            $this->calendar = $this->runFetchCalendar($fields);
                        } else {
                            $this->saveResponse();

                            $data = $this->runFetch($fields);
                            $this->calendar = $this->runFetchCalendar($fields);

                            return $data;
                        }
                    } catch (\WebDriverException $e) {
                        $this->logger->error('Exception: ' . $e->getMessage());

                        $this->saveResponse();

                        throw new \CheckRetryNeededException(5, 0);
                    }
                }

                if (is_array($resForm)) {
                    return $resForm;
                }
            } catch (\WebDriverException $e) {
                $this->logger->error('Exception: ' . $e->getMessage());

                $data = $this->runFetch($fields);
                $this->calendar = $this->runFetchCalendar($fields);

                return $data;
            }

            /*            if (!$this->seleniumAuth && !$this->noFligths) {
                        try {
                            $isLoaded = $this->waitFor(function () {
                                return $this->waitForElement(\WebDriverBy::xpath("(//strong[contains(normalize-space(),'Total duration')])[1]"),
                                        0);
                            }, 20);

                            if ($isLoaded) {
                                $result = $this->getXhrRequests();
                            } else {
                                throw new \CheckRetryNeededException(5, 0);
                            }
                        } catch (\WebDriverException $e) {
                            $this->logger->error('Exception: ' . $e->getMessage());

                            throw new \CheckRetryNeededException(5, 0);
                        }
                        }
            */
        } catch (\TimeOutException $e) {
            $this->logger->error("TimeoutException: " . $e->getMessage());
            $this->driver->executeScript('window.stop();');
            $timeout = true;
        } catch (\UnexpectedJavascriptException $e) {
            $this->logger->error("UnexpectedJavascriptException: " . $e->getMessage());
            $this->driver->executeScript('window.stop();');
        } catch (\NoSuchElementException $e) {
            $this->logger->error('NoSuchElementException: ' . $e->getMessage());

            throw new \CheckRetryNeededException(5, 0);
        }

        return $result ?? null;
    }

    private function searchFormScript($fields): ?bool
    {
        $this->logger->notice(__METHOD__);

        if (strpos($this->http->currentUrl(), 'https://book.qantas.com/qf-booking/dyn/air/booking/FFCO') !== false) {
            $this->logger->debug('Flights have already been selected');

            return true;
        }

        if (!$this->waitForElement(\WebDriverBy::xpath('//form[@action="https://book.qantas.com/qf-booking/dyn/air/tripflow.redirect"]'),
            0)) {
            throw new \CheckRetryNeededException(5, 0);
        }

        // Close modal dialog that prevents clicking on Trip Type Menu selector
        if ($this->http->FindSingleNode("//div[@id='react-aria-modal-dialog'] ")
            && $this->http->FindSingleNode("//button[@id='modalBtnClose']")
        ) {
            $this->checkWrapper();
        }

        $this->saveResponse();
        $this->driver->executeScript(/** @lang JavaScript */ "
               document.querySelector('form[action=\"https://book.qantas.com/qf-booking/dyn/air/tripflow.redirect\"]').click();
        ");
        $this->logger->debug("Open Form");

        if ($this->waitForElement(\WebDriverBy::xpath('//div[@class="css-kx5i2d-runway-popup-field__placeholder-LargeButton"][text()="Where from?"]'),
            0)) {
            throw new \CheckRetryNeededException(5, 0);
        }

        if ($this->waitForElement(\WebDriverBy::xpath('//div[@class="css-kx5i2d-runway-popup-field__placeholder-LargeButton"][text()="Where to?"]'),
            0)) {
            $this->SetWarning("No flights in this direction");

            return false;
        }

        if ($this->waitForElement(\WebDriverBy::xpath("//div[text()='Select travel dates']"), 0)) {
            $this->driver->executeScript(/** @lang JavaScript */ "document.querySelector('button[class=\"css-1lllks9-runway-popup-field__button\"]').click();");

            $dateLabel = date('Y-m-d', $fields['DepDate']);

            if ($this->waitForElement(\WebDriverBy::xpath("//button[@data-testid='{$dateLabel}'][@tabindex='-1']"),
                3)) {
                $this->SetWarning('This date no flights');

                $this->noFligths = true;

                return false;
            }
        }

        $this->saveResponse();

        $this->saveResponse();
        $this->driver->executeScript(/** @lang JavaScript */ "
            document.querySelector('button[aria-label=\"Trip Type Menu, Return selected\"]').click();
        ");

        sleep(1);
        $this->driver->executeScript(/** @lang JavaScript */ "
            document.getElementById('trip-type-item-1').click();
        ");
        $this->logger->debug("One Way");
        $this->saveResponse();

//        if ($this->debugMode) {
//            $this->saveResponse();
//            $this->driver->executeScript(/** @lang JavaScript */ "
//            document.querySelector('button[aria-label=\"Travel Class Menu, Economy selected\"]').click();");
//
//            sleep(1);
//            $this->driver->executeScript(/** @lang JavaScript */ "
//            document.getElementById('travel-class-item-3').click();");
//            $this->logger->debug("Business class test");
//            $this->saveResponse();
//        }

        $dateOnPage = $this->http->FindSingleNode('//input[@name="travelDates"]/@value');
        $this->logger->debug("dataChecker: " . $dateOnPage);

        if ($subButton = $this->waitForElement(\WebDriverBy::xpath('//div[@data-testid="search-flights-btn"]//button'), 0)) {
            $this->logger->debug('submit');

            try {
                $this->driver->executeScript("document.querySelector('button[class=\"css-1mza773-baseStyles-baseStyles-baseStyles-solidStyles-solidStyles-solidStyles-Button\"]').click();");
            } catch (\WebDriverException $e) {
                $this->logger->error($e->getMessage());

                throw new \CheckRetryNeededException(5, 0);
            }
        } else {
            $this->saveResponse();
            $this->logger->error('some bug on page. can\'t get submit button');

            throw new \CheckRetryNeededException(5, 0);
        }

//        $this->waitForElement(\WebDriverBy::xpath('//div[@class="multi-search-form"]/@class'), 5);

        if ($this->waitForElement(\WebDriverBy::xpath("//h1[normalize-space()='Access Denied'] | //span[contains(., 'This site can’t be reached')] | //span[contains(., 'No internet')]"),
            10)) {
            $this->markProxyAsInvalid();

            throw new \CheckRetryNeededException(5, 0);
        }
        $this->saveResponse();

        if ($this->http->FindSingleNode("//h1[normalize-space()='Access Denied']")) {
            $this->markProxyAsInvalid();

            throw new \CheckRetryNeededException(5, 0);
        }

        if ($this->http->FindSingleNode("//h1[contains(., 'an error at our end')]")) {
            throw new \CheckRetryNeededException(5, 0);
        }

        $this->waitForElement(\WebDriverBy::xpath("//h1[normalize-space()='Important Information'] | //span[contains(., 'Rewards only')] | (//strong[contains(normalize-space(),'Total duration')])[1] | (//div[@class = 'cheapest-fare-cell'])[1]"),
            20);

        $this->saveResponse();

        if ($this->http->FindSingleNode("//h1[normalize-space()='Important Information']")) {
            $this->driver->executeScript("
                    document.querySelector('#btn-qf-continue').scrollIntoView();
                ");

            if ($continue = $this->waitForElement(\WebDriverBy::id('btn-qf-continue'), 5)) {
                $continue->click();
            }

            if ($this->http->FindPreg('/(?:page isn’t working|There is no Internet connection|This site can’t be reached|Access denied|>No internet<\/span>)/ims')) {
                throw new \CheckRetryNeededException(5, 0);
            }
        }
        $this->saveResponse();

        return null;
    }

    private function runFetch($fields)
    {
        $this->logger->notice(__METHOD__);

        if ($msg = $this->http->FindSingleNode('//p[contains(., "Sorry, an error seems to have occurred, and this page cannot be displayed")]')) {
            throw new \CheckRetryNeededException(2, 0);
//            throw new \CheckException($msg, ACCOUNT_PROVIDER_ERROR);
        }

        if ($this->waitForElement(\WebDriverBy::xpath('//span[contains(., "Rewards")]'), 10)) {
            if (!$this->XMLRequest) {
                return $this->getXhrRequests($fields);
            }
        }

        $this->saveResponse();

        if ($mess = $this->http->FindSingleNode('//span[@msg-src-key= "ALLP.text.NoFlightAvailable"] | //h3[@msg-src-key="ALLP.text.flightFilters.noMatchingFlights"]')) {
            $this->SetWarning($mess);

            return [];
        }

        $asyncForm = $this->waitForElement(\WebDriverBy::xpath('//form[@id="ASYNC_AVAIL_FORM"]'), 1);

        if (strpos($this->http->currentUrl(), "errorCodes=") !== false) {
            $this->SetWarning("We're having trouble finding flight options that match your search. Try another date.");

            return [];
        }

        if (!$asyncForm) {
            $this->waitForElement(\WebDriverBy::xpath('//span[contains(., "Rewards")]'), 10);

            try {
                $this->tabId = $this->driver->executeScript('return window.modelInput.tabSessionId');
            } catch (\WebDriverException $e) {
                throw new \CheckRetryNeededException(5, 0);
            }
            $this->logger->debug("TAB_ID: " . $this->tabId);
        } else {
            $parsed_url = parse_url($asyncForm->getAttribute("action"));
            parse_str($parsed_url['query'], $query_params);
            $this->tabId = $query_params['TAB_ID'];
        }

        $payload = [
            "WDS_DMIN_FILTER"             => "UPSELL_INT",
            "WDS_BUILD_DMIN_FROM_SESSION" => "TRUE",
            "WDS_OLD_TAB_ID"              => "",
            "SITE"                        => "QFQFQFBW",
            "LANGUAGE"                    => "GB",
            "SKIN"                        => "P",
            "TAB_ID"                      => $this->tabId,
        ];

        $payload = http_build_query($payload);

        if ($this->XMLRequest) {
            $temp = $this->driver->executeScript('
                var xhr = new XMLHttpRequest();
                xhr.withCredentials = true;
                xhr.open("POST", "https://book.qantas.com/qf-booking/dyn/air/booking/flexPricerAvailabilityActionFromLoad", false);
                xhr.setRequestHeader("Accept", "application/json");
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                var data = "' . $payload . '";   
                var responseText = null;
                xhr.onreadystatechange = function() {
                    if (this.readyState == 4 && this.status == 200) {
                        responseText = this.responseText;
                    }
                };
                xhr.send(data);
                return responseText;
            ');

            $resString = htmlspecialchars_decode($temp);
            $data = $this->http->JsonLog($resString);
        } else {
            $script = '
                fetch("https://book.qantas.com/qf-booking/dyn/air/booking/flexPricerAvailabilityActionFromLoad", {
                    "credentials": "include",
                    "headers": {
                        "Accept": "application/json",
                        "Accept-Encoding": "gzip, deflate, br, zstd",
                        "Content-Type": "application/x-www-form-urlencoded",
                    },
                    "referrer": "https://book.qantas.com/qf-booking/dyn/air/booking/FFCO?SITE=QFQFQFBW&LANGUAGE=GB&TAB_ID=' . $this->tabId . '",
                    "body": "' . $payload . '",
                    "method": "POST",
                    "mode": "cors"
                })
                .then( response => {

                    if (response.ok) {
                        return response.json().catch(error => {
                            let script = document.createElement("script");
                            let id = "dataPrice";
                            script.id = \'dataPriceError\';
                            script.setAttribute(id, error);
                            document.querySelector("body").append(script);
                        });
                    }
                    response.text().then( text => {
                        let script = document.createElement("script");
                        let id = "dataPrice";
                        script.id = \'dataPriceError\';
                        script.setAttribute(id, text);
                        document.querySelector("body").append(script);
                    })
                })
                .then( result => {
                    let script = document.createElement("script");
                    let id = "dataPrice";
                    script.id = id;
                    script.setAttribute(id, JSON.stringify(result));
                    document.querySelector("body").append(script);
                });
            ';
            $this->logger->info($script, ['pre' => true]);

            try {
                $this->driver->executeScript($script);
            } catch (\WebDriverException $e) {
                throw new \CheckRetryNeededException(5, 0);
            }

            $dataPrice = $this->waitForElement(\WebDriverBy::xpath('//script[@id="dataPrice"]'), 20, false);
            $this->saveResponse();

            if (!$dataPrice) {
                $errorURL = $this->http->currentUrl();

                if (str_contains($errorURL, "errorCodes=")) {
                    $this->SetWarning("We're having trouble finding flight options that match your search. Try another date.");

                    return [];
                }

                throw new \CheckRetryNeededException(5, 0);
            }

//            $resString = $dataPrice->getAttribute("dataprice");
            $resString = $this->http->FindSingleNode('//script[@id="dataPrice"]/@dataprice');
            $resString = htmlspecialchars_decode($resString);
            $data = $this->http->JsonLog($resString);
        }

        if (strpos($this->http->currentUrl(), "errorCodes=") !== false) {
            $this->SetWarning("We're having trouble finding flight options that match your search. Try another date.");

            return [];
        }

        if ($data->modelInput->pageCode === 'GERR') {
            $this->SetWarning("No Classic Reward fares. There are no flights within 6 days of your searched dates");

            return [];
        }

        if (is_array($this->checkBackupToSearch($data))) {
            return [];
        }

        return $this->response = $this->parseRewardFlights($fields, true, $resString);
    }

    private function runFetchCalendar($fields): array
    {
        $this->logger->notice(__METHOD__);

        if (!$fields['ParseCalendar']) {
            $this->logger->info("ParseCalendar off");

            return [];
        }

        if (!empty($this->calendar)) {
            $this->parseRewardCalendarJson($this->calendar);
        }

        if ($this->http->FindSingleNode('//p[contains(., "Sorry, an error seems to have occurred, and this page cannot be displayed")]')) {
            throw new \CheckRetryNeededException(2, 0);
//            throw new \CheckException($msg, ACCOUNT_PROVIDER_ERROR);
        }

        if (empty($this->enc)) {
            $this->sendNotification('check ENC code //DM');

            throw new \CheckException("Don't send ENC code");
        }

        $this->waitForElement(\WebDriverBy::xpath('//span[contains(., "Rewards")]'), 10);

        $this->saveResponse();

        if ($mess = $this->http->FindSingleNode('//span[@msg-src-key= "ALLP.text.NoFlightAvailable"] | //h3[@msg-src-key="ALLP.text.flightFilters.noMatchingFlights"]')) {
            $this->SetWarning($mess);

            return [];
        }

        if (strpos($this->http->currentUrl(), "errorCodes=") !== false) {
            $this->SetWarning("We're having trouble finding flight options that match your search. Try another date.");

            return [];
        }

        $payload = http_build_query([
            "BOUND_TO_CALCULATE_1"               => "TRUE",
            "B_DATE_1"                           => date('YmdHi', $fields['DepDate']),
            "B_LOCATION_1"                       => $fields['DepCode'],
            "EMBEDDED_TRANSACTION"               => "preAvailabilityAction",
            "ENC"                                => $this->enc,
            "ENCT"                               => "1",
            "E_LOCATION_1"                       => $fields['ArrCode'],
            "LANGUAGE"                           => "GB",
            "SITE"                               => "QFQFQFBI",
            "SKIN"                               => "P",
            "WDS_AVAIL_CLASSIC_REWARD_PER_BOUND" => true,
            "WDS_CABIN"                          => "Rewards{$this->encodeCabinCalendar($fields['Cabin'])}",
            "WDS_IS_REWARDS_ONLY"                => true,
            "WDS_MARGINAL_BUS_CFF"               => "ACEMBUS",
            "WDS_MARGINAL_ECO_CFF"               => "ACEMECO",
            "WDS_MARGINAL_FIR_CFF"               => "ACEMFIR",
            "WDS_MARGINAL_PRM_CFF"               => "ACEMPRM",
            "WDS_OLD_TAB_ID"                     => $this->tabId,
            "WDS_SESSION_LESS"                   => "TRUE",
            "DATE_RANGE_QUALIFIER_1"             => "C",
            "DATE_RANGE_QUALIFIER_2"             => "C",
            "DATE_RANGE_VALUE_1"                 => "15",
            "DATE_RANGE_VALUE_2"                 => "15",
        ]);

        $script = ('
                var xhr = new XMLHttpRequest();
                xhr.withCredentials = true;
                xhr.open("POST", "https://book.qantas.com/qf-booking/dyn/air/booking/flexPricerCalendarActionFromLoad", false);
                xhr.setRequestHeader("Accept", "application/json");
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                var data = "' . $payload . '";   
                var responseText = null;
                xhr.onreadystatechange = function() {
                    if (this.readyState == 4 && this.status == 200) {
                        responseText = this.responseText;
                    }
                };
                xhr.send(data);
                return responseText;
            ');
        $this->logger->info('[Run script:]');
        $this->logger->info($script, ['pre' => true]);
        $data = $this->driver->executeScript($script);

        $resString = htmlspecialchars_decode($data);
        $calendar = $this->http->JsonLog($resString);

        if (strpos($this->http->currentUrl(), "errorCodes=") !== false) {
            $this->SetWarning("We're having trouble finding flight options that match your search. Try another date.");

            return [];
        }

        if ($calendar->modelInput->pageCode === 'GERR') {
            $this->SetWarning("No Classic Reward fares.");

            return [];
        }

        if (($calendar->modelInput->pageCode == "BackupToSearch")) {
            $this->SetWarning('There are no flights for the this month');

            return [];
        }

        return $this->parseRewardCalendarJson($calendar);
    }

    private function getXhrRequests($fields)
    {
        $this->logger->notice(__METHOD__);

        /** @var \SeleniumDriver $seleniumDriver */
        $seleniumDriver = $this->http->driver;

        try {
            $requests = $seleniumDriver->browserCommunicator->getRecordedRequests();
        } catch (BrowserCommunicatorException $e) {
            $this->logger->error('BrowserCommunicatorException: ' . $e->getMessage());

            throw new \CheckRetryNeededException(5, 0);
        } catch (\WebDriverCurlException | \WebDriverException $e) {
            $this->logger->error('WebDriverException: ' . $e->getMessage());

            throw new \CheckRetryNeededException(5, 0);
        }
        $response = null;

        foreach ($requests as $n => $xhr) {
            if (strpos($xhr->request->getUri(), 'air/booking/flexPricerAvailabilityActionFromLoad') !== false) {
                $response = json_encode($xhr->response->getBody());

                continue;
            }

//            if (strpos($xhr->request->getUri(), 'air/booking/flexPricerCalendarActionFromLoad') !== false) {
//                $responseCalendar = json_encode($xhr->response->getBody());
//
//                continue;
//            }

            if (isset($response)) {
                break;
            }
        }

        if ((empty($response)
                || strpos($response, ',"availability":') === false
                || $this->http->FindSingleNode("//span[normalize-space()='This is the current selected date']/following-sibling::span[normalize-space()!=''][1]") === 'No flights'
            )
            && ($msg = $this->http->FindSingleNode("
                (//span[contains(normalize-space(text()),'Flights are not available on this date. See other dates above.')])[1]
                | (//*[self::h5 or self::strong or self::div][contains(normalize-space(text()),'It looks like something went wrong there')])[1]
            "))
        ) {
            $this->SetWarning($msg);

            return [];
        }

        if (empty($response) && ($this->waitForElement(\WebDriverBy::xpath("
            //p[contains(normalize-space(text()),'Sorry, an error seems to have occurred, and this page cannot be displayed')]
            "),
                0))) {
            throw new \CheckRetryNeededException(2, 0);
//            throw new \CheckException($msg->getText(), ACCOUNT_PROVIDER_ERROR);
        }

        if (empty($response)) {
            throw new \CheckException('We are unable to process this request currently. Please try again later', ACCOUNT_PROVIDER_ERROR);
        }

        if ($fields['ParseCalendar'] && empty($temp->modelInput->availability->calendars[0]->tabs)) {
            $temp = $this->http->JsonLog($response);

            if (!empty($temp->modelInput->viewClassicRewardsForm)) {
                $this->enc = $temp->modelInput->viewClassicRewardsForm->parameters->ENC[0];
                $this->logger->debug("Enc: " . $this->enc);
            } else {
                $this->enc = $temp->modelInput->previousNextCalendarForm->parameters->ENC[0];
                $this->logger->debug("Enc: " . $this->enc);
            }
            $temp = null;
        }

//        $this->http->JsonLog($responseCalendar, 1, false, 'useMarginalPointsPrice');
//        $useMarginalPointsPrice = $this->http->FindPreg('/useMarginalPointsPrice"\s*:\s*(:false|true)/', false,
//            $responseCalendar ?? '');
//
//        if (isset($useMarginalPointsPrice)) {
//            $this->useMarginalPointsPrice = $useMarginalPointsPrice === 'true' ? true : false;
//            $this->logger->warning('useMarginalPointsPrice: ' . var_export($useMarginalPointsPrice, true));
//        }

        $this->response = $response;

        return $response;
    }

    private function getCabinFields($onlyKeys = true): array
    {
        $cabins = [
            'economy'        => 'Economy',
            'premiumEconomy' => 'Premium Economy',
            'firstClass'     => 'First',
            'business'       => 'Business',
        ];

        if ($onlyKeys) {
            return array_keys($cabins);
        }

        return $cabins;
    }

    private function decodeCabin($cabinCode): string
    {
        $cabins = [
            'Economy'         => 'economy',
            'Premium Economy' => 'premiumEconomy',
            'First'           => 'firstClass',
            'Business'        => 'business',
        ];

        return $cabins[$cabinCode];
    }

    private function parseRewardFlights($fields, $isNew = false, $data = null): array
    {
        $routes = [];
        $cabinArray = $this->getCabinFields(false);

        if (empty($this->enc)) {
            $temp = $this->http->JsonLog($data, 0);

            if (!empty($temp->modelInput->viewClassicRewardsForm)) {
                $this->enc = $temp->modelInput->progressBarBean->pageForms->FFCO->parameters->ENC[0];
                $this->logger->debug("Enc: " . $this->enc);
            } else {
                $this->enc = $temp->modelInput->previousNextCalendarForm->parameters->ENC[0];
                $this->logger->debug("Enc: " . $this->enc);
            }
            $temp = null;
        }

        if ($isNew && $data) {
            $jsonData = $this->http->JsonLog($data, 0, true);
        } else {
            $TAB_ID = $this->http->FindPreg("/TAB_ID=(.+)/", false, $this->http->currentUrl());
            $json = $this->http->FindSingleNode("//script[contains(.,'var theRFCOForm')]", null, false,
                "/theRFCOForm = new RFCOForm\((\{.+\})\);\s*(?:$|\/\/)/iu");
            $jsonData = $this->http->JsonLog($json, 0, true);
            $requestID = (int) ($this->http->FindPreg("/this.requestId = (\d+);/") ?? 100);
            $TAB_ID = $TAB_ID ?? $jsonData['tabSessionId'];
        }

        if ($isNew) {
            if (isset($jsonData['modelInput']['availability']['bounds'][0])) {
                $availability = $jsonData['modelInput']['availability'];
            }
        } else {
            if (isset($jsonData['availability']['bounds'][0])) {
                $availability = $jsonData['availability'];
            }
        }

        if (!isset($availability)) {
            $this->logger->error('other format json');

            throw new \CheckException('other format json', ACCOUNT_ENGINE_ERROR);
        }

        $bounds = $availability['bounds'][0];
        $fareFamilies = $availability['listFareFamily']['fareFamilies'];

        if (empty($bounds['listItineraries'])) {
            $dateStr = date("d M", $fields['DepDate']);
            $this->SetWarning("Trip from {$fields['DepCode']} to {$fields['ArrCode']} is not available on the {$dateStr}.");

            return [];
        }

        $currency = $availability['currency']['code'];

        if ($isNew) {
            $itineraries = $bounds['listItineraries']['itineraries'];
        } else {
            $itineraries = $bounds['listItineraries']['itinerariesAsMap'];
        }

        if ($isNew) {
            $sortedFlights = $this->sortFlights($bounds['flights']);
        }

        foreach ($itineraries as $id => $itinerary) {
            if ($isNew) {
                if (!isset($sortedFlights[$itinerary['itemId']]) || isset($itinerary['fakeItinerary'])) {
                    $this->sendNotification("check itineraries vs flights or fakeIt //ZM");
                    $this->logger->notice("skip itinerary");

                    continue;
                }
                $flights = $sortedFlights[$itinerary['itemId']];
            } else {
                if (!isset($bounds['flights'][$id]) || isset($itinerary['fakeItinerary'])) {
                    $this->sendNotification("check itinerariesAsMap vs flights or fakeIt //ZM");
                    $this->logger->notice("skip itinerary");

                    continue;
                }
                $flights = $bounds['flights'][$id];
            }
            $hasBusinessCabinForMarginal = $bounds['flights'][$itinerary['itemId']]['hasBusinessCabinForMarginal'];
            $this->logger->debug('itinerary #' . $id . ($isNew ? "[" . $itinerary['itemId'] . "]" : ''));
            $segments = [];
            $layover = null;
            $totalFlight = null;
            // заглушка:
            $urlReferer = 'https://book.qantas.com/';

            if (!$isNew) {
                $urlReferer = $this->http->currentUrl();
            }

            foreach ($itinerary['segments'] as $s) {
                // first value - classic search, second - new format
                $flightNum = $s['flightFullName'] ?? ($s['airline']['code'] . $s['flightNumber']);

                if ($flightNum === 'RJ344') {
                    $this->logger->error('skip this route: it has flight RJ344 (go back in time)');

                    continue 2;
                }
                $airline = $s['cachedAirlineCode'] ?? $s['airline']['code'];

                if (($pos = strpos($airline, '_')) !== false) {
                    $airline = substr($airline, 0, $pos);
                }
                $seg = [
                    'id'        => $s['id'],
                    'departure' => [
                        'date'     => date('Y-m-d H:i', (int) ($s['beginDate'] / 1000)),
                        'dateTime' => (int) ($s['beginDate'] / 1000),
                        'airport'  => $this->http->FindPreg('/^([A-Z]{3})/', false, $s['beginLocationCode']),
                        'terminal' => $this->http->FindPreg('/^(?:[A-Z]{3})(.*)/', false, $s['beginLocationCode']),
                    ],
                    'arrival' => [
                        'date'     => date('Y-m-d H:i', (int) ($s['endDate'] / 1000)),
                        'dateTime' => (int) ($s['endDate'] / 1000),
                        'airport'  => $this->http->FindPreg('/^([A-Z]{3})/', false, $s['endLocationCode']),
                        'terminal' => $this->http->FindPreg('/^(?:[A-Z]{3})(.*)/', false, $s['endLocationCode']),
                    ],
                    'num_stops' => $s['nbrOfStops'],
                    'cabin'     => null,
                    'flight'    => [$flightNum],
                    'airline'   => $airline,
                    'distance'  => null,
                    'times'     => ['flight' => null, 'layover' => null],
                ];

                if ($isNew) {
                    if (isset($bounds['listItineraries']['airlines'][$s['cachedOperatingCarrierCode']])) {
                        $seg['operator'] = $bounds['listItineraries']['airlines'][$s['cachedOperatingCarrierCode']]['capitalizedName'];
                    }
                }
                $segments[] = $seg;
            }
            $headers = [
                'Accept'           => 'application/json, text/javascript, */*; q=0.01',
                'Content-Type'     => 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-Requested-With' => 'XMLHttpRequest',
                'Referer'          => $urlReferer,
            ];

            foreach ($flights['listRecommendation'] as $cabinCode => $item) {
                $this->logger->emergency($cabinCode);

                if (!$fareFamilies[$cabinCode]['isMarginal']) {
                    $this->logger->notice("skip itinerary");

                    continue;
                }

                if (strpos($cabinCode, "BUS") !== false && !$hasBusinessCabinForMarginal) {
                    $this->logger->notice("skip itinerary");

                    continue;
                }

                $this->logger->emergency('tax' . $item['taxForOne']);
                $headData = [
                    'distance'  => null,
                    'num_stops' => $itinerary['nbrOfStops'],
                    'times'     => [
                        'flight'  => $totalFlight,
                        'layover' => $itinerary['nbrOfStops'] == 0 ? null : $layover,
                    ],
                    'redemptions' => [
                        'miles'   => null,
                        'program' => $this->AccountFields['ProviderCode'],
                    ],
                    'payments' => [
                        'currency' => $currency,
                        'taxes'    => $isNew ? null : $item['taxForOne'], // 'taxForAll'
                        'fees'     => null,
                    ],
                ];

                $routeCabins = [];

                foreach ($item['mixedCabins'] as $mixedCabin) {
                    $routeCabins[$mixedCabin['segmentId']] = $mixedCabin['realCabinName'];
                }

                if ($isNew) {
//                    if (isset($this->useMarginalPointsPrice)) {
//                        $showTax = $this->useMarginalPointsPrice;
//                    } else {
//                        $showTax = $item['priceForOne']['surcharges'] !== 0;
//                    }
//
//                    if (!$showTax) {
//                        $headData['redemptions']['miles'] = $item['priceForOne']['convertedCashMiles'];
//                    } else {
                    $headData['redemptions']['miles'] = $item['priceForOne']['convertedBaseFare'];
                    $headData['payments']['taxes'] = $item['priceForOne']['tax'];
//                    }
                } else {
                    $requestID++;
                    $AIRLINE_CODES = urlencode(implode(',', array_map(function ($s) {
                        return substr($s['cachedAirlineCode'], 0, 2);
                    }, $itinerary['segments'])));
                    $OPERATING_CARRIER_CODES = urlencode(implode(',', array_map(function ($s) {
                        return stripos($s['cachedOperatingCarrierCode'],
                            'null') !== false ? substr($s['cachedAirlineCode'],
                            0, 2) : substr($s['cachedOperatingCarrierCode'], 0, 2);
                    }, $itinerary['segments'])));

                    $CLASSES = urlencode(implode(',', $item['rbds']));

                    $DATES = urlencode(implode(',', array_map(function ($s) {
                        return date("dmYHi", $s['departure']['dateTime']) . '/' . date("dmYHi",
                                $s['arrival']['dateTime']);
                    }, $segments)));

                    $FLIGHT_NUMBERS = urlencode(implode(',', array_map(function ($s) {
                        return $s['flightNumber'];
                    }, $itinerary['segments'])));

                    $SECTORS = urlencode(implode(',', array_map(function ($s) {
                        return $s['departure']['airport'] . '/' . $s['arrival']['airport'];
                    }, $segments)));
                    $SEGMENT_ID = urlencode(implode(',', array_keys($segments)));
                    $NB_DAYS_BETWEEN_DEP_ARR = urlencode(implode(',', array_map(function ($s) {
                        return $s['nbDaysBetweenItiDepAndSegArr'];
                    }, $itinerary['segments'])));
                    $postData = "PAGE_CODE=RFCO&LANGUAGE=GB&SITE=QFQFQFFA&TAB_ID={$TAB_ID}&REQUEST_ID={$requestID}&AIRLINE_CODES={$AIRLINE_CODES}&OPERATING_CARRIER_CODES={$OPERATING_CARRIER_CODES}&CLASSES={$CLASSES}&DATES={$DATES}&FLIGHT_NUMBERS={$FLIGHT_NUMBERS}&SECTORS={$SECTORS}&SEGMENT_ID={$SEGMENT_ID}&NB_DAYS_BETWEEN_DEP_ARR={$NB_DAYS_BETWEEN_DEP_ARR}&CONTAINS_CLASSIC_REWARDS=true&NB_BOUNDS_IN_REQUEST=1";
                    $memMaxRedirects = $this->curl->getMaxRedirects();
                    $this->curl->RetryCount = 0;
                    $this->curl->setMaxRedirects(0);
                    $this->setCookieFromSelenium($this->curl);
                    $this->curl->PostURL("https://book.qantas.com/pl/QFAward/wds/RetrieveLoyaltyPointsInfoServlet",
                        $postData, $headers);

                    if ($this->isBadProxy($this->curl)) {
                        $this->sendNotification('proxy changed // ZM');

                        if (!isset($proxyChanged)) {
                            $this->setProxyGoProxies(null, 'gb');
                            sleep(1);
                            $this->curl->PostURL("https://book.qantas.com/pl/QFAward/wds/RetrieveLoyaltyPointsInfoServlet",
                                $postData, $headers);
                            $proxyChanged = true;
                        } else {
                            break 2; // вернем, что собрали
                        }
                    }

                    if ($this->curl->Response['code'] == 500) {
                        sleep(2);
                        $this->curl->PostURL("https://book.qantas.com/pl/QFAward/wds/RetrieveLoyaltyPointsInfoServlet",
                            $postData, $headers);

                        if ($this->curl->Response['code'] != 200) {
                            $skip500 = true;

                            continue;
                        }
                        $skip500Helped = true;
                    }

                    if ($this->curl->Response['code'] != 200
                        || $this->curl->FindPreg('/^\s*{"model":{"pageCode":"RPTS","requestId":"\d+","localizedMessages":{}}}\s*$/')
                    ) { // retry helped
                        sleep(2);
                        $this->curl->setMaxRedirects($memMaxRedirects);
                        $this->curl->PostURL("https://book.qantas.com/pl/QFAward/wds/RetrieveLoyaltyPointsInfoServlet",
                            $postData, $headers);
                    }
                    $this->curl->RetryCount = 2;

                    if ($this->curl->FindPreg('/^\s*{"model":{"pageCode":"RPTS","requestId":"\d+","localizedMessages":{}}}\s*$/')) {
                        $this->logger->warning('can\'t get miles. skip ' . $cabinCode);

                        continue;
                    }

                    if ($this->curl->Response['code'] == 403) {
                        continue;
                    }

                    $paymentData = $this->curl->JsonLog(null, 0, true);

                    if (isset($skip500)) {
                        $hasPaymentsAfter500 = true;
                    }

                    if (isset($paymentData['model']['pageCode'])
                        && $paymentData['model']['pageCode'] === 'RPTS'
                        && $this->curl->FindPreg("/THE ITINERARY CONTAINS MORE THAN ONE DEPARTURE FROM THE FIRST DEPARTURE CITY\/COUNTRY/")
                    ) {
                        $this->logger->error('skip MORE THAN ONE DEPARTURE: ' . $cabinCode);
                        $this->logger->error('THE ITINERARY CONTAINS MORE THAN ONE DEPARTURE FROM THE FIRST DEPARTURE CITY/COUNTRY');
                        $skipMoreThanOne = true;

                        continue;
                    }

                    if (isset($paymentData['model']['pageCode'])
                        && $paymentData['model']['pageCode'] === 'GERR'
                        && $this->curl->FindPreg("/We are having trouble processing your booking. Please try again or contact us if the problem persists/")
                    ) {
                        $this->logger->error('skip Recommendation: ' . $cabinCode);
                        $this->logger->error('We are having trouble processing your booking. Please try again or contact us if the problem persists');
                        $skipRecommendation = true;

                        continue;
                    }

                    if (isset($paymentData['model']['bound']['trip'][0])) {
                        $headData['redemptions']['miles'] = $paymentData['model']['quote']['costWithoutDiscount'];
                    } else {
                        if ($this->requestDateTime < 70) {
                            throw new \CheckRetryNeededException(5, 0);
                        }

                        if (isset($skipNoMiles)) {
                            if (count($routes) > 0) {
                                return $routes;
                            }

                            if (!isset($this->checkNewFormat)) {
                                $this->checkNewFormat = true;

                                return [];
                            }
                            $this->sendNotification("can't get miles // ZM");

                            throw new \CheckException("can't get miles", ACCOUNT_ENGINE_ERROR);
                        }
                        // after skip can parse other - ok
                        $skipNoMiles = true;
                        $this->logger->warning('skip route (can\'t get miles)');

                        continue;
                    }
                }
                $updSegments = $segments;

                foreach ($segments as $i => $segment) {
                    if (array_key_exists($segment['id'], $routeCabins)) {
                        foreach ($cabinArray as $k => $v) {
                            if (strcasecmp($routeCabins[$segment['id']], $v) == 0) {
                                $updSegments[$i]['cabin'] = $k;

                                break;
                            }
                        }
                    } else {
                        if ($isNew && isset($availability['fareFamiliesMinirules']['minirules'][$cabinCode]['fullTextRules'])) {
                            $updSegments[$i]['cabin'] = $this->getCabinFromData($availability['fareFamiliesMinirules']['minirules'][$cabinCode],
                                '');
                        }

                        if (!$updSegments[$i]['cabin'] && $isNew && isset($availability['listFareFamily']['fareFamilies'][$cabinCode])) {
                            $updSegments[$i]['cabin'] = $this->getCabinFromData([],
                                $availability['listFareFamily']['fareFamilies'][$cabinCode]['name']);
                        }
                        // MB: $availability['ffCodeToAssociatedCabinForCabinCrossSell'][$cabinCode] - checked - не подходит для детекта кэбина

                        if (!$updSegments[$i]['cabin']) {
                            foreach ($cabinArray as $k => $v) {
//                            if (isset($fareFamilies[$item['ffCode']]) && strcasecmp($fareFamilies[$item['ffCode']]['name'],
                                if (isset($fareFamilies[$cabinCode]) && strcasecmp($fareFamilies[$cabinCode]['name'],
                                        $v) == 0
                                ) {
                                    $updSegments[$i]['cabin'] = $k;

                                    break;
                                }
                            }
                        }
                    }

                    if (!$updSegments[$i]['cabin']) {
//                        $this->sendNotification("check cabin // ZM");
                    }
                    unset($updSegments[$i]['id']);
                }
                $result = ['connections' => $updSegments];
                $res = array_merge($headData, $result);

                if ($isNew) {
                    if ($item['showLSA']) { // флаг LastSeatMessage - показать ли сообщение 'Hurry! There are 5 or fewer seats available at this price. Book now to secure your seat.'
                        $res['tickets'] = 5;
                    }

                    if (isset($availability['listFareFamily']['fareFamilies'][$cabinCode]['name'])) {
                        $res['award_type'] = $availability['listFareFamily']['fareFamilies'][$cabinCode]['name'];

                        if (preg_match("/^(Business|Premium Economy|First|Economy) (Saver|Flex|Sale|Classic Reward|Deal)$/i",
                            $res['award_type'], $m)) {
                            $res['award_type'] = $m[2];
                            $res['classOfService'] = $m[1];
                        } else {
                            $res['classOfService'] = $availability['listFareFamily']['fareFamilies'][$cabinCode]['name'];

                            if ($res['classOfService'] === 'Saver' || $res['classOfService'] === 'Flex' || $res['classOfService'] === 'Sale') { // seems Economy
                                $res['award_type'] = $res['classOfService'];
                                $res['classOfService'] = 'Economy';
                            }
                        }
                    }
                }
                $this->logger->debug("Parsed data:");
                $this->logger->debug(var_export($res, true), ['pre' => true]);
                $routes[] = $res;
            }
        }

        if (isset($skipMoreThanOne) && empty($routes)) {
            $this->SetWarning('ERROR : please check those fields; THE ITINERARY CONTAINS MORE THAN ONE DEPARTURE FROM THE FIRST DEPARTURE CITY/COUNTRY');

            return $routes;
        }

        if (isset($skipRecommendation) && empty($routes)) {
            throw new \CheckException('We are having trouble processing your booking. Please try again or contact us if the problem persists', ACCOUNT_PROVIDER_ERROR);
        }

        if (isset($hasPaymentsAfter500)) {
            $this->sendNotification('payments  after 500 // ZM');
        }

        if (isset($skip500Helped)) {
            $this->sendNotification('retry 500 helped // ZM');
        }

        if (isset($skip500) && empty($routes)) {
            $this->sendNotification('no payments (500) // ZM');

            throw new \CheckException('provider has never given payment (500)', ACCOUNT_ENGINE_ERROR);
        }

        return $routes;
    }

    private function parseRewardCalendarJson($data = null): array
    {
        $this->logger->notice(__METHOD__);
        $calendar = [];

        foreach ($data->modelInput->availability->calendars[0]->tabs as $tab) {
            if (empty($tab->marginalPrice)) {
                continue;
            }

            preg_match("/^(Business|Premium Economy|First|Economy) (Saver|Flex|Sale|Classic Reward|Deal)$/i",
                $tab->calendarAvailableFareFamilies[0]->name, $m);

            $calendar[] = [
                'date'        => date('Y-m-d', (int) ($tab->date / 1000)),
                'redemptions' => ['miles' => $tab->marginalPrice->convertedBaseFare],
                'payments'    => [
                    'currency' => $tab->marginalPrice->currency->code,
                    'taxes'    => $tab->marginalPrice->tax,
                    'fees'     => null,
                ],
                'cabin'        => $this->decodeCabin($m[1]),
                'brandedCabin' => $m[1],
            ];
        }

        if (empty($calendar)) {
            $this->SetWarning('There are no flights for the this month');
        }

        return $calendar;
    }

    private function getCabinFromData($data, $extValue): ?string
    {
        $this->logger->notice(__METHOD__);

        if (!isset($data['fullTextRules'])) {
            $data['fullTextRules'] = [];
        }

        foreach ($data['fullTextRules'] as $v) {
            if (isset($v['ruleId']) && $v['ruleId'] === 'travelClass' && isset($v['fullTextValue']) && !empty($v['fullTextValue'])) {
                $data['fareFamilyName'] = $v['fullTextValue'];

                break;
            }
        }

        if (!isset($data['fareFamilyName'])) {
            $data['fareFamilyName'] = $extValue;
        }

        if (stripos($data['fareFamilyName'], 'Premium Economy') !== false) {
            return 'premiumEconomy';
        }

        if (stripos($data['fareFamilyName'], 'Economy') !== false
            || $data['fareFamilyName'] === 'Saver'
            || $data['fareFamilyName'] === 'Flex'
        ) {
            return 'economy';
        }

        if (stripos($data['fareFamilyName'], 'Business') !== false) {
            return 'business';
        }

        if (stripos($data['fareFamilyName'], 'First') !== false) {
            return 'firstClass';
        }

        return null;
    }

    private function checkBackupToSearch($dataPrice): ?array
    {
        $this->logger->notice(__METHOD__);

        if (isset($dataPrice->modelInput) && isset($dataPrice->modelInput->pageCode)
            && $dataPrice->modelInput->pageCode === 'BackupToSearch'
            && isset($dataPrice->modelInput->form)
            && strpos($dataPrice->modelInput->form->action,
                'www.qantas.com/tripflowapp/bookingError.tripflow') !== false
        ) {
            $headers = [
                'Origin'       => 'https://book.qantas.com',
                'Content-type' => 'application/x-www-form-urlencoded',
                'Referer'      => 'https://book.qantas.com/',
                'method'       => 'POST',
            ];
            $payload['WDS_SESSION_LESS'] = 'TRUE';
            $checkCnt = 1;

            foreach ($dataPrice->modelInput->form->parameters as $key => $value) {
                if (is_array($value) && count($value) === 1 && $key !== 'WDS_SESSION_LESS') {
                    // $payload .= "&" . $key . '=' . $value[0];
                    $payload[$key] = $value;
                    $checkCnt++;
                }
            }
            $payload = http_build_query($payload);

            if ($checkCnt === 8) {
                $url = preg_replace("/.*\/\//", "https://", trim($dataPrice->modelInput->form->action));
                $memRedirects = $this->http->getMaxRedirects();
                $this->http->setMaxRedirects(0);
//                $this->http->PostURL($url, $payload, $headers);
                $this->http->setMaxRedirects($memRedirects);

                // TODO Возможно этого метода больше не существует
                // TODO Получить ошибки я не смог.

                $this->SetWarning("We couldn\'t find any flights for the dates you entered. Try changing the dates and search again.");

                return [];
            }
        }

        return null;
    }

    private function sortFlights(array $data): array
    {
        $res = [];

        foreach ($data as $item) {
            $res[$item['flightId']] = $item;
        }

        if (empty($res)) {
            return $data;
        }

        return $res;
    }

    private function routeWithNewDesign($dep, $arr)
    {
        return
            (($dep->country->code === 'AU' || $arr->country->code !== 'AU')
                && ($arr->isCommercialOnly === false)
                && in_array($dep->country->code, $this->singleEngineCountries()))
            || (($arr->country->code === 'AU' || $dep->country->code !== 'AU')
                && ($dep->isCommercialOnly === false)
                && in_array($arr->country->code, $this->singleEngineCountries()));
    }

    private function singleEngineCountries(): array
    {
        return [
            "AU",
            "NZ",
            "FJ",
            "PF",
            "NC",
            "VU",
            "CA",
            "MX",
            "KR",
            "HK",
            "SG",
            "TH",
            "CN",
            "JP",
            "GB",
            "FR",
            "DE",
            "NL",
            "IE",
            "ZA",
            "IN",
            "ID",
            "MY",
            "PG",
            "TW",
            "VN",
            "AT",
            "BE",
            "DK",
            "AE",
            "FI",
            "IL",
            "IT",
            "NO",
            "ES",
            "CH",
            "SE",
            "PH",
            "US",
            "TR",
            "CL",
            "PT",
        ];
    }

    private function checkBeforeStart($fields)
    {
        $this->logger->notice(__METHOD__);

        if ($fields['DepDate'] > strtotime('+354 day')) {
            $this->SetWarning('too late');

            $this->noFligths = true;
        }

        if ($fields['Currencies'][0] !== 'USD') {
            $fields['Currencies'][0] = $this->getRewardAvailabilitySettings()['defaultCurrency'];
            $this->logger->notice("parse with defaultCurrency: " . $fields['Currencies'][0]);
        }
        $depDetail = $arrDetail = null;

        $dataOrigin = \Cache::getInstance()->get('ra_qantas_origin_' . $fields['DepCode']);
        $headers = [
            'Accept'  => '*/*',
            'Origin'  => 'https://www.qantas.com',
            'Referer' => 'https://www.qantas.com/',
        ];

        $this->initCurl();

        if (!$dataOrigin || !isset($dataOrigin->airports)) {
            $this->logger->notice('Check qantas origin');
            $this->curl->RetryCount = 0;
            $this->curl->removeCookies();
            $this->curl->GetURL("https://api.qantas.com/flight/routesearch/v1/airports?queryFrom={$fields['DepCode']}",
                $headers, 20);

            if ($this->isBadProxy($this->curl)) {
                throw new \CheckRetryNeededException(5, 0);
            }
            $data = $this->curl->JsonLog(null, 0);

            if (isset($data->airports)) {
                \Cache::getInstance()->set('ra_qantas_origin_' . $fields['DepCode'], $data, 60 * 60 * 24);
            }
        } else {
            $data = $dataOrigin;
        }

        if (isset($data->airports) && is_array($data->airports)) {
            if (count($data->airports) === 0) {
                $this->SetWarning("This origin is not available.");

                $this->noFligths = true;
            }

            foreach ($data->airports as $airport) {
                if ($airport->code === $fields['DepCode']) {
                    $depDetail = $airport;

                    break;
                }
            }

            if (!isset($depDetail)) {
                $this->SetWarning("This origin is not available.");

                $this->noFligths = true;
            }
        }

        if ($this->noFligths) {
            return;
        }

        $dataRoute = \Cache::getInstance()->get('ra_qantas_route_' . $fields['DepCode'] . '_' . $fields['ArrCode']);

        if (!$dataRoute || !isset($dataRoute->airports)) {
            $this->logger->notice('Check qantas route');
            $this->curl->RetryCount = 0;
            $this->curl->removeCookies();
            $this->curl->GetURL("https://api.qantas.com/flight/routesearch/v1/airports/{$fields['DepCode']}?queryTo={$fields['ArrCode']}",
                $headers, 20);
            $data = $this->curl->JsonLog(null, 0);

            if (isset($data->airports)) {
                \Cache::getInstance()->set('ra_qantas_route_' . $fields['DepCode'] . '_' . $fields['ArrCode'], $data,
                    60 * 60 * 24);
            }
        } else {
            $data = $dataRoute;
        }

        if (isset($data->airports) && is_array($data->airports)) {
            if (count($data->airports) === 0) {
                $this->SetWarning("This destination is not available from the selected origin.");

                $this->noFligths = true;
            }

            foreach ($data->airports as $airport) {
                if ($airport->code === $fields['ArrCode']) {
                    $arrDetail = $airport;

                    break;
                }
            }

            if (!isset($arrDetail)) {
                $this->SetWarning("This destination is not available from the selected origin.");

                $this->noFligths = true;
            }
        }

        $this->logger->debug(var_export($depDetail, true));

        if (isset($depDetail, $arrDetail) && $this->routeWithNewDesign($depDetail, $arrDetail)) {
            $this->seleniumAuth = false;
        }
    }

    private function checkWrapper()
    {
        $this->logger->notice(__METHOD__);

        $noThx = $this->waitForElement(\WebDriverBy::xpath("//button[@class='RedirectPopup-module__linkButton___1OsOn']"),
            5);

        try {
            if ($noThx) {
                try {
                    $noThx->click();
                } catch (\Exception $e) {
                    $this->driver->executeScript("document.querySelector('.RedirectPopup-module__linkButton___1OsOn').click();");
                }
            }
        } catch (\WebDriverCurlException $e) {
            $this->logger->error($e->getMessage());

            throw new \CheckRetryNeededException(5, 0);
        }
    }

    private function initCurl()
    {
        $this->logger->notice(__METHOD__);

        $this->curl = new \HttpBrowser("none", new \CurlDriver());
        $this->http->brotherBrowser($this->curl);

        $this->curl->LogHeaders = true;
        $this->curl->setDefaultHeader("Accept-Encoding", "gzip, deflate, br");
        $this->curl->setHttp2(true);

        $this->curl->SetProxy("{$this->http->getProxyAddress()}:{$this->http->getProxyPort()}");
        $this->curl->setProxyAuth($this->http->getProxyLogin(), $this->http->getProxyPassword());
        $this->curl->setUserAgent($this->http->getDefaultHeader("User-Agent"));
    }

    private function setCookieFromSelenium($http)
    {
        $this->logger->notice(__METHOD__);

        try {
            $cookies = $this->driver->manage()->getCookies();

            foreach ($cookies as $cookie) {
                $http->setCookie($cookie['name'], $cookie['value'], $cookie['domain'], $cookie['path'],
                    $cookie['expiry'] ?? null);
            }
        } catch (\UnexpectedJavascriptException | \WebDriverCurlException $e) {
            return;
        }
    }

    private function loadStartPage($fields): void
    {
        $this->logger->notice(__METHOD__);

        try {
            // TODO временное решение, пров почему-то выставляет дату на 1 день назад
            $dateLabel = date('Y-m-d', strtotime('+1 day', $fields['DepDate']));
            $this->logger->debug("dateLabel: " . $dateLabel);

            $this->http->GetURL("https://www.qantas.com/au/en/book-a-trip/flights.html?departureAirportCode={$fields['DepCode']}&arrivalAirportCode={$fields['ArrCode']}&departureDate={$dateLabel}&usePoints=true&adults={$fields['Adults']}",
                [], 20);
        } catch (\TimeOutException $e) {
            $this->logger->error('Exception: ' . $e->getMessage());

            $this->markProxyAsInvalid();

            try {
                $this->http->GetURL("https://www.qantas.com/au/en/book-a-trip/flights.html?departureAirportCode={$fields['DepCode']}&arrivalAirportCode={$fields['ArrCode']}&departureDate={$dateLabel}&usePoints=true&adults={$fields['Adults']}",
                    [], 20);
            } catch (\TimeOutException | \WebDriverCurlException | \UnknownServerException | \NoSuchDriverException $e) {
                $this->logger->error('Exception: ' . $e->getMessage());

                $this->markProxyAsInvalid();

                throw new \CheckRetryNeededException(5, 0);
            }
        } catch (\UnknownServerException | \UnexpectedJavascriptException | \WebDriverCurlException | \WebDriverException | \NoSuchDriverException $e) {
            $this->logger->error('Exception: ' . $e->getMessage());

            throw new \CheckRetryNeededException(5, 0);
        } catch (\Facebook\WebDriver\Exception\UnknownErrorException
        | \Facebook\WebDriver\Exception\WebDriverException
        | \Facebook\WebDriver\Exception\UnknownErrorException
        | \Facebook\WebDriver\Exception\WebDriverCurlException
        $e) {
            $this->logger->error('\Facebook\WebDriver\Exception\..: ' . $e->getMessage());

            throw new \CheckRetryNeededException(5, 0);
        }

        try {
            if ($this->proxyRegion !== 'au'
                || $this->waitForElement(\WebDriverBy::xpath("//button[@id='modal-no-thanks-btn']"), 0, false)) {
                $this->checkWrapper();
            }
        } catch (\WebDriverCurlException
        | \UnknownServerException
        | \Facebook\WebDriver\Exception\UnknownErrorException
        | \Facebook\WebDriver\Exception\WebDriverException
        | \Facebook\WebDriver\Exception\UnknownErrorException
        | \Facebook\WebDriver\Exception\WebDriverCurlException
        $e) {
            $this->logger->error('Exception: ' . $e->getMessage());

            throw new \CheckRetryNeededException(5, 0);
        }
    }

    private function encodeCabinCalendar($cabin)
    {
        switch ($cabin) {
            case 'economy':
                return 'ECO';

            case 'premiumEconomy':
                return 'PRM';

            case 'business':
                return 'BUS';

            case 'firstClass':
                return 'FIR';
        }
        $this->sendNotification('new cabin ' . $cabin . ' // DM');

        return null;
    }
}
