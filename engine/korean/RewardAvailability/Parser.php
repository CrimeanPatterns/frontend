<?php

namespace AwardWallet\Engine\korean\RewardAvailability;

use AwardWallet\Common\Selenium\FingerprintFactory;
use AwardWallet\Common\Selenium\FingerprintRequest;
use AwardWallet\Engine\ProxyList;
use WebDriverBy;

class Parser extends \TAccountCheckerKorean
{
    use \SeleniumCheckerHelper;
    use ProxyList;
    private const xpathAuth = '
            //button[normalize-space()="Log out"]
            | //button[@id="my-panel-btn"]
        ';

    public $isRewardAvailability = true;
    private $data;
    private $calendarData;

    private $deepAirportCode = null;
    private $arrAirportCode = null;

    private $loginXpath;
    private $radioXPath;

    private $login = false;

    public static function getRASearchLinks(): array
    {
        return ['https://www.koreanair.com/' => 'search page'];
    }

    public static function GetAccountChecker($accountInfo)
    {
        /*        $debugMode = $accountInfo['DebugState'] ?? false;

                if (!$debugMode) {
                    require_once __DIR__ . "/ParserOld.php";

                    return new ParserOld();
                }*/

        return new static();
    }

    public function InitBrowser()
    {
        \TAccountChecker::InitBrowser();
        $this->UseSelenium();
        $this->debugMode = isset($this->AccountFields['DebugState']) && $this->AccountFields['DebugState'];

        $this->useChromePuppeteer(\SeleniumFinderRequest::CHROME_PUPPETEER_104);
        $request = FingerprintRequest::chrome();

        $this->setProxyGoProxies(null, 'kr');

        $request->browserVersionMin = \HttpBrowser::BROWSER_VERSION_MIN;
        $fingerprint = $this->services->get(FingerprintFactory::class)->getOne([$request]);

        if (empty($fingerprint)) {
            $this->seleniumOptions->fingerprint = $fingerprint->getFingerprint();
            $this->http->setUserAgent($fingerprint->getUseragent());
        }

        $this->http->saveScreenshots = true;
        $resolutions = [
            [1152, 864],
            [1280, 720],
            [1280, 768],
            [1280, 800],
            [1360, 768],
            [1366, 768],
            [1920, 1080],
        ];

        $this->KeepState = false;
        $chosenResolution = $resolutions[array_rand($resolutions)];
        $this->setScreenResolution($chosenResolution);

        $this->seleniumRequest->setHotSessionPool(self::class . '100', $this->AccountFields['ProviderCode']);
    }

    public function getRewardAvailabilitySettings()
    {
        return [
            'supportedCurrencies'      => ['EUR', 'KRW', 'USD'],
            'supportedDateFlexibility' => 0,
            'defaultCurrency'          => 'USD',
            'priceCalendarCabins'      => ["firstClass", "premiumEconomy", "economy", "unknown"],
        ];
    }

    public function IsLoggedIn()
    {
        $loggedInUserInfo = $this->driver->executeScript("return sessionStorage.getItem('loggedInUserInfo')");
        $dLogin = $this->http->JsonLog($loggedInUserInfo, 1);

        if (strpos($this->http->currentUrl(), 'https://www.koreanair.com/') !== false
            && (isset($dLogin->signinStatus) || $this->waitForElement(\WebDriverBy::xpath(self::xpathAuth), 0))
        ) {
            try {
                $this->http->GetURL('https://www.koreanair.com');

                if ($this->waitForElement(\WebDriverBy::xpath("
                    //span[contains(text(), 'This site can’t be reached') or contains(text(), 'This page isn’t working')]
                    | //h1[normalize-space()='Access Denied']
                    | //h1[normalize-space()='No internet']
                "), 0)) {
                    throw new \CheckRetryNeededException(5, 0);
                }
            } catch (\UnknownServerException $e) {
                $this->logger->error("exception: " . $e->getMessage());

                throw new \CheckRetryNeededException(5, 0);
            }

            $this->saveResponse();

            return true;
        }

        $this->saveResponse();

        return false;
    }

    public function LoadLoginForm()
    {
        if (!isset($this->AccountFields['Login2'])) {
            // TODO tmp: for ra-awardwallet
            throw new \CheckException('no auth data', ACCOUNT_ENGINE_ERROR);
        }

        if ($this->AccountFields['Login2'] === 'sky') {
            $this->loginXpath = "//label[contains(text(),'SKYPASS Number') and contains(.,'Required')]/following-sibling::div/input";
            $this->radioXPath = "//button[normalize-space(text())='SKYPASS Number']";
        } else {
            $this->loginXpath = "//label[contains(text(),'User ID') and contains(.,'Required')]/following-sibling::div/input";
            $this->radioXPath = "//button[normalize-space(text())='User ID']";
        }

        try {
            $this->http->GetURL('https://www.koreanair.com');
            sleep(2);

            if ($this->waitForElement(\WebDriverBy::xpath("
                    //span[contains(text(), 'This site can’t be reached') or contains(text(), 'This page isn’t working')]
                    | //h1[normalize-space()='Access Denied']
                    | //h1[normalize-space()='No internet']
                "), 0)) {
                throw new \CheckRetryNeededException(5, 0);
            }

            $award = $this->waitForElement(\WebDriverBy::xpath('//button[@id="tabBonusTrip"]'), 15);
            $this->driver->executeScript("try { document.querySelector('kc-global-cookie-banner').shadowRoot.querySelector('.-confirm').click() } catch (e) {}");

            $this->saveResponse();

            $loggedInUserInfo = $this->driver->executeScript("return sessionStorage.getItem('loggedInUserInfo')");
            $dLogin = $this->http->JsonLog($loggedInUserInfo, 1);

            if (isset($dLogin->signinStatus)
                || $this->waitForElement(\WebDriverBy::xpath(self::xpathAuth), 0)
            ) {
                return true;
            }

            if ($award) {
                $this->driver->executeScript("document.querySelector('button#tabBonusTrip').click()");
            } else {
                $this->http->GetURL('https://www.koreanair.com/login');
                $loginPage = true;
            }

            if (!$this->waitForElement(WebDriverBy::xpath($this->loginXpath), 5)) {
                $this->driver->executeScript('window.stop();');
                $this->http->GetURL('https://www.koreanair.com/login');
            }
        } catch (\UnknownServerException $e) {
            $this->logger->error("exception: " . $e->getMessage());

            throw new \CheckRetryNeededException(5, 0);
        } catch (\UnrecognizedExceptionException $e) {
            $this->logger->error("exception: " . $e->getMessage());
        }

        $radio = $this->waitForElement(\WebDriverBy::xpath($this->radioXPath), 15);

        if ($radio) {
            $radio->click();
        }

        $login = $this->waitForElement(WebDriverBy::xpath($this->loginXpath), 15);
        $this->saveResponse();

        if (!$login && !isset($loginPage)) {
            $loggedInUserInfo = $this->driver->executeScript("return sessionStorage.getItem('loggedInUserInfo')");
            $dLogin = $this->http->JsonLog($loggedInUserInfo, 1);

            if (isset($dLogin->signinStatus) || $this->waitForElement(\WebDriverBy::xpath(self::xpathAuth), 0)
            ) {
                return $this->login = true;
            }

            throw new \CheckRetryNeededException(5, 0);
        }

        if (!$this->waitForElement(WebDriverBy::xpath($this->loginXpath), 15)
            || !$this->waitForElement(WebDriverBy::xpath("//label[contains(text(),' Password ')]/following-sibling::div/input"),
                0)
            || !$this->waitForElement(WebDriverBy::xpath("//button[@type='submit' and contains(text(),'Log in')]"),
                0)) {
            $this->saveResponse();

            return false;
        }

        return true;
    }

    public function Login()
    {
        if ($this->login) {
            return true;
        }

        try {
            $mover = new \MouseMover($this->driver);
            $mover->logger = $this->logger;
            $mover->enableCursor();
            $mover->duration = random_int(40, 60) * 100;
            $mover->steps = 1;
            $mover->setCoords(0, 500);
        } catch (\ErrorException $e) {
            $this->logger->error($e->getMessage(), ['pre' => true]);

            throw new \CheckRetryNeededException(5, 0);
        }

        $login = $this->waitForElement(WebDriverBy::xpath($this->loginXpath), 0);
        $pass = $this->waitForElement(WebDriverBy::xpath("//label[contains(text(),' Password ')]/following-sibling::div/input"),
            0);
        $btn = $this->waitForElement(WebDriverBy::xpath("//button[@type='submit' and contains(text(),'Log in')]"),
            0);

        $this->driver->executeScript("try { document.querySelector('kc-global-cookie-banner').shadowRoot.querySelector('.-confirm').click() } catch (e) {}");

        if (!$login || !$pass || !$btn) {
            $this->saveResponse();

            return false;
        }

        try {
            $mover->moveToElement($login);
            $mover->click();
            $mover->sendKeys($login, $this->AccountFields['Login']);

            $mover->moveToElement($pass);
            $mover->click();
            $mover->sendKeys($pass, $this->AccountFields['Pass']);
        } catch (\Exception $e) {
            $login->sendKeys($this->AccountFields['Login']);
            $pass->sendKeys($this->AccountFields['Pass']);
        }

        sleep(1);

        $btn->click();

        if ($this->waitForElement(\WebDriverBy::xpath("
                    //span[contains(text(), 'This site can’t be reached') or contains(text(), 'This page isn’t working')]
                    | //h1[normalize-space()='Access Denied']
                    | //h1[normalize-space()='No internet']
                "), 5)) {
            throw new \CheckRetryNeededException(5, 0);
        }

        if ($flame = $this->waitForElement(\WebDriverBy::xpath('//iframe[@id="sec-cpt-if"]'), 2)) {
            $this->driver->switchTo()->frame($flame);

            do {
                try {
                    $tic = $this->waitForElement(\WebDriverBy::xpath('//div[@id="sec-ch-ctdn-timer"]'), 1,
                        false)->getText();
                    sleep(2);
                    $nextTic = $this->waitForElement(\WebDriverBy::xpath('//div[@id="sec-ch-ctdn-timer"]'), 1,
                        false)->getText();
                } catch (\Error $e) {
                    $this->logger->error('Don\'t verified...');

                    throw new \CheckRetryNeededException(5, 0);
                }

                $this->logger->debug('Verify...');
            } while ($tic !== $nextTic);

            $this->logger->error('Verified!');
            $this->driver->switchTo()->defaultContent();
        }

        $this->waitFor(function () {
            $loggedInUserInfo = $this->driver->executeScript("return sessionStorage.getItem('loggedInUserInfo')");
            $dLogin = $this->http->JsonLog($loggedInUserInfo, 1);

            return !$this->waitForElement(WebDriverBy::xpath("//button[@type='submit' and (contains(text(),'Log in') or contains(text(),'Login'))]"),
                    0) && (isset($dLogin->signinStatus) || $this->waitForElement(\WebDriverBy::xpath(self::xpathAuth), 0)
                );
        }, 5);
        $this->saveResponse();

        if (!$this->ksessionId) {
            $this->getKsessionId();
        }

        return true;
    }

    public function ParseCalendar(array $fields)
    {
        $this->logger->info("Parse Calendar", ['Header' => 2]);
        $this->logger->debug('Params: ' . var_export($fields, true));

        if ($fields['DepDate'] > strtotime('+363 day')) {
            $this->ErrorCode = ACCOUNT_WARNING;
            $this->ErrorMessage = "The requested departure date is too late.";
            $this->logger->error($this->ErrorMessage);

            return ['fares' => []];
        }

        if (!$this->ksessionId) {
            $this->getKsessionId();
        }

        $settings = $this->getRewardAvailabilitySettings();

        if (!in_array($fields['Currencies'][0], $settings['supportedCurrencies'])) {
            $fields['Currencies'][0] = $settings['defaultCurrency'];
            $this->logger->notice("parse with defaultCurrency: " . $fields['Currencies'][0]);
        }

        if (!$this->validRoute($fields)) {
            if (!$this->validRoute($fields)) {
                $this->SetWarning('This route is not in the list of award flights.');

                return ['routes' => []];
            }
        }

        $this->http->RetryCount = 0;
        $calendar = $this->getCalendar($fields);

        if (isset($calendar) && !$fields['ParseFlights']) {
            $this->logger->notice('Data ok, saving session');
            $this->keepSession(true);
        }

        return ['fares' => $this->parseCalendarJson($calendar)];
    }

    public function ParseRewardAvailability(array $fields)
    {
        $this->logger->info("Parse Reward Availability", ['Header' => 2]);
        $this->logger->debug("params: " . var_export($fields, true));

        if ($fields['DepDate'] > strtotime('+363 day')) {
            $this->ErrorCode = ACCOUNT_WARNING;
            $this->ErrorMessage = "The requested departure date is too late.";
            $this->logger->error($this->ErrorMessage);

            return ['routes' => []];
        }

        if (!$this->ksessionId) {
            $this->getKsessionId();
        }

        $settings = $this->getRewardAvailabilitySettings();

        if (!in_array($fields['Currencies'][0], $settings['supportedCurrencies'])) {
            $fields['Currencies'][0] = $settings['defaultCurrency'];
            $this->logger->notice("parse with defaultCurrency: " . $fields['Currencies'][0]);
        }

        $this->http->RetryCount = 0;

        if (!$fields['ParseCalendar']) {
            if (!$this->validRoute($fields)) {
                $this->SetWarning('This route is not in the list of award flights.');

                return ['routes' => []];
            }
        }

        $this->data = null;

        try {
            $data = $this->getDataAjax($fields);

            if (isset($data)) {
                $this->logger->notice('Data ok, saving session');
                $this->keepSession(true);
            }
        } catch (\InvalidSelectorException | \Facebook\WebDriver\Exception\InvalidSelectorException $e) {
            // sometimes help
            $this->logger->error('InvalidSelectorException: ' . $e->getMessage());
            sleep(2);

            try {
                $data = $this->getDataAjax($fields);
            } catch (\InvalidSelectorException | \Facebook\WebDriver\Exception\InvalidSelectorException $e) {
                $this->logger->error('InvalidSelectorException: ' . $e->getMessage());

                throw new \CheckRetryNeededException(5, 0);
            }
        }

        if (!isset($data->upsellBoundAvailList)) {
            if (isset($data->status) && $data->status === 'OK'
                && isset($data->message) && $data->message === 'Communication was not successful. Please try again in a few minutes.'
                && isset($data->subMessages)
                && strpos($data->subMessages[0], 'We are unable to find recommendations for your search') !== false
            ) {
                $this->logger->error($msg = 'Flights cannot be searched with the itinerary you have entered. Please search again.');
                $this->SetWarning($msg);

                return ["routes" => []];
            }

            if (isset($data->status) && $data->status === 'OK'
                && isset($data->message) && $data->message === 'Communication was not successful. Please try again in a few minutes.'
                && isset($data->subMessages)
                && strpos($data->subMessages[0], 'The requested departure date is too late.') !== false
            ) {
                $this->SetWarning($data->subMessages[0]);

                return ["routes" => []];
            }

            if (isset($data->status) && $data->status === 'OK'
                && isset($data->message)
                && isset($data->subMessages)
                && strpos($data->subMessages[0],
                    'We are unable to find departing flights for the requested outbound') !== false
            ) {
                if ($this->calendarData && $this->notFlightDay(date('Ymd', $fields['DepDate']))) {
                    $this->logger->notice('no flights on selected day');
                    $this->SetWarning('There are no remaining seats in the outbound flights on the date selected');

                    return [
                        "routes" => [],
                    ];
                }

                if (strpos($data->message,
                        'There are no remaining seats in the outbound flights on the date selected') !== false) {
                    $this->SetWarning('There are no remaining seats in the outbound flights on the date selected');

                    return [
                        "routes" => [],
                    ];
                }

                throw new \CheckRetryNeededException(5, 0);
            }

            if (isset($data->status) && $data->status === 'OK'
                && isset($data->message) && $data->message === 'Please proceed after log-in.'
            ) {
                $this->logger->error('Please proceed after log-in.');

                throw new \CheckRetryNeededException(5, 0);
            }

            throw new \CheckException('Something went wrong', ACCOUNT_ENGINE_ERROR);
        }

        return [
            "routes" => $this->parseRewardFlights($data),
        ];
    }

    private function notFlightDay($dateString)
    {
        if (!isset($this->calendarData->boundFareCalendarList)
            || !isset($this->calendarData->boundFareCalendarList[0])
            || !isset($this->calendarData->boundFareCalendarList[0]->fareCalendarList)
            || !is_array($this->calendarData->boundFareCalendarList[0]->fareCalendarList)
        ) {
            return false;
        }
        $noFlights = $hasDay = false;

        foreach ($this->calendarData->boundFareCalendarList[0]->fareCalendarList as $bound) {
            if ($bound->departureDate !== $dateString) {
                continue;
            }

            if (isset($bound->travellerTypeFareInfoList)
                && is_array($bound->travellerTypeFareInfoList)
                && empty($bound->travellerTypeFareInfoList)
                && empty($bound->currency)
            ) {
                $noFlights = true;
            }
            $hasDay = true;

            break;
        }

        if ($hasDay) {
            return $noFlights;
        }

        return false;
    }

    private function getKsessionId()
    {
        $this->logger->notice(__METHOD__);

        try {
            $this->ksessionId = $this->driver->executeScript("return sessionStorage.getItem('ksessionId')");
        } catch (\UnexpectedJavascriptException | \WebDriverCurlException | WebDriverException $e) {
            $this->logger->error("KsessionId not found");

            throw new \CheckRetryNeededException(5, 0);
        }
        $this->logger->debug($this->ksessionId);
    }

    private function getDataAjax($fields)
    {
        $this->logger->notice(__METHOD__);

        try {
            if (!$this->loginInfo) {
                $returnData = $this->driver->executeScript($tt = /** @lang JavaScript */
                    '
                        var xhttp = new XMLHttpRequest();
                        xhttp.open("GET", "https://www.koreanair.com/api/li/auth/isUserLoggedIn", false);

                        var responseText = null;
                        xhttp.onreadystatechange = function() {
                            if (this.readyState == 4 && this.status == 200) {
                                responseText = this.responseText;
                            }
                        };
                        xhttp.send();
                        return responseText;
                    '
                );
                $this->logger->debug($tt, ['pre' => true]);

                if (!$returnData) {
                    return null;
                }
                $response = $this->http->JsonLog($returnData);

                if (!$response || !isset($response->userInfo->skypassNumber) || $response->userInfo->skypassNumber === '000000000000') {
                    return null;
                }
                $this->loginInfo = $response;
            }

            $data = $this->getPayloadMain($fields);

            $data = str_replace('"', '\"', json_encode($data));

            $this->driver->executeScript($tt = /** @lang JavaScript */ '
                fetch("https://www.koreanair.com/api/ap/booking/avail/awardAvailability", {
                    "headers": {
                        "accept": "application/json",
                        "channel": "pc",
                        "content-type": "application/json",
                        "ksessionid": "' . ($this->ksessionId) . '",
                        "x-queueit-ajaxpageurl": "https%3A%2F%2Fwww.koreanair.com%2Fbooking%2Fselect-award-flight%2Fdeparture"
                    },
                    "origin": "https://www.koreanair.com",
                    "referrer": "https://www.koreanair.com/booking/select-award-flight/departure",
                    "body": "' . $data . '",
                    "method": "POST",
                    "mode": "cors",
                }).then( response => response.json())
                    .then( result => {
                        let script = document.createElement("script");
                        let id = "dataPrice";
                        script.id = id;
                        script.setAttribute(id, JSON.stringify(result));
                        document.querySelector("body").append(script);
                    }).catch(error => {                    
                        let newDiv = document.createElement("div");
                        let id = "error";
                        newDiv.id = id;
                        let newContent = document.createTextNode(error);
                        newDiv.appendChild(newContent);
                        document.querySelector("body").append(newDiv);
                    });
               ');

            //Нужно ожидание пока Запрос отработает
            sleep(10);
            $this->saveResponse();

            $fetchData = $this->http->FindSingleNode('//script[@id="dataPrice"]/@dataprice');

            if (!isset($fetchData)) {
                throw new \CheckRetryNeededException(5, 0);
            }
            $returnData = $this->http->JsonLog($fetchData);

            if (isset($returnData->verify_url)) {
                $this->logger->error('Verify this request');

                $flame = $this->waitForElement(\WebDriverBy::xpath('//iframe[@id="sec-cpt-if"]'), 0);

                if (!$flame) {
                    throw new \CheckRetryNeededException(5, 0);
                }

                $this->driver->switchTo()->frame($flame);

                do {
                    try {
                        $tic = $this->waitForElement(\WebDriverBy::xpath('//div[@id="sec-ch-ctdn-timer"]'), 1, false)->getText();
                        sleep(2);
                        $nextTic = $this->waitForElement(\WebDriverBy::xpath('//div[@id="sec-ch-ctdn-timer"]'), 1, false)->getText();
                    } catch (\Error $e) {
                        $this->logger->error('Don\'t verified...');

                        throw new \CheckRetryNeededException(5, 0);
                    }

                    $this->logger->debug('Verify...');
                } while ($tic !== $nextTic);
                $this->logger->error('Verified!');
                $this->driver->switchTo()->defaultContent();

                sleep(3);
                $this->saveResponse();
                $this->driver->executeScript($tt);

                sleep(10);
                $this->saveResponse();

                $fetchData = $this->http->FindSingleNode('//script[@id="dataPrice"][2]/@dataprice');

                if (!isset($fetchData)) {
                    throw new \CheckRetryNeededException(5, 0);
                }
                $returnData = $this->http->JsonLog($fetchData);

                if (isset($returnData->verify_url)) {
                    throw new \CheckRetryNeededException(5, 0);
                }
            }
        } catch (\InvalidSelectorException | \Facebook\WebDriver\Exception\ScriptTimeoutException $e) {
            // retry help
            $this->logger->error('InvalidSelectorException: ' . $e->getMessage());
            $this->logger->debug($tt, ['pre' => true]);

            try {
                $returnData = $this->driver->executeScript($tt);
            } catch (\InvalidSelectorException | \Facebook\WebDriver\Exception\ScriptTimeoutException $e) {
                $this->logger->error('InvalidSelectorException: ' . $e->getMessage());

                throw new \CheckRetryNeededException(5, 0);
            }
        } catch (\WebDriverCurlException | \Facebook\WebDriver\Exception\WebDriverCurlException
        | \WebDriverException | \Facebook\WebDriver\Exception\WebDriverException $e) {
            $this->logger->error('WebDriverException: ' . $e->getMessage());

            throw new \CheckRetryNeededException(5, 0);
        }

        return $returnData;
    }

    private function getCalendar($fields)
    {
        $this->logger->notice(__METHOD__);

        $data = $this->getPayloadCalendar($fields);

        try {
            $calendarData = $this->driver->executeScript($tt = /** @lang JavaScript */
                '
                    var xhttp = new XMLHttpRequest();
                    xhttp.withCredentials = true;
                    xhttp.open("POST", "https://www.koreanair.com/api/ap/booking/avail/calendarFareMatrix", false);
                    xhttp.setRequestHeader("Content-type", "application/json");
                    xhttp.setRequestHeader("Accept", "application/json");
                    xhttp.setRequestHeader("ksessionid", "' . $this->ksessionId . '");
                    xhttp.setRequestHeader("channel", "pc");
                    xhttp.setRequestHeader("Connection", "keep-alive");
                    xhttp.setRequestHeader("Accept-Encoding", "gzip, deflate, br");
                    xhttp.setRequestHeader("x-sec-clge-req-type", "ajax");
                    xhttp.setRequestHeader("Referer", "https://www.koreanair.com/booking/calendar-fare-bonus");


                    var data = JSON.stringify(' . json_encode($data) . ');
                    var responseText = null;
                    xhttp.onreadystatechange = function() {
                        if (this.readyState == 4 && this.status == 200) {
                            responseText = this.responseText;
                        }
                    };
                    xhttp.send(data);
                    return responseText;
                '
            );
        } catch (\InvalidSelectorException | \Facebook\WebDriver\Exception\ScriptTimeoutException $e) {
            $this->logger->error('Exception: ' . $e->getMessage());
            $this->logger->debug($tt, ['pre' => true]);
            sleep(2);

            try {
                $this->logger->debug("retry script calendarData");
                $calendarData = $this->driver->executeScript($tt);
            } catch (\InvalidSelectorException | \Facebook\WebDriver\Exception\ScriptTimeoutException $e) {
                $this->logger->error('Exception: ' . $e->getMessage());

                throw new \CheckRetryNeededException(5, 0);
            }
        } catch (\WebDriverCurlException | \Facebook\WebDriver\Exception\WebDriverCurlException
        | \WebDriverException | \Facebook\WebDriver\Exception\WebDriverException | \UnexpectedJavascriptException $e) {
            $this->logger->error('WebDriverException: ' . $e->getMessage());

            throw new \CheckRetryNeededException(5, 0);
        }

        $this->logger->debug($tt, ['pre' => true]);
        $calendarData = $this->http->JsonLog($calendarData, 0, true);

        if (isset($calendarData->message)
            && $calendarData->message === 'Communication was not successful. Please try again in a few minutes.'
        ) {
            $this->logger->notice('no flights on selected day');
            $this->SetWarning('No seats are available not only on the boarding date you have entered, but also nearby dates.');
            $data = ["routes" => []];

            return $data;
        }

        if ($calendarData && $this->notFlightDay(date('Ymd', $fields['DepDate']))) {
            $this->logger->notice('no flights on selected day');
            $this->SetWarning('There are no remaining seats in the outbound flights on the date selected');
            $data = ["routes" => []];

            return $data;
        }

        return $calendarData;
    }

    private function getPayloadMain($fields)
    {
        return [
            'commercialFareFamilies' => ['KEBONUSALL'],
            'currency'               => $fields['Currencies'][0],
            'sta'                    => false,
            'segmentList'            => [
                [
                    'departureDate'    => date('Ymd', $fields['DepDate']),
                    'departureAirport' => $this->deepAirportCode,
                    'arrivalAirport'   => $this->arrAirportCode,
                ],
            ],
            'travelers' => [
                [
                    'travellerType' => 'ADT',
                    'fqtvNumber'    => $this->loginInfo->userInfo->skypassNumber ?? null,
                    'lastName'      => $this->loginInfo->userInfo->englishLastName ?? null,
                    'firstName'     => $this->loginInfo->userInfo->englishFirstName ?? null,
                ],
            ],
            'corporateCode' => 'string',
        ];
    }

    private function getPayloadCalendar($fields)
    {
        return [
            'commercialFareFamilies' => ['KEBONUSALL'],
            'sta'                    => false,
            'adult'                  => $fields['Adults'],
            'child'                  => 0,
            'infant'                 => 0,
            'segmentList'            => [
                [
                    'departureDate'    => date('Ymd', $fields['DepDate']),
                    'departureAirport' => $this->deepAirportCode,
                    'arrivalAirport'   => $this->arrAirportCode,
                ],
            ],
            'travelers' => [
                [
                    'travellerType' => 'ADT',
                    'fqtvNumber'    => $this->loginInfo->userInfo->skypassNumber ?? null,
                    'lastName'      => $this->loginInfo->userInfo->englishLastName ?? null,
                    'firstName'     => $this->loginInfo->userInfo->englishFirstName ?? null,
                    'discountCode'  => '',
                ],
            ],
            'corporateCode' => '',
            'type'          => '[KeCalendarFareBonusMatrixRequest] Set KeCalendarFareBonusMatrixRequest',
        ];
    }

    private function getCabin(string $cabin, bool $isFlip = true)
    {
        $cabins = [
            'economy'        => 'X', // Economy Class
            'premiumEconomy' => 'O', // Prestige Class
            //            'business' => ' ',
            'firstClass' => 'A', // First Class
        ];

        if ($isFlip) {
            $cabins = array_flip($cabins);
        }

        if (isset($cabins[$cabin])) {
            return $cabins[$cabin];
        }
        $this->sendNotification("RA check cabin {$cabin} (" . var_export($isFlip, true) . ") // DM");

        throw new \CheckException("check cabin {$cabin} (" . var_export($isFlip, true) . ")", ACCOUNT_ENGINE_ERROR);
    }

    private function getAwardType(string $cabin)
    {
        $cabins = [
            'X' => 'Economy Class', // Economy Class
            'O' => 'Prestige Class', // Prestige Class
            //            'business' => ' ',
            'A' => 'First Class', // First Class
        ];

        if (isset($cabins[$cabin])) {
            return $cabins[$cabin];
        }
        $this->sendNotification("RA check cabin {$cabin} // DM");

        throw new \CheckException("check cabin {$cabin}", ACCOUNT_ENGINE_ERROR);
    }

    private function parseRewardFlights($data): array
    {
        $this->logger->notice(__METHOD__);
        $routes = [];

        if (count($data->upsellBoundAvailList) > 1) {
            $this->sendNotification('RA chech upsellBoundAvailList // MI');

            throw new \CheckException('upsellBoundAvailList > 1', ACCOUNT_ENGINE_ERROR);
        }

        foreach ($data->upsellBoundAvailList[0]->availFlightList as $availFlightList) {
            foreach ($availFlightList->commercialFareFamilyList as $fare) {
                if ($fare->soldout === true) {
                    $this->logger->notice('Skip soldOut');
                    $skipped = true;

                    continue;
                }
                $route = [
                    'distance'  => null,
                    'num_stops' => $availFlightList->numberOfStops,
                    'times'     => [
                        'flight'  => $availFlightList->totalFlyingTime,
                        'layover' => null,
                    ],
                    'redemptions' => [
                        'miles'   => $fare->travellerTypeFareList[0]->mileage,
                        'program' => $this->AccountFields['ProviderCode'],
                    ],
                    'payments' => [
                        'currency' => $fare->travellerTypeFareList[0]->currency,
                        'taxes'    => $fare->travellerTypeFareList[0]->totalAmount,
                        'fees'     => null,
                    ],
                    'connections'    => [],
                    'tickets'        => $fare->seatCount,
                    'award_type'     => $this->getAwardType($fare->bookingClass),
                    'classOfService' => $this->clearCOS($this->getAwardType($fare->bookingClass)),
                ];

                foreach ($availFlightList->flightInfoList as $flight) {
                    $route['connections'][] = [
                        'departure' => [
                            'date'     => date('Y-m-d H:i', strtotime($flight->departureDateTime)),
                            'dateTime' => strtotime($flight->departureDateTime),
                            'airport'  => $flight->departureAirport,
                            'terminal' => $flight->departureTerminal ?? null,
                        ],
                        'arrival' => [
                            'date'     => date('Y-m-d H:i', strtotime($flight->arrivalDateTime)),
                            'dateTime' => strtotime($flight->arrivalDateTime),
                            'airport'  => $flight->arrivalAirport,
                            'terminal' => $flight->arrivalTerminal ?? null,
                        ],
                        'meal'       => null,
                        'cabin'      => $this->getCabin($fare->bookingClass),
                        'fare_class' => $fare->bookingClass,
                        'flight'     => ["{$flight->carrierCode}{$flight->flightNumber}"],
                        'airline'    => $flight->carrierCode,
                        'operator'   => $flight->operationCarrierCode,
                        'distance'   => null,
                        'aircraft'   => $flight->aircraftTypeDesc,
                        'times'      => [
                            'flight'  => $flight->flyingTime,
                            'layover' => null,
                        ],
                    ];
                }
                $this->logger->debug('Parsed data:');
                $this->logger->debug(var_export($route, true), ['pre' => true]);
                $routes[] = $route;
            }
        }

        if (empty($routes) && isset($skipped)) {
            $this->SetWarning('All tickets are sold out');
        }

        if (empty($routes) && empty($data->upsellBoundAvailList[0]->availFlightList)) {
            $this->SetWarning('No flights found');
        }

        return $routes;
    }

    private function parseCalendarJson($calendar)
    {
        $this->logger->notice(__METHOD__);
        $this->logger->info("Parse Result Calendar", ['Header' => 3]);
        $result = [];

        if (empty($calendar['boundFareCalendarList'])) {
            $this->logger->warning("No has data");

            throw new \CheckRetryNeededException(5, 0);
        }

        foreach ($calendar['boundFareCalendarList'][0]['fareCalendarList'] as $day) {
            if ($day['emptyFare']) {
                continue;
            }

            $dateTime = \DateTime::createFromFormat('Ymd', $day['departureDate']);

            foreach ($day['travellerTypeFareInfoList'] as $num => $value) {
                $result[] = [
                    'date'        => $dateTime->format('Y-m-d'),
                    'redemptions' => ['miles' => $value[0]['mileage']],
                    'payments'    => [
                        'currency' => $day['currency'],
                        'taxes'    => $value[0]['totalAmount'],
                        'fees'     => null,
                    ],
                    'cabin'        => $this->getCabinFromCalendar($day['fareFamilyList'][$num]),
                    'brandedCabin' => $this->getBrandedCabinID($day['fareFamilyList'][$num]),
                ];
            }
        }

        $this->logger->debug(var_export($result, true), ['pre' => true]);

        if (empty($result)) {
            $this->SetWarning('There are no flights for the this month');
        }

        return $result;
    }

    private function getCabinFromCalendar(string $cabin)
    {
        $cabins = [
            'KEBONUSEY' => 'economy', // Economy Class
            'KEBONUSPR' => 'premiumEconomy', // Prestige Class
            //            'business' => ' ',
            'KEBONUSFC' => 'firstClass', // First Class
        ];

        if (isset($cabins[$cabin])) {
            return $cabins[$cabin];
        }
        $this->sendNotification("RA check cabin Calendar {$cabin} // DM");

        throw new \CheckException("check cabin Calendar {$cabin}", ACCOUNT_ENGINE_ERROR);
    }

    private function getBrandedCabinID(string $cabin): string
    {
        $cabins = [
            //For Calendar brandedCabin
            'KEBONUSEY' => 'Economy Class', // Economy Class
            'KEBONUSPR' => 'Prestige Class', // Prestige Class
            //            'business' => ' ',
            'KEBONUSFC' => 'First Class', // First Class
        ];

        if (isset($cabins[$cabin])) {
            return $cabins[$cabin];
        }

        $this->sendNotification("RA check Brandede cabin Calendar {$cabin} // DM");

        throw new \CheckException("check cabin Brandede Calendar {$cabin}", ACCOUNT_ENGINE_ERROR);
    }

    private function validRoute($fields): bool
    {
        $this->logger->notice(__METHOD__);

        $airports = \Cache::getInstance()->get('ra_korean_airports2');

        $browser = new \HttpBrowser("none", new \CurlDriver());

        $browser->SetProxy("{$this->http->getProxyAddress()}:{$this->http->getProxyPort()}");
        $browser->setProxyAuth($this->http->getProxyLogin(), $this->http->getProxyPassword());
        $browser->setUserAgent($this->http->getDefaultHeader("User-Agent"));

        $this->http->brotherBrowser($browser);

        $browser->setDefaultHeader("Upgrade-Insecure-Requests", "1");
        $browser->setDefaultHeader("Connection", "keep-alive");
        $browser->setDefaultHeader('Accept-Encoding', 'gzip, deflate, br');

        if (!$airports || !is_array($airports)) {
            $airports = [];

            $browser->GetURL("https://www.koreanair.com/api/et/route/c/a/getReservationAirport?airportCode=&directionType=D&flowType=NR&langCode=en&nationCode=us&tripType=RO",
                [], 20);

            if ($this->isBadProxy()
                || strpos($browser->Error, 'Network error 28 - Connection timed out after') !== false
                || strpos($browser->Error, 'Network error 92 - HTTP/2 stream 0 was not closed cleanly') !== false
            ) {
                $this->setProxyGoProxies(null, 'kr');

                $browser->SetProxy("{$this->http->getProxyAddress()}:{$this->http->getProxyPort()}");
                $browser->setProxyAuth($this->http->getProxyLogin(), $this->http->getProxyPassword());
                $browser->setUserAgent($this->http->getDefaultHeader("User-Agent"));

                $browser->GetURL("https://www.koreanair.com/api/et/route/c/a/getReservationAirport?airportCode=&directionType=D&flowType=NR&langCode=en&nationCode=us&tripType=RO");

                if ($this->isBadProxy()
                    || strpos($browser->Error, 'Network error 28 - Connection timed out after') !== false
                    || strpos($browser->Error, 'Network error 92 - HTTP/2 stream 0 was not closed cleanly') !== false) {
                    throw new \CheckRetryNeededException(5, 0);
                }
            }
            $data = $browser->JsonLog(null, 1, true);

            foreach ($data['locationInfoList'] as $locationInfoList) {
                $airports[] = $locationInfoList['airportCode'];
            }

            if (!empty($airports)) {
                \Cache::getInstance()->set('ra_korean_airports2', $airports, 60 * 60 * 24);
            }
        }

        foreach ($airports as $airportCode) {
            if (strcasecmp($airportCode, $fields['DepCode']) === 0) {
                $this->deepAirportCode = $airportCode;
            }

            if (strcasecmp($airportCode, $fields['ArrCode']) === 0) {
                $this->arrAirportCode = $airportCode;
            }

            if ($this->deepAirportCode && $this->arrAirportCode) {
                break;
            }
        }

        if ($this->deepAirportCode && $this->arrAirportCode) {
            $route = "{$this->deepAirportCode}-{$this->arrAirportCode}";
        } else {
            $browser->cleanup();

            return false;
        }

        $data = \Cache::getInstance()->get('ra_korean_award_route2' . $this->deepAirportCode . '-' . $this->arrAirportCode);

        if (!$data) {
            $browser->GetURL("https://www.koreanair.com/api/et/route/c/a/isAwardRoute?routeList={$route}",
                [], 20);

            if ($this->isBadProxy() || strpos($browser->Error,
                    'Network error 28 - Connection timed out after') !== false) {
                $this->setProxyGoProxies();

                $browser->GetURL("https://www.koreanair.com/api/et/route/c/a/isAwardRoute?routeList={$route}",
                    [], 20);
            }
            $data = $browser->JsonLog();

            if ($this->isBadProxy()) {
                throw new \CheckRetryNeededException(5, 1);
            }

            if (!empty($data)) {
                \Cache::getInstance()->set('ra_korean_award_route2' . $this->deepAirportCode . '-' . $this->arrAirportCode,
                    $data, 60 * 60 * 24 * 2);
            }
        }

        $browser->cleanup();

        return !isset($data->award) || $data->award === true;
    }

    private function clearCOS(string $cos): string
    {
        if (preg_match("/^(.+\w+) (?:cabin|class|standard|reward)$/i", $cos, $m)) {
            $cos = $m[1];
        }

        return $cos;
    }
}
