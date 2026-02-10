<?php

use AwardWallet\Common\Parsing\Web\Proxy\Provider\MountRotatingRequest;
use AwardWallet\Common\Selenium\FingerprintFactory;
use AwardWallet\Common\Selenium\FingerprintRequest;
use AwardWallet\Engine\ProxyList;

class TAccountCheckerFuelrewards extends TAccountChecker
{
    use ProxyList;
    use SeleniumCheckerHelper;
    /**
     * @var CaptchaRecognizer
     */
    private $recognizer;

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
//        $this->http->SetProxy($this->proxyReCaptchaVultr());
        //$this->setProxyNetNut();
        //$this->requestProxyManager(new MountRotatingRequest());
    }

    public function IsLoggedIn()
    {
        $this->http->RetryCount = 0;
        $this->http->GetURL("https://www.fuelrewards.com/fuelrewards/loggedIn.html", [], 20);
        $this->http->RetryCount = 2;

        if ($this->loginSuccessful()) {
            return true;
        }

        return false;
    }

    public function LoadLoginForm()
    {
        $this->http->removeCookies();
        return $this->seleniumAuth();
        $this->http->RetryCount = 1;
        $this->http->GetURL('https://www.fuelrewards.com/fuelrewards/login-signup?utm_source=HP&utm_medium=um&utm_campaign=login');
        $this->http->RetryCount = 2;

        if ($this->http->FindPreg("/window\[\"bobcmn\"\]/")) {
            return $this->seleniumAuth();
        }

        if (!$this->http->ParseForm("loginform")) {
            if (
                $this->http->Error == 'Network error 0 - '
                || $this->http->Error == 'Network error 52 - Empty reply from server'
            ) {
                $this->markProxyAsInvalid();

                throw new CheckRetryNeededException(3, 0);
            }

            return $this->checkErrors();
        }

        if ($this->http->FindPreg("/submitFormWithRecaptchaV3/")) {
            return $this->seleniumAuth();
        }

        $this->http->SetInputValue('userId', $this->AccountFields['Login']);
        $this->http->SetInputValue('password', $this->AccountFields['Pass']);
        $this->http->SetInputValue('rememberMe', "true");
        $this->http->SetInputValue('_rememberMe', "on");

        $captcha = $this->parseReCaptcha();

        if ($captcha === false) {
            return false;
        }

        if ($this->http->FindPreg("/submitFormWithRecaptchaV3/")) {
            $this->http->SetInputValue('reCaptchaV3Token', $captcha);
        } else {
            $this->http->SetInputValue('g-recaptcha-response', $captcha);
        }

        if (!$this->http->PostForm()) {
            return $this->checkErrors();
        }

        return true;
    }

    public function checkErrors()
    {
        $this->logger->notice(__METHOD__);
        // Our site is currently down for scheduled maintenance.
        if ($message = $this->http->FindSingleNode("//p[contains(text(), 'Our site is currently down for scheduled maintenance.')]")) {
            throw new CheckException($message, ACCOUNT_PROVIDER_ERROR);
        }
        // Maintenance
        $this->http->RetryCount = 0;
        $this->http->GetURL("https://www.fuelrewards.com/");

        if ($message = $this->http->FindSingleNode("//h1[contains(text(), '502 Bad Gateway')]")) {
            throw new CheckException(self::PROVIDER_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
        }

        if ($message = $this->http->FindSingleNode("//p[contains(text(), 're currently in the middle of something exciting')]")) {
            throw new CheckException($message, ACCOUNT_PROVIDER_ERROR);
        }
        // Maintenance
        if ($message = $this->http->FindSingleNode("//p[contains(text(), 're currently conducting maintenance')]")) {
            throw new CheckException($message, ACCOUNT_PROVIDER_ERROR);
        }
        // Our site is currently down for maintenance.
        if ($message = $this->http->FindSingleNode("//p[contains(text(), 'Our site is currently down for maintenance')]")) {
            throw new CheckException($message, ACCOUNT_PROVIDER_ERROR);
        }
        // The server is temporarily unable to service your request due to maintenance downtime or capacity problems.
        if ($message = $this->http->FindSingleNode("//p[contains(text(), 'The server is temporarily unable to service')]")) {
            throw new CheckException($message, ACCOUNT_PROVIDER_ERROR);
        }

        return false;
    }

    public function Login()
    {
        if ($this->loginSuccessful()) {
            $this->markProxySuccessful();
            return true;
        }

        $this->CheckError($this->http->FindPreg('/class=\\\\"warn error\\\\"\>([^<]*)/ims'), ACCOUNT_INVALID_PASSWORD);
        $this->CheckError($this->http->FindSingleNode('//label[contains(text(), "Please enter a valid email address")]'));
        // User name or password not recognized
        $this->CheckError($this->http->FindPreg("/\(\'\#serverErrors\'\)\.text\(\"(User name or password not recognized)/ims"), ACCOUNT_INVALID_PASSWORD);
        // Your login has been locked due to too many failed login attempts
        $this->CheckError($this->http->FindPreg("/\(\'\#serverErrors\'\)\.text\(\"(Your login has been locked due to too many failed login attempts[^\"])/ims"), ACCOUNT_LOCKOUT);
        // Data retrieval error, unable to process request
        $this->CheckError($this->http->FindPreg("/\(\'\#serverErrors\'\)\.text\(\"(Data retrieval error, unable to process request[^\"])/ims"), ACCOUNT_PROVIDER_ERROR);
        $this->CheckError($this->http->FindPreg('/\$\(\'\#serverErrors\'\).text\("((?:General error, unable to process request|Data retrieval error, unable to process request))"\)/ims'), ACCOUNT_PROVIDER_ERROR);
        //# Password update
        if ($message = $this->http->FindSingleNode("//h1[contains(text(), 'Password Update')]")) {
            throw new CheckException("FreeBirds (Fanatic Rewards) website is asking you to update your password, until you do so we would not be able to retrieve your account information.", ACCOUNT_PROVIDER_ERROR);
        }/*checked*/

        if ($serverErrors = $this->http->FindPreg('/\$\(\'\#serverErrors\'\).text\("([^\"]+)"\)/ims')) {
            $this->logger->error("[Error]: '{$serverErrors}'");

            if (in_array($serverErrors, [
                "Please verify that you are not a robot.",
                "CAPTCHA verification failed. Please try again later."
            ])) {
                throw new CheckRetryNeededException(3, 0, self::CAPTCHA_ERROR_MSG);
            }

            $this->DebugInfo = $serverErrors;

            return false;
        }

        if ($this->http->FindSingleNode('//body[contains(text(), "The requested URL was rejected.")] ')) {
            $this->markProxyAsInvalid();

            throw new CheckRetryNeededException(3, 0);
        }

        return $this->checkErrors();
    }

    public function GetRedirectParams($targetURL = null)
    {
        $arg = parent::GetRedirectParams($targetURL);
        $arg['SuccessURL'] = 'https://www.fuelrewards.com/fuelrewards/dashboard.html';
        $arg['CookieURL'] = 'https://www.fuelrewards.com/fuelrewards.html';

        return $arg;
    }

    public function Parse()
    {
        /*
        // debug
        if (!$this->http->FindSingleNode("//input[@id = 'totalRewardBal']/@value")) {
            $this->http->GetURL("https://www.fuelrewards.com/fuelrewards/loggedIn.html");
        }
        */

        // set Name
        $this->SetProperty('Name', beautifulName($this->http->FindSingleNode('(//div[@class="user-name"])[1]', null, true, "/([^|]+)/")));
        // set Account Number
        $this->SetProperty('AccountNumber', $this->http->FindSingleNode('(//div[@class="user-account"])[1]', null, true, '/Account#\s+(.*)/ims'));
        // set Member Since
        $this->SetProperty('MemberSince', $this->http->FindSingleNode('(//div[@class="user-date"])[1]', null, true, '/Member since\s+(.*)/ims'));
        // set Total Amount Saved on Fuel
        $this->SetProperty('TotalAmountSaved', $this->http->FindSingleNode('//div[contains(@class, "balance_ts")]/h2', null, false));
        // Status
        $trier = $this->http->FindPreg("/tier\s*=\s*(?:\'|\")([^\'\"]+)/");
        $this->logger->debug("[Status]: {$trier}");
        // Next Status
        $this->SetProperty('NextStatus', $this->http->FindSingleNode('//p[contains(@class, "togo") and not(contains(@class, "no-eval"))]//a[contains(@href, "fuelrewards/status")]', null, true, '/(.*)\sstatus/ims'));

        if (!in_array($trier, [
            'SEGREACTIVATIONDEC19',
            'NCALDEBRAND919',
        ])) {
            $this->SetProperty("Status", beautifulName($trier));
        }
        // set Balance
        $this->SetBalance($this->http->FindSingleNode("//input[@id = 'totalRewardBal']/@value"));
        // Expiration Date
        $exp = $this->http->FindSingleNode("//span[contains(text(), 'Rewards Expiring')]/following-sibling::span[1]", null, true, "/on\s*([\d\/]+)/ims");

        if ($exp = strtotime($exp)) {
            $this->SetExpirationDate($exp);
        }
        // Rewards to expire
        $this->SetProperty('RewardsToExpire', $this->http->FindSingleNode("//span[contains(text(), 'Rewards Expiring')]/following-sibling::span[2]"));

        if ($this->ErrorCode == ACCOUNT_ENGINE_ERROR) {
            // Account ID: 4363450, SetBalance(0);
            if (
                !empty($this->Properties['Name'])
                && !empty($this->Properties['AccountNumber'])
                && !empty($this->Properties['MemberSince'])
                && !isset($this->Properties['TotalAmountSaved'])
                && empty($this->Properties['AccountExpirationDate'])
                && empty($this->Properties['RewardsToExpire'])
                && $this->http->FindPreg('/<input type="hidden" name="totalRewardBal" id="totalRewardBal" value=""\/>/')
            ) {
                $this->SetBalance(0);
            }
        }
    }

    protected function parseReCaptcha()
    {
        $this->logger->notice(__METHOD__);
        $key = $this->http->FindSingleNode("//form[@id = 'loginform']//div[@class = 'g-recaptcha']/@data-sitekey");
        $this->logger->debug("data-sitekey: {$key}");
        $extendedParameters = [];

        if (!$key && $this->http->FindPreg("/submitFormWithRecaptchaV3/")) {
            $key = $this->http->FindPreg('/enterprise.execute\(\'([^\']+)\', \{action: \'FR_LOGIN\'/');
            $extendedParameters = [
                "invisible" => "1",
                "version"   => "enterprise",
//                "version"   => "v3",
                "action"    => "FR_LOGIN",
                "min_score" => 0.7,
            ];

            /*
            if (!$key) {
                return false;
            }

            $this->recognizer = $this->getCaptchaRecognizer(self::CAPTCHA_RECOGNIZER_ANTIGATE_API_V2);
            $this->recognizer->RecognizeTimeout = 120;
            $parameters = [
                "type"         => "RecaptchaV3TaskProxyless",
                "websiteURL"   => $this->http->currentUrl(),
                "websiteKey"   => $key,
                "minScore"     => 0.9,
                "pageAction"   => "FR_LOGIN",
                "isEnterprise" => true,
            ];

            return $this->recognizeAntiCaptcha($this->recognizer, $parameters);
            */
        }

        if (!$key) {
            return false;
        }

        $this->recognizer = $this->getCaptchaRecognizer();
        $this->recognizer->RecognizeTimeout = 120;
        $parameters = [
            "pageurl" => $this->http->currentUrl(),
//            "proxy"   => $this->http->GetProxy(),
        ] + $extendedParameters;

        return $this->recognizeByRuCaptcha($this->recognizer, $key, $parameters);
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);

        if ($this->http->FindNodes('//a[@class="toplink" and contains(text(), "logout")]')) {
            return true;
        }

        return false;
    }

    private function seleniumAuth(): bool
    {
        $this->logger->notice(__METHOD__);
        $selenium = clone $this;
        $this->http->brotherBrowser($selenium->http);
        $retry = false;
        $logout = null;

        try {
            $this->logger->notice("Running Selenium...");
            $selenium->UseSelenium();
            $selenium->http->saveScreenshots = true;
            $selenium->setProxyGoProxies(null, 'ca');

            if ($this->attempt == 1) {
                $selenium->useFirefoxPlaywright();
            } else {
                $selenium->useChromePuppeteer();
            }
            $selenium->seleniumOptions->addHideSeleniumExtension = true;
            $selenium->seleniumOptions->userAgent = null;

            $selenium->seleniumRequest->setOs(SeleniumFinderRequest::OS_MAC);



            $selenium->http->start();
            $selenium->Start();
            $selenium->http->GetURL('https://www.fuelrewards.com/fuelrewards/login-signup?utm_source=HP&utm_medium=um&utm_campaign=login');
            sleep(5);
            $loginInput = $selenium->waitForElement(WebDriverBy::xpath('//input[@id = "username"]'), 7);
            $passInput = $selenium->waitForElement(WebDriverBy::xpath('//input[@id = "password"]'), 0);

            if (!$loginInput && !$passInput) {
                $btn = $selenium->waitForElement(WebDriverBy::xpath('//button[@id = "jar"]'), 0);
                $captchaInput = $selenium->waitForElement(WebDriverBy::xpath('//input[@name = "answer"]'), 0);

                if ($captchaInput && $btn) {
                    $this->savePageToLogs($selenium);
                    $captcha = $this->parseCaptcha();

                    if (!$captcha) {
                        return false;
                    }
                    $captchaInput->sendKeys($captcha);
                    $this->savePageToLogs($selenium);
                    $btn->click();

                    $loginInput = $selenium->waitForElement(WebDriverBy::xpath('//input[@id = "username"]'), 7);
                }
            }

            $passInput = $selenium->waitForElement(WebDriverBy::xpath('//input[@id = "password"]'), 0);
            $btn = $selenium->waitForElement(WebDriverBy::xpath('//button[@name = "action"]'), 0);
            $this->savePageToLogs($selenium);

            if (!$loginInput || !$passInput || !$btn) {
                return $this->checkErrors();
            }

            $this->logger->debug("set credentials");
//            $loginInput->clear();
//            $loginInput->sendKeys($this->AccountFields['Login']);
//            $passInput->clear();
//            $passInput->sendKeys($this->AccountFields['Pass']);

            $mover = new MouseMover($selenium->driver);
            $mover->logger = $this->logger;
            $mover->sendKeys($loginInput, $this->AccountFields['Login'], 5);
            $mover->sendKeys($passInput, $this->AccountFields['Pass'], 5);

            try {
                $selenium->driver->executeScript('document.querySelector(\'input[name="rememberMe"]\').checked = true;');
            } catch (UnexpectedJavascriptException $e) {
                $this->logger->error("Exception: ".$e->getMessage(), ['HtmlEncode' => true]);
            }

            $this->logger->debug("click 'Sign In'");
            $this->savePageToLogs($selenium);
            $btn->click();

            $selenium->waitForElement(WebDriverBy::xpath('
                //a[@class="toplink" and contains(text(), "logout")]
                | //p[@id = "serverErrors" and not(contains(@style, "hidden"))] 
                | //body[contains(text(), "The requested URL was rejected.")]  
                | //h5[contains(text(), "Current Reward Balance") and @style]
                | //label[contains(text(), "Please enter a valid email address")]
            '), 10);
            $this->savePageToLogs($selenium);

            $btn = $selenium->waitForElement(WebDriverBy::xpath('//button[@id = "jar"]'), 0);
            $captchaInput = $selenium->waitForElement(WebDriverBy::xpath('//input[@name = "answer"]'), 0);

            if ($captchaInput && $btn) {
                $this->savePageToLogs($selenium);
                $captcha = $this->parseCaptcha();

                if (!$captcha) {
                    return false;
                }
                $captchaInput->sendKeys($captcha);
                $this->savePageToLogs($selenium);
                $btn->click();

                $result = $selenium->waitForElement(WebDriverBy::xpath('
                    //a[@class="toplink" and contains(text(), "logout")]
                    | //p[@id = "serverErrors" and not(contains(@style, "hidden"))] 
                    | //body[contains(text(), "The requested URL was rejected.")]  
                    | //h5[contains(text(), "Current Reward Balance") and @style]
                    | //label[contains(text(), "Please enter a valid email address")]
                '), 10);
                $this->savePageToLogs($selenium);

                if (stristr($result->getText(), "CAPTCHA verification failed. Please try again later.")) {
                    $loginInput = $selenium->waitForElement(WebDriverBy::xpath('//input[@id = "userId"]'), 7);
                    $passInput = $selenium->waitForElement(WebDriverBy::xpath('//input[@id = "password"]'), 0);
                    $btn = $selenium->waitForElement(WebDriverBy::xpath('//a[@id = "loginButton"]'), 0);
                    $this->savePageToLogs($selenium);

                    if (!$loginInput || !$passInput || !$btn) {
                        return $this->checkErrors();
                    }

                    $this->logger->debug("set credentials");
                    $mover = new MouseMover($selenium->driver);
                    $mover->logger = $this->logger;
                    $mover->sendKeys($loginInput, $this->AccountFields['Login'], 5);
                    $mover->sendKeys($passInput, $this->AccountFields['Pass'], 5);
                    $this->savePageToLogs($selenium);
                    $btn->click();

                    $selenium->waitForElement(WebDriverBy::xpath('
                        //a[@class="toplink" and contains(text(), "logout")]
                        | //p[@id = "serverErrors" and not(contains(@style, "hidden"))] 
                        | //body[contains(text(), "The requested URL was rejected.")]  
                        | //h5[contains(text(), "Current Reward Balance") and @style]
                        | //label[contains(text(), "Please enter a valid email address")]
                    '), 10);
                    $this->savePageToLogs($selenium);
                }

            }

            $cookies = $selenium->driver->manage()->getCookies();

            foreach ($cookies as $cookie) {
                $this->http->setCookie($cookie['name'], $cookie['value'], $cookie['domain'], $cookie['path'], $cookie['expiry'] ?? null);
            }

            $this->logger->debug("[Current Selenium URL]: {$selenium->http->currentUrl()}");
        } catch (
            NoSuchDriverException
            | UnrecognizedExceptionException
            | UnexpectedJavascriptException
            $e
        ) {
            $this->logger->error("Exception: " . $e->getMessage(), ['HtmlEncode' => true]);
            $retry = true;
        } finally {
            $selenium->http->cleanup();

            // retries
            if ($retry && ConfigValue(CONFIG_SITE_STATE) != SITE_STATE_DEBUG) {
                throw new CheckRetryNeededException(3, 0);
            }
        }

        return true;
    }

    protected function parseCaptcha()
    {
        $this->logger->notice(__METHOD__);
        $imageData = $this->http->FindSingleNode('//div[contains(@class, "captcha-image")]/img/@src', null, true, "/png;base64\,\s*([^<]+)/ims");
        $this->logger->debug("png;base64: {$imageData}");

        if (!empty($imageData)) {
            $this->logger->debug("decode image data and save image in file");
            // decode image data and save image in file
            $imageData = base64_decode($imageData);
            $image = imagecreatefromstring($imageData);
            $file = "/tmp/captcha-" . getmypid() . "-" . microtime(true) . ".png";
            imagejpeg($image, $file);
        }

        if (!isset($file)) {
            return false;
        }
        $this->logger->debug("file: " . $file);
        $this->recognizer = $this->getCaptchaRecognizer();
        $this->recognizer->RecognizeTimeout = 100;
        $captcha = $this->recognizeCaptcha($this->recognizer, $file, [
            'regsense'         => 1,
//            'language'         => 2,
//            'textinstructions' => 'Only lower register here / Здесь только маленькие буквы',
        ]);
        unlink($file);

        return $captcha;
    }
}
