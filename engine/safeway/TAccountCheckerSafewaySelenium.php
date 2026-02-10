<?php

use AwardWallet\Engine\ProxyList;

class TAccountCheckerSafewaySelenium extends TAccountChecker
{
    use SeleniumCheckerHelper;
    use ProxyList;
    private const WAIT_TIMEOUT = 10;
    private const SHORT_TIMEOUT = 5;
    private const WITHOUT_TIMEOUT = 5;

    private const CONFIGS = [
        /*
        // works, but calls Incapsula with active use
        'firefox-100' => [
            'agent'           => "Mozilla/5.0 (Macintosh; Intel Mac OS X 12_2_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/98.0.4758.102 Safari/537.36",
            'browser-family'  => SeleniumFinderRequest::BROWSER_FIREFOX,
            'browser-version' => SeleniumFinderRequest::FIREFOX_100,
        ],
        */

        /*
        // not tested but i think it calls incapsula too
        'firefox-playwright-100' => [
            'browser-family'  => SeleniumFinderRequest::BROWSER_FIREFOX_PLAYWRIGHT,
            'browser-version' => SeleniumFinderRequest::FIREFOX_PLAYWRIGHT_100,
        ],
        'firefox-playwright-102' => [
            'browser-family'  => SeleniumFinderRequest::BROWSER_FIREFOX_PLAYWRIGHT,
            'browser-version' => SeleniumFinderRequest::FIREFOX_PLAYWRIGHT_102,
        ],
        'firefox-playwright-101' => [
            'browser-family'  => SeleniumFinderRequest::BROWSER_FIREFOX_PLAYWRIGHT,
            'browser-version' => SeleniumFinderRequest::FIREFOX_PLAYWRIGHT_101,
        ],
        */

        // works but crashes in ProcessStep with unable to save response so i cant debug it
        'chrome-100' => [
            'agent'           => "Mozilla/5.0 (Macintosh; Intel Mac OS X 12_2_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/98.0.4758.102 Safari/537.36",
            'browser-family'  => SeleniumFinderRequest::BROWSER_CHROME,
            'browser-version' => SeleniumFinderRequest::CHROME_100,
        ],

        /*
        // works but has an error the same as firefox-84
        'chromium-80' => [
            'agent'           => "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/95.0.4638.69 Safari/537.36",
            'browser-family'  => SeleniumFinderRequest::BROWSER_CHROMIUM,
            'browser-version' => SeleniumFinderRequest::CHROMIUM_80,
        ],
        'chrome-84' => [
            'agent'           => "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/95.0.4638.69 Safari/537.36",
            'browser-family'  => SeleniumFinderRequest::BROWSER_CHROME,
            'browser-version' => SeleniumFinderRequest::CHROME_84,
        ],
        'chrome-95' => [
            'agent'           => "Mozilla/5.0 (Macintosh; Intel Mac OS X 12_2_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/98.0.4758.102 Safari/537.36",
            'browser-family'  => SeleniumFinderRequest::BROWSER_CHROME,
            'browser-version' => SeleniumFinderRequest::CHROME_95,
        ],
        */

        /*
        // works but calls error 401 hull screen in center of the page
        'puppeteer-103' => [
            'agent'           => "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:97.0) Gecko/20100101 Firefox/97.0",
            'browser-family'  => SeleniumFinderRequest::BROWSER_CHROME_PUPPETEER,
            'browser-version' => SeleniumFinderRequest::CHROME_PUPPETEER_103,
        ],
        'chrome-94-mac' => [
            'browser-family'  => \SeleniumFinderRequest::BROWSER_CHROME,
            'browser-version' => \SeleniumFinderRequest::CHROME_94,
        ],
        */

        /*
        // has an error, page lagging and dont loads completely
        'firefox-84' => [
            'agent'           => "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:97.0) Gecko/20100101 Firefox/97.0",
            'browser-family'  => SeleniumFinderRequest::BROWSER_FIREFOX,
            'browser-version' => SeleniumFinderRequest::FIREFOX_84,
        ],
        */
    ];
    public $regionOptions = [
        ""             => "Select your brand",
        "safeway"      => "Safeway",
        "acmemarkets"  => "Acme",
        // refs #7743
        //        "dominicks" => "Dominick's",
        "vons"     => "Vons",
        "tomthumb" => "Tom Thumb",
    ];
    private $responseDataProfile;
    private $responseDataAuth;
    private $responseDataRewards;
    private $curlChecker;
    private $config;

    public function InitBrowserOld()
    {
        parent::InitBrowser();
        $this->KeepState = true;
        $this->AccountFields['Login2'] = $this->checkRegionSelection($this->AccountFields['Login2']);
        $this->logger->notice('Region => ' . $this->AccountFields['Login2']);
        $this->http->setDefaultHeader("X-SWY_BANNER", $this->AccountFields['Login2']);
        $this->http->setHttp2(true);
        $this->UseSelenium();
        $this->useFirefox(SeleniumFinderRequest::FIREFOX_84);
        $this->seleniumOptions->fingerprintParams = FingerprintParams::vanillaFirefox();
        $this->seleniumOptions->recordRequests = true;
        $this->http->saveScreenshots = true;
    }

    public function initBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
        $this->AccountFields['Login2'] = $this->checkRegionSelection($this->AccountFields['Login2']);
        $this->logger->notice('Region => ' . $this->AccountFields['Login2']);
        $this->UseSelenium();
        $this->setConfig();

        $resolutions = [
            /*
            [1152, 864],
            [1280, 720],
            [1280, 768],
            [1280, 800],
            [1360, 768],
            [1366, 768],
            */
            [1920, 1080],
        ];

        $resolution = $resolutions[array_rand($resolutions)];
        $this->setScreenResolution($resolution);

        $this->seleniumRequest->setOs(SeleniumFinderRequest::OS_MAC);
        $this->seleniumOptions->addHideSeleniumExtension = false;
        $this->seleniumOptions->userAgent = null;

        $this->seleniumRequest->request(
            self::CONFIGS[$this->config]['browser-family'],
            self::CONFIGS[$this->config]['browser-version']
        );

        /*
        $this->usePacFile(false);
        */
        $this->setProxyGoProxies();
        $this->http->saveScreenshots = true;
    }

    /*
    public function IsLoggedIn()
    {
        $this->http->GetURL("https://www.{$this->AccountFields['Login2']}.com");
        $this->waitForElement(WebDriverBy::xpath('//div[@class="account-review"]/div[contains(@class, "name")]'), self::WAIT_TIMEOUT);
        $this->saveResponse();

        if ($this->http->FindSingleNode('//div[@class="account-review"]/div[contains(@class, "name")]')) {
            return true;
        }

        return false;
    }
    */

    public function LoadLoginForm()
    {
        $this->http->removeCookies();
        /*
        $this->http->GetURL("https://www.{$this->AccountFields['Login2']}.com/customer-account/rewards");
        */
        $this->http->GetURL("https://www.{$this->AccountFields['Login2']}.com");
        $this->saveResponse();

        $openLoginModalButton = $this->waitForElement(WebDriverBy::xpath('//span[contains(@class, "user-greeting")]'), self::WAIT_TIMEOUT);

        if (!isset($openLoginModalButton)) {
            return $this->CheckErrors();
        }
        $openLoginModalButton->click();
        $openLoginFormButton = $this->waitForElement(WebDriverBy::xpath('//div[@id="signin-dropdown"]//button[not(contains(@onclick, "createAccount"))]'), self::SHORT_TIMEOUT);

        if (!isset($openLoginFormButton)) {
            return $this->CheckErrors();
        }
        $openLoginFormButton->click();
        $login = $this->waitForElement(WebDriverBy::xpath('//input[@id="label-email"]'), self::SHORT_TIMEOUT);
        $pwd = $this->waitForElement(WebDriverBy::xpath('//input[@id="label-password"]'), self::WITHOUT_TIMEOUT);
        $btn = $this->waitForElement(WebDriverBy::xpath('//input[@id="btnSignIn"]'), self::WITHOUT_TIMEOUT);

        if (!isset($login, $pwd, $btn)) {
            $this->saveResponse();
            $login = $this->waitForElement(WebDriverBy::xpath('//input[@id = "enterUsername"]'), self::WITHOUT_TIMEOUT);

            if (!$login) {
                if (
                    in_array($this->AccountFields['Login'], [
                        'esdo.tyang@gmail.com',
                    ])
                ) {
                    throw new CheckException(self::PROVIDER_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
                }

                return false;
            }

            $login->sendKeys($this->AccountFields['Login']);
            $btn = $this->waitForElement(WebDriverBy::xpath('//button[contains(text(), "Sign in with password") and not(@disabled)]'), self::WAIT_TIMEOUT);

            if (!$btn) {
                $this->saveResponse();

                return false;
            }

            $btn->click();
            $this->saveResponse();

            $pwd = $this->waitForElement(WebDriverBy::xpath('//input[@id = "password"]'), self::WAIT_TIMEOUT);
            $this->saveResponse();

            if (!$pwd) {
                if ($el = $this->waitForElement(WebDriverBy::xpath('//div[contains(@class, "popup-light-error alert fade show")]'), self::WITHOUT_TIMEOUT)) {
                    $message = $el->getText();
                    $this->logger->error("[Error]: {$message}");

                    if (strstr($message, 'We couldn\'t find an account with this email address.')) {
                        throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
                    }

                    $this->DebugInfo = $message;
                }

                if (
                    in_array($this->AccountFields['Login'], [
                        'ereiner83@alum.mit.edu',
                        'dhrebec@gmail.com',
                        'jwells.per@gmail.com',
                        'jtmoneymeyerz@gmail.com',
                        'dave@thekregs.com',
                        'Davidylin@yahoo.com',
                        'john@gilham.net',
                    ])
                ) {
                    throw new CheckException(self::PROVIDER_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
                }

                return false;
            }

            $pwd->sendKeys($this->AccountFields['Pass']);
            $btn = $this->waitForElement(WebDriverBy::xpath('//button[@aria-label="Sign in" and not(@disabled)]'), self::SHORT_TIMEOUT);

            if (!$btn) {
                $this->saveResponse();

                return false;
            }

            $btn->click();
        } else {
            $login->sendKeys($this->AccountFields['Login']);
            $pwd->sendKeys($this->AccountFields['Pass']);
            $this->saveResponse();

            // sometimes credentials are not inserted by sendKeys, strange bug
            if ($login->getAttribute('value') != $this->AccountFields['Login']
                || $pwd->getAttribute('value') != $this->AccountFields['Pass']
            ) {
                $this->driver->executeScript("document.getElementById('label-password').value = '{$this->AccountFields['Pass']}';");
                $login->click();
                $this->driver->executeScript("document.getElementById('label-email').value = '{$this->AccountFields['Login']}';");
                $pwd->click();
            }

            if ($validationError = $this->waitForElement(WebDriverBy::cssSelector('.form-group.has-error .errorMessage li'), self::SHORT_TIMEOUT)) {
                throw new CheckException($validationError->getText(), ACCOUNT_INVALID_PASSWORD);
            }

            $btn->click();
        }

        $el = $this->waitForElement(WebDriverBy::xpath('
            //span[contains(@class, "dst-sign-in-up user-greeting") and starts-with(text(), "Hi, ")]
            | //div[contains(@class, "errorMessage")]/ul/li
            | //div[@id = "error-message"]
            | //p[contains(text(), "Sorry, we\'re having technical difficulties, please check back later")]
            | //p[contains(text(), "Lifetime Savings:")]
            | //div[contains(@class, "popup-light-error alert fade show")]
        '), self::WAIT_TIMEOUT);
        $this->saveResponse();

        if ($providerError = $this->waitForElement(WebDriverBy::xpath("//p[contains(text(), \"Sorry, we're having technical difficulties, please check back later.\")]"), self::WITHOUT_TIMEOUT)) {
            throw new CheckException($providerError->getText(), ACCOUNT_PROVIDER_ERROR);
        }

        try {
            $success = $el
                && (stripos($el->getText(), 'Hi, ') !== false || stripos($el->getText(), 'Lifetime Savings:') !== false);
        } catch (StaleElementReferenceException $e) {
            $this->logger->error("StaleElementReferenceException: " . $e->getMessage());
            sleep(self::SHORT_TIMEOUT);
            $el = $this->waitForElement(WebDriverBy::xpath('//span[contains(@class, "dst-sign-in-up user-greeting") and starts-with(text(), "Hi, ")]| //p[contains(text(), "Lifetime Savings:")]'), self::WITHOUT_TIMEOUT);
            $success = $el
                && (stripos($el->getText(), 'Hi, ') !== false || stripos($el->getText(), 'Lifetime Savings:') !== false);
        }

        if ($success) {
            $this->driver->executeScript('document.querySelector(".dst-sign-in-up.user-greeting").click();');
            $btnToProfile = $this->waitForElement(WebDriverBy::xpath('//a[@href = "/customer-account/account-settings"]'), self::SHORT_TIMEOUT);

            if ($btnToProfile) {
                $this->driver->executeScript('document.querySelector(\'a[href="/customer-account/account-settings"]\').click();');
                sleep(self::SHORT_TIMEOUT);
            }
        }
        /*
        $this->getRecordedRequests();

        if (!empty($this->responseDataAuth) || !empty($this->responseDataProfile)) {
            $this->http->SetBody($this->responseDataAuth ?? $this->responseDataProfile);
        }
        */

        $this->saveResponse();

        return true;
    }

    public function ProcessStep($step)
    {
        $this->setProxyGoProxies();
        $answer = $this->Answers[$this->Question];
        unset($this->Answers[$this->Question]);

        $input = $this->waitForElement(WebDriverBy::xpath('//input[@formcontrolname="otpCode"]'), self::WAIT_TIMEOUT);
        $submit = $this->waitForElement(WebDriverBy::xpath('//button[contains(@class, "auth-styles") and @type="submit"]'), self::SHORT_TIMEOUT);
        $this->saveResponse();

        if (!isset($input, $submit)) {
            return false;
        }

        $this->logger->debug('entering answer');
        $input->clear();
        $input->sendKeys($answer);
        $this->saveResponse();
        $this->logger->debug('clicking submit');
        $submit = $this->waitForElement(WebDriverBy::xpath('//button[contains(@class, "auth-styles") and @type="submit" and not(@disabled)]'), self::SHORT_TIMEOUT);
        $submit->click();
        sleep(5);
        $this->saveResponse();

        $error = $this->http->FindSingleNode('//div[contains(@class, "popup") and contains(@class, "error")]//div[contains(@class, "body-text")]');
        $this->saveResponse();

        if (isset($error)) {
            $this->holdSession();
            $this->AskQuestion($this->Question, $error, 'Question');

            return false;
        }

        $error = $this->http->FindSingleNode('//div[contains(@class, "help-text") and contains(@class, "error")]');
        $this->saveResponse();

        if (isset($error)) {
            $this->holdSession();
            $this->AskQuestion($this->Question, $error, 'Question');

            return false;
        }

        $error = $this->http->FindSingleNode('//div[@class="error-code"]');

        if (isset($error)) {
            if (strstr($error, "HTTP ERROR 401")) {
                throw new CheckException(self::PROVIDER_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
            }

            $this->DebugInfo = $error;

            return false;
        }

        $incapsula = $this->http->FindPreg("/_Incapsula_Resource/");

        if (isset($incapsula)) {
            throw new CheckException(self::PROVIDER_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
        }

        /*
        $this->getRecordedRequests();

        if (!empty($this->responseDataAuth) || !empty($this->responseDataProfile)) {
            $this->http->SetBody($this->responseDataAuth ?? $this->responseDataProfile);
        }
        */

        $this->saveResponse();

        return true;
    }

    public function Login()
    {
        $response = $this->http->JsonLog();

        if (
            !empty($response->sessionToken)
            || !empty($response->profile)
            || !empty($response->personalInfo)
            || !empty($response->loginInfo)
        ) {
            return true;
        }

        if ($this->processQuestion()) {
            return false;
        }

        // The email address or password you entered does not match our records. Please try again.
        if ($this->http->FindPreg('/"errorCode":"E0000004".+"errorSummary":"Authentication failed"/')) {
            throw new CheckException("The email address or password you entered does not match our records. Please try again.", ACCOUNT_INVALID_PASSWORD);
        }
        // Your account has been disabled.
        if ($this->http->FindPreg("/\"errors\":\[\{\"code\":\"RSS01025E\"/ims")
            /*|| $this->http->FindPreg("/<errors><code>RSS01025E<\/code>/ims")*/) {
            throw new CheckException("Your account has been disabled.", ACCOUNT_PROVIDER_ERROR);
        }
        // Maintenance
        if ($this->http->FindPreg("/\"errors\":\[\{\"code\":\"RSS01100E\"/ims")
            /*|| $this->http->FindPreg("/<errors><code>RSS01100E<\/code>/ims")*/) {
            throw new CheckException("We're sorry for inconvenience, but our site content is currently unavailable due to maintenance", ACCOUNT_PROVIDER_ERROR);
        }
        // Password must be 8-12 characters long.
        if ($this->http->FindPreg("/\"errors\":\[\{\"code\":\"RSS01024E\"/ims")
            /*|| $this->http->FindPreg("/<errors><code>RSS01024E<\/code>/ims")*/) {
            throw new CheckException("Password must be 8-12 characters long.", ACCOUNT_INVALID_PASSWORD);
        }

        if ($this->http->FindPreg("/\"status\":\"LOCKED_OUT\"/ims")) {
            throw new CheckException("Because of multiple login attempts your account has been temporarily locked for security reasons.", ACCOUNT_LOCKOUT);
        }

        if ($message = $this->http->FindSingleNode('//div[contains(@class, "popup-light-error alert fade show")]')) {
            $this->logger->error("[Error]: {$message}");

            if (strstr($message, 'The password entered doesn\'t match our records.')) {
                throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
            }

            if ($message == 'Sorry, we\'re experiencing technical difficulties.') {
                throw new CheckException($message, ACCOUNT_PROVIDER_ERROR);
            }

            if (strstr($message, 'The account has been locked out due to multiple attempts of login with incorrect password')) {
                throw new CheckException($message, ACCOUNT_LOCKOUT);
            }

            $this->DebugInfo = $message;

            return false;
        }

        if (
            in_array($this->AccountFields['Login'], [
                'Davidylin@yahoo.com',
                'john@gilham.net',
            ])
        ) {
            throw new CheckException(self::PROVIDER_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        if (
            !strstr($this->http->currentUrl(), 'account-dashboard')
        ) {
            $this->http->GetURL("https://www.{$this->AccountFields['Login2']}.com/customer-account/account-dashboard");
        }
        $this->waitForElement(WebDriverBy::xpath('//div[@class="account-review"]/div[contains(@class, "name")]'), self::WAIT_TIMEOUT);
        $this->saveResponse();
        $this->getRecordedRequests();

        return $this->getCurlChecker()->Parse();
    }

    public function getCurlChecker()
    {
        $this->logger->notice(__METHOD__);

        if (!isset($this->curlChecker)) {
            $this->curlChecker = new TAccountCheckerSafeway();
            $this->curlChecker->http = new HttpBrowser("none", new CurlDriver());
            $this->curlChecker->http->setProxyParams($this->http->getProxyParams());

            $this->http->brotherBrowser($this->curlChecker->http);
            $this->curlChecker->AccountFields = $this->AccountFields;
            $this->curlChecker->itinerariesMaster = $this->itinerariesMaster;
            $this->curlChecker->HistoryStartDate = $this->HistoryStartDate;
            $this->curlChecker->historyStartDates = $this->historyStartDates;
            $this->curlChecker->http->LogHeaders = $this->http->LogHeaders;
            $this->curlChecker->ParseIts = $this->ParseIts;
            $this->curlChecker->ParsePastIts = $this->ParsePastIts;
            $this->curlChecker->WantHistory = $this->WantHistory;
            $this->curlChecker->WantFiles = $this->WantFiles;
            $this->curlChecker->strictHistoryStartDate = $this->strictHistoryStartDate;

            $this->curlChecker->globalLogger = $this->globalLogger;
            $this->curlChecker->logger = $this->logger;
            $this->curlChecker->onTimeLimitIncreased = $this->onTimeLimitIncreased;

            $this->logger->debug("forward recorded requests");
            $this->curlChecker->responseDataProfile = $this->responseDataProfile;
            $this->curlChecker->responseDataAuth = $this->responseDataAuth;
            $this->curlChecker->responseDataRewards = $this->responseDataRewards;

            $this->logger->debug("set cookies");

            foreach ($this->driver->manage()->getCookies() as $cookie) {
                if ($cookie['name'] == "SWY_SHARED_SESSION") {
                    $this->logger->debug('found SWY_SHARED_SESSION cookie');
                    $this->logger->debug($cookie['value']);
                    $sessionInfo = $this->http->JsonLog(urldecode($cookie['value']));

                    if (isset($sessionInfo->accessToken)) {
                        $this->State['token'] = $sessionInfo->accessToken;
                    }
                }

                $this->http->setCookie($cookie['name'], $cookie['value'], $cookie['domain'], $cookie['path'], $cookie['expiry'] ?? null);
            }
        }

        return $this->curlChecker;
    }

    protected function checkRegionSelection($region)
    {
        // refs #7743
        if ($this->AccountFields['Login2'] == 'dominicks') {
            throw new CheckException("Dominic's stores were closed in January, 2014", ACCOUNT_PROVIDER_ERROR);
        }

        if (empty($region) || !in_array($region, array_flip($this->regionOptions))) {
            $region = 'safeway';
        }

        return $region;
    }

    private function markConfigAsBadOrSuccess($success = true): void
    {
        return;

        if ($success) {
            $this->logger->info("marking config {$this->config} as successful");
            Cache::getInstance()->set('safeway_config_' . $this->config, 1, 60 * 60 * 3);
        } else {
            $this->logger->info("marking config {$this->config} as bad");
            Cache::getInstance()->set('safeway_config_' . $this->config, 0, 60 * 60 * 3);
        }
    }

    private function setConfig()
    {
        $configs = self::CONFIGS;

        if (ConfigValue(CONFIG_SITE_STATE) === SITE_STATE_DEBUG) {
            unset($configs['chrome-94-mac']);
        }

        $successfulConfigs = array_filter(array_keys($configs), function (string $key) {
            return Cache::getInstance()->get('safeway_config_' . $key) === 1;
        });

        $neutralConfigs = array_filter(array_keys($configs), function (string $key) {
            return Cache::getInstance()->get('safeway_config_' . $key) !== 0;
        });

        if (count($successfulConfigs) > 0) {
            $this->config = $successfulConfigs[array_rand($successfulConfigs)];
            $this->logger->info("found " . count($successfulConfigs) . " successful configs");
        } elseif (count($neutralConfigs) > 0) {
            $this->config = $neutralConfigs[array_rand($neutralConfigs)];
            $this->logger->info("found " . count($neutralConfigs) . " neutral configs");
        } else {
            $this->config = array_rand($configs);
        }

        /*
        $this->config = array_rand($configs);
        */

        $this->logger->info("selected config $this->config");
    }

    private function CheckErrors()
    {
        $this->logger->notice(__METHOD__);

        if ($this->http->FindPreg("/Service Unavailable/ims")) {
            throw new CheckException(self::PROVIDER_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
        }

        return false;
    }

    private function processQuestion()
    {
        $this->saveResponse();

        /*
        if (!$this->http->FindSingleNode('//form[@id="verifyOptionForm"]')) {
            return false;
        }
        */

        if ($this->http->FindSingleNode('//form[@id="verifyOptionForm"]')) {
            $sendCodeToEmail = $this->waitForElement(WebDriverBy::xpath('//form[@id="verifyOptionForm"]//span[@id="email"]'), self::WAIT_TIMEOUT);

            if (!isset($sendCodeToEmail)) {
                $this->sendNotification('refs #25366 safeway - send code to email button not found // IZ');

                return false;
            }

            if ($this->isBackgroundCheck() && !$this->getWaitForOtc()) {
                $this->Cancel();
            }

            $sendCodeToEmail->click();
            $submit = $this->waitForElement(WebDriverBy::xpath('//button[contains(@class, "auth-styles") and @data-tabindex="last" and not(@disabled)]'), self::SHORT_TIMEOUT);

            if (!isset($submit)) {
                return false;
            }
            $submit->click();
        }

        $this->waitForElement(WebDriverBy::xpath('//input[@formcontrolname="otpCode"]'), self::WAIT_TIMEOUT);
        $this->saveResponse();

        $question = $this->http->FindSingleNode('//div[contains(@class, "enter-otp") and contains(@class, "subtitle")]');
        $email = $this->http->FindSingleNode('//div[contains(@class, "enter-otp") and contains(@class, "subtitle")]/following-sibling::p');

        if (!isset($question, $email)) {
            return $this->CheckErrors();
        }

        $this->holdSession();
        $this->AskQuestion("{$question} {$email}", null, 'Question');

        return true;
    }

    private function getRecordedRequests()
    {
        try {
            $requests = $this->http->driver->browserCommunicator->getRecordedRequests();
        } catch (AwardWallet\Common\Selenium\BrowserCommunicatorException | Facebook\WebDriver\Exception\JavascriptErrorException $e) {
            $this->logger->error("Exception: " . $e->getMessage(), ['HtmlEncode' => true]);

            $requests = [];
        }

        foreach ($requests as $n => $xhr) {
            if (stripos($xhr->request->getUri(), '/accountDashBoard?request-id') !== false) {
                $this->logger->debug("xhr request {$n}: {$xhr->request->getVerb()} {$xhr->request->getUri()} " . json_encode($xhr->request->getHeaders()));
                /*
                $this->logger->debug("xhr response {$n}: {$xhr->response->getStatus()} {$xhr->response->getHeaders()}");
                */
                $this->responseDataProfile = json_encode($xhr->response->getBody());
            }
        }

        $this->logger->info('[Profile responseDataProfile]: ' . htmlspecialchars($this->responseDataProfile ?? null));
        $this->logger->info('[Current URL]: ' . $this->http->currentUrl());
    }

    private function getRecordedRequestsold()
    {
        foreach ($this->http->driver->browserCommunicator->getRecordedRequests() as $n => $xhr) {
            if (stripos($xhr->request->getUri(), '/api/v1/authn') !== false) {
                $this->logger->debug("xhr request {$n}: {$xhr->request->getVerb()} {$xhr->request->getUri()} " . json_encode($xhr->request->getHeaders()));
                /*
                $this->logger->debug("xhr response {$n}: {$xhr->response->getStatus()} {$xhr->response->getHeaders()}");
                */
                $this->responseDataAuth = json_encode($xhr->response->getBody());
            }

            if (stripos($xhr->request->getUri(), '/rewards') !== false) {
                $this->logger->debug("xhr request {$n}: {$xhr->request->getVerb()} {$xhr->request->getUri()} " . json_encode($xhr->request->getHeaders()));
                /*
                $this->logger->debug("xhr response {$n}: {$xhr->response->getStatus()} {$xhr->response->getHeaders()}");
                */
                $this->responseDataRewards = json_encode($xhr->response->getBody());
            }

            if (stripos($xhr->request->getUri(), '/profile') !== false) {
                $this->logger->debug("xhr request {$n}: {$xhr->request->getVerb()} {$xhr->request->getUri()} " . json_encode($xhr->request->getHeaders()));
                /*
                $this->logger->debug("xhr response {$n}: {$xhr->response->getStatus()} {$xhr->response->getHeaders()}");
                */
                $this->responseDataProfile = json_encode($xhr->response->getBody());
            }
        }

        $this->logger->info('[Auth responseData]: ' . htmlspecialchars($this->responseDataAuth ?? null));
        $this->logger->info('[Rewards responseData]: ' . htmlspecialchars($this->responseDataRewards ?? null));
        $this->logger->info('[Profile responseDataProfile]: ' . htmlspecialchars($this->responseDataProfile ?? null));
        $this->logger->info('[Current URL]: ' . $this->http->currentUrl());
    }
}
