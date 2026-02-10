<?php

use AwardWallet\Common\Parsing\Web\Proxy\Provider\MountRotatingRequest;
use AwardWallet\Engine\ProxyList;
use AwardWallet\Common\Selenium\BrowserCommunicatorException;

class TAccountCheckerShangrilaSelenium extends TAccountChecker
{
    use SeleniumCheckerHelper;
    use ProxyList;
    /**
     * @var CaptchaRecognizer
     */
    private $recognizer;

    private TAccountCheckerShangrila $curlChecker;
    private array $responseData = [];

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->useSelenium();
        $this->UseSelenium();

        /*
        $this->http->SetProxy($this->proxyReCaptchaVultr());
        */
        if ($this->attempt == 0) {
            $this->setProxyBrightData(null, 'static', 'us');            
        } else if ($this->attempt == 1){
            $this->setProxyGoProxies();
        } else {
            $this->setProxyMount();
        }

        $this->useGoogleChrome();
        $this->seleniumOptions->addHideSeleniumExtension = false;
        $this->seleniumOptions->userAgent = null;
        /*
        $this->useFirefox(SeleniumFinderRequest::FIREFOX_84);
//        $this->seleniumOptions->fingerprintParams = FingerprintParams::vanillaFirefox();
        $this->seleniumOptions->addHideSeleniumExtension = false;
        $this->setKeepProfile(true);
        */

        $this->http->saveScreenshots = true;
        $this->seleniumOptions->recordRequests = true;
        $this->usePacFile(false);
    }

    public function LoadLoginForm()
    {
        $this->http->removeCookies();
        $this->http->GetURL('https://www.shangri-la.com/en/corporate/golden-circle/gcsignin/');
        $this->waitForElement(WebDriverBy::xpath("//div[contains(@class,'login-form')]"), 7);
        $this->acceptCookies();

        if (filter_var($this->AccountFields['Login'], FILTER_VALIDATE_EMAIL) !== false) {
            $switchPass = $this->waitForElement(WebDriverBy::xpath('//div[contains(text(), "Change to use password")]'), 5);
            $switchPass->click();
            $login = $this->waitForElement(WebDriverBy::xpath('//input[@name = "email"]'), 10);
            $password = $this->waitForElement(WebDriverBy::xpath('//input[@name = "email-password"]'), 0);
        } else {
            $switchGc = $this->waitForElement(WebDriverBy::xpath('//span[contains(@class, "shangrila-react-login-box-switch-icon-gc")]'), 0);
            $switchGc->click();
            $login = $this->waitForElement(WebDriverBy::xpath('//input[@name = "gc"]'), 10);
            $password = $this->waitForElement(WebDriverBy::xpath('//input[@name = "gc-password"]'), 0);
        }

        $this->saveResponse();

        if (!$login || !$password) {
            $this->logger->error("Failed to find form fields");

            return $this->checkErrors();
        }

        $login->sendKeys($this->AccountFields['Login']);
        $password->sendKeys($this->AccountFields['Pass']);
        $submit = $this->waitForElement(WebDriverBy::xpath('//button[contains(text(), "Sign In")]'), 0);

        //$this->saveResponse();
        $submit->click();

        return true;
    }

    private function slideCaptcha()
    {
        $this->logger->notice(__METHOD__);
        $iframe = $this->waitForElement(WebDriverBy::xpath("//div[@class=\"geetest_panel_next\" and @style=\"display: block;\"]"), 0);

        if (!$iframe) {
            $this->saveResponse();
            $this->logger->error('Failed to find captcha iframe');

            return false;
        }

        $iframeCoords = ['x' => $iframe->getLocation()->getX(), 'y' => $iframe->getLocation()->getY()];
        $this->logger->info('=iframeCoords:');
        $this->logger->info(var_export($iframeCoords, true), ['pre' => true]);

        return $this->solveSlideCaptcha($iframeCoords);
    }

    private function solveSlideCaptcha(array $iframeCoords)
    {
        $this->logger->notice(__METHOD__);
        $captchaElem = $this->waitForElement(WebDriverBy::xpath('//*[contains(@class, "geetest_canvas_slice")]'), 20);
        // slider btn
        $slider = $this->waitForElement(WebDriverBy::xpath('//div[contains(@class, "geetest_slider_button")]'), 0);
        $this->saveResponse();

        if (!$captchaElem || !$slider) {
            $this->logger->error("something went wrong");

            return false;
        }

        $captchaCoords = ['x' => $captchaElem->getLocation()->getX(), 'y' => $captchaElem->getLocation()->getY()];
        $this->logger->info('=captchaCoords:');
        $this->logger->info(var_export($captchaCoords, true), ['pre' => true]);

        $params = [
            'coordinatescaptcha' => '1',
            'textinstructions'   => 'Click on the center of the dark puzzle / Кликните на центр темного паззла',
        ];
        $targetRel = $this->solveCoordinatesCaptcha($captchaElem, $params, $iframeCoords);

        if (!$targetRel) {
            $this->logger->error("something went wrong");

            return false;
        }

        $this->logger->info('=targetCoords:');
        $this->logger->info(var_export($targetRel, true), ['pre' => true]);
        $targetRel = end($targetRel);

        foreach ([1, +5, -5] as $i => $dx) { // offsets
            $try = $i + 1;
            $this->logger->info("inner try = {$try}, slide dx = {$dx}");
            $tryTargetAbs = ['x' => $targetRel['x'] + $dx, 'y' => $targetRel['y']];
            $this->logger->info('absolute slide try');
            $success = $this->slideTry($tryTargetAbs);

            if ($success) {
                return true;
            }
        }

        return false;
    }

    private function slideTry(array $targetAbs)
    {
        $this->logger->notice(__METHOD__);
        // slider btn
        $slider = $this->waitForElement(WebDriverBy::xpath('//div[contains(@class, "geetest_slider_button")]'), 5);

        if (!$slider) {
            return false;
        }

        $mouse = $this->driver->getMouse()->mouseDown($slider->getCoordinates());

        /*
        $this->logger->debug('mouseDown');
        $mouse->mouseDown();
        $this->logger->debug('mouse shape');
        $mouse->mouseMove(null, rand(3, 10), rand(-5, 5));
        $this->saveResponse();
        $this->logger->debug('mouseUp');
        $mouse->mouseUp();

        $delay = 3;
        $this->logger->debug("wait {$delay} sec");
        sleep($delay);
        $this->saveResponse();
        */

        $distance = $targetAbs['x'] - 30;
        $this->logger->debug("distance: $distance");
        $this->logger->debug('mouseDown');
        $mouse->mouseDown();
        /*
        $this->logger->debug('mouseMove');
        $mouse->mouseMove(null, intval($distance));
        */
        $this->logger->debug('mouse fake move');
        $mouse->mouseMove(null, $targetAbs['x']);
        $this->saveResponse();
        $this->logger->debug('mouse move');
        $mouse->mouseMove(null, -30);

        $this->saveResponse();
        $this->logger->debug('mouseUp');
        $mouse->mouseUp();

        $this->logger->debug('waiting result...');
        $this->waitForElement(WebDriverBy::xpath('//div[contains(@class, "geetest_result_content") and contains(text(), "Position the piece in its slot.")]'), 5);
        $slider = $this->waitForElement(WebDriverBy::xpath('//div[contains(@class, "geetest_slider_button")]'), 0);
        $this->saveResponse();

        return $slider ? false : true;
    }

    private function solveCoordinatesCaptcha($elem, array $params, array $iframeCoords)
    {
        $this->logger->notice(__METHOD__);
        $this->recognizer = $this->getCaptchaRecognizer();
        $this->recognizer->RecognizeTimeout = 120;

        if (!$elem) {
            $this->logger->error('Cannot take screenshot of an empty element');

            return false;
        }
        $pathToScreenshot = $this->takeScreenshotOfElement($elem);
        $this->logger->debug('Path to captcha screenshot ' . $pathToScreenshot);

        try {
            $text = $this->recognizer->recognizeFile($pathToScreenshot, $params);
        } catch (CaptchaException $e) {
            $this->logger->error("CaptchaException: {$e->getMessage()}");

            if ($e->getMessage() === 'server returned error: ERROR_CAPTCHA_UNSOLVABLE') {
                // almost always solvable
                $this->recognizer->reportIncorrectlySolvedCAPTCHA();

                throw new CheckRetryNeededException(3, 0, self::CAPTCHA_ERROR_MSG);
            }

            if (
                strstr($e->getMessage(), 'CURL returned error: Operation timed out after ')
                || strstr($e->getMessage(), 'timelimit (120) hit')
                || strstr($e->getMessage(), 'CURL returned error: Failed to connect to rucaptcha.com port 80')
            ) {
                throw new CheckRetryNeededException(3, 0, self::CAPTCHA_ERROR_MSG);
            } else {
                throw $e;
            }
        } finally {
            unlink($pathToScreenshot);
        }

        return $this->parseCoordinates($text);
    }

    public function Login()
    {
        $this->waitForElement(WebDriverBy::xpath('//myxpath | //div[contains(text(), "Points Balance")] | //div[@class="geetest_panel_next" and @style="display: block;"] | //span[@class="geetest_mark"]'), 25);
        $this->saveResponse();

        if ($this->slideCaptcha()) {
            $this->waitForElement(WebDriverBy::xpath('//myxpath | //div[contains(text(), "Points Balance")]'), 25);
            $this->saveResponse();
        }

        try {
            $seleniumDriver = $this->http->driver;
            $requests = $seleniumDriver->browserCommunicator->getRecordedRequests();
    
            foreach ($requests as $n => $xhr) {
                $this->logger->debug("xhr request {$n}: {$xhr->request->getVerb()} {$xhr->request->getUri()} " . json_encode($xhr->request->getHeaders()));
                /*
                $this->logger->debug("xhr response {$n}: {$xhr->response->getStatus()} {$xhr->response->getHeaders()}");
                */
                if (
                    strstr($xhr->request->getUri(), '/v1/website?__c=')
                    && strstr(json_encode($xhr->response->getBody()), 'gcMemberId')
                ) {
                    $this->logger->debug("xhr response {$n} body: " . json_encode($xhr->response->getBody()));
                    $this->responseData['fetchUserInfo'] = json_encode($xhr->response->getBody());
    
                    /*
                    break;
                    */
                }
            }// foreach ($requests as $n => $xhr)
    
            /*if (!empty($responseData)) {
                $this->http->SetBody($responseData);
            }*/    
        } catch(BrowserCommunicatorException $e) {

            throw new CheckRetryNeededException(3, 0, self::PROVIDER_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
        }

        $response = $this->http->JsonLog($this->responseData['fetchUserInfo'] ?? null);

        if ($this->loginSuccessful($response)) {
            $this->captchaReporting($this->recognizer);

            return true;
        }

        if ($message = $this->http->FindSingleNode('//div[contains(@class, "shangrila-react-login-box-common-err") and normalize-space(.) != ""]')) {
            $this->logger->error("[Error]: {$message}");


            if (
                strstr($message, "Current login method is temporarily unavailable, please try using another login method.")
                || strstr($message, "It looks like you already have a Shangri-La Circle account")
            ) {
                $this->captchaReporting($this->recognizer);
                throw new CheckException($message, ACCOUNT_PROVIDER_ERROR);
            }

            if (
                $message == 'Membership Number/Password is not valid. Please try again.'
                || strstr($message, 'Email/Password is not valid or email is not verified. Please try again.')
                || strstr($message, 'Incorrect password')
            ) {
                $this->captchaReporting($this->recognizer);
                throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
            }

            $this->DebugInfo = $message;

            return false;
        }

        if ($message = $this->http->FindSingleNode('//div[(contains(@class, "form-err-wrapper") or contains(@class, "shangrila-ui-library-modal-info-wrapper")) and normalize-space(.) != ""] | //div[contains(@class, "confirm-modal") and not(@style="display:none;")]//div[contains(@class, "sl-modal-content")]/div[@class = "text"]')) {
            $this->logger->error("[Error]: {$message}");

            if (strstr($message, "Please enter a valid Email Address.")) {
                $this->captchaReporting($this->recognizer);
                throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
            }

            if (
                strstr($message, "A new device sign-in attempt was detected. To ensure your account security, please sign in with a verification code.")
                && $this->processQuestion()
            ) {
                $this->captchaReporting($this->recognizer);
                /*
                throw new CheckException("A new device sign-in attempt was detected. To ensure your account security, please sign in with a verification code.", ACCOUNT_PROVIDER_ERROR);// TODO: gag
                */
                return false;
            }

            if ($message == 'Unfortunately, we are experiencing some technical difficulty. Please try again later.') {
                $this->captchaReporting($this->recognizer);
                $this->ErrorReason = self::ERROR_REASON_BLOCK;

                $this->DebugInfo = "block by provider";
                throw new CheckRetryNeededException(3, 0, self::PROVIDER_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
            }

            $this->DebugInfo = $message;

            return false;
        }

        if ($this->waitForElement(WebDriverBy::xpath('//div[contains(@class, "geetest_result_content") and contains(text(), "Position the piece in its slot.")] | //div[@class="geetest_panel_next" and @style="display: block;"]'), 0)) {
            $this->captchaReporting($this->recognizer, false);

            throw new CheckRetryNeededException(3, 0, self::CAPTCHA_ERROR_MSG);
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        $this->getCurlChecker();
        $this->curlChecker->Parse($this->responseData);
        $this->SetBalance($this->curlChecker->Balance);
        $this->Properties = $this->curlChecker->Properties;

        if ($this->ErrorCode != ACCOUNT_CHECKED) {
            $this->ErrorCode = $this->curlChecker->ErrorCode;
            $this->ErrorMessage = $this->curlChecker->ErrorMessage;
            $this->DebugInfo = $this->curlChecker->DebugInfo;
        }
    }

    public function ParseItineraries()
    {
        $this->getCurlChecker();
       /* $this->http->GetURL('https://www.shangri-la.com/en/corporate/shangrilacircle/online-services/reservations-list/?orderType=UPCOMING&page=1&orderConsumeType=HOTEL');
        $this->getCurlChecker();
        $this->curlChecker->ParseItineraries();*/
        $result = [];
        // Upcoming reservations
        $page = 1;
        $noUpcoming = false;

        do {
            $this->http->GetURL("https://www.shangri-la.com/en/corporate/shangrilacircle/online-services/reservations-list/?orderType=UPCOMING&orderConsumeType=HOTEL&page={$page}");
            $this->savePageToLogs($this);
            $stop = $this->http->FindPreg('/"hotelOrderList":\[\]/');
            $this->logger->debug('Stop: ' . $stop . ', Page: ' . $page);

            if (!$this->http->FindPreg('/var __pageData\s*=\s*.+?"hotelOrderList":\[\],"totalCount":0,/s')) {
                //$this->sendNotification('check reservation');
                $items = $this->http->JsonLog($this->http->FindPreg('/var __pageData\s*=\s*(\{.+?\});/s'));

                if (isset($items->orderDatas->hotelOrderList)) {
                    foreach ($items->orderDatas->hotelOrderList as $item) {
                        $url = $item->detailUrl;
                        $this->http->NormalizeURL($url);
                        $this->http->GetURL($url);
                        $this->savePageToLogs($this);
                        $this->curlChecker->http->SetBody($this->driver->executeScript('return document.documentElement.innerHTML'));
                        if ($res = $this->curlChecker->ParseItinerary()) {
                            $result[] = $res;
                        }
                    }
                }
            } elseif ($page === 1) {
                $noUpcoming = true;
            }
            $page++;
        } while ($page < 5 && !$stop);

        // Past reservations
        if ($this->ParsePastIts) {
            $this->http->GetURL('https://www.shangri-la.com/en/corporate/golden-circle/online-services/reservations-list/?orderType=PAST&orderConsumeType=HOTEL');
            $this->savePageToLogs($this);
            $noPast = false;

            if (!$this->http->FindPreg('/var __pageData\s*=\s*.+?"hotelOrderList":\[\],"totalCount":0,/s')) {
                $items = $this->http->JsonLog($this->http->FindPreg('/var __pageData\s*=\s*(\{.+?\});/s'));

                if (isset($items->orderDatas->hotelOrderList)) {
                    foreach ($items->orderDatas->hotelOrderList as $item) {
                        $url = $item->detailUrl;
                        $this->http->NormalizeURL($url);
                        $this->http->GetURL($url);
                        $this->savePageToLogs($this);
                        $this->curlChecker->http->SetBody($this->driver->executeScript('return document.documentElement.innerHTML'));
                        if ($res = $this->curlChecker->ParseItinerary()) {
                            $result[] = $res;
                        }
                    }
                }
            } else {
                $noPast = true;
            }

            if ($noPast && $noUpcoming) {
                return $this->noItinerariesArr();
            }
        } elseif ($noUpcoming) {
            return $this->noItinerariesArr();
        }

        return [];
    }

    public function checkErrors()
    {
        $this->logger->notice(__METHOD__);

        return false;
    }

    public function loginSuccessful($response)
    {
        $this->logger->notice(__METHOD__);
        $this->getCurlChecker();

        return $this->curlChecker->loginSuccessful($response);
    }

    protected function getCurlChecker()
    {
        $this->logger->notice(__METHOD__);

        if (!isset($this->curlChecker)) {
            $this->curlChecker = new TAccountCheckerShangrila();
            $this->curlChecker->http = new HttpBrowser("none", new CurlDriver());
            if (isset($this->http->Response['body'])) {
                $this->curlChecker->http->SetBody($this->http->Response['body']);
            }
            $this->curlChecker->http->setProxyParams($this->http->getProxyParams());

            $this->http->brotherBrowser($this->curlChecker->http);
            $this->curlChecker->State = $this->State;
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
        }

        $this->logger->debug("set headers");
        $defaultHeaders = $this->http->getDefaultHeaders();
        foreach ($defaultHeaders as $header => $value) {
            $this->curlChecker->http->setDefaultHeader($header, $value);
        }
        $cookies = $this->driver->manage()->getCookies();
        $this->logger->debug("set cookies");

        foreach ($cookies as $cookie) {
            $this->curlChecker->http->setCookie($cookie['name'], $cookie['value'], $cookie['domain'], $cookie['path'], $cookie['expiry'] ?? null);
        }

        return $this->curlChecker;
    }

    private function acceptCookies()
    {
        $this->logger->notice(__METHOD__);
        $acceptCookies = $this->waitForElement(WebDriverBy::xpath("//*[@id = 'js-cookie-manage-accept-all']"), 0);

        if ($acceptCookies) {
            $acceptCookies->click();
            sleep(3);
            $this->saveResponse();
        }
    }

    private function processQuestion()
    {
        $this->logger->notice(__METHOD__);
        $this->saveResponse();
        if (
            !$this->http->FindSingleNode('//div[@class="shangrila-ui-library-modal-info-wrapper" and contains(text(), "A new device sign-in attempt was detected. To ensure your account security, please sign in with a verification code.")]//button')
        ) {
            return false;
        }

        if (!$okButton = $this->waitForElement(WebDriverBy::xpath('//div[@class="shangrila-ui-library-modal-info-wrapper" and contains(text(), "A new device sign-in attempt was detected. To ensure your account security, please sign in with a verification code.")]//button'), 10)) {
            return false;
        }
        $okButton->click();
        if (!$login = $this->waitForElement(WebDriverBy::xpath('//input[@name="email"]'), 10)) {
            return false;
        }
        $login->sendKeys($this->AccountFields['Login']);
        if (!$sendCodeButton = $this->waitForElement(WebDriverBy::xpath('//div[contains(@class, "shangrila-react-login-count-down") and contains(text(), "Send")]'), 10)) {
            return false;
        }

        if ($this->isBackgroundCheck() && !$this->getWaitForOtc()) {
            $this->Cancel();
        }
        $this->saveResponse();
        $sendCodeButton->click();
        sleep(5);
        $this->saveResponse();
        $this->holdSession();
        $this->AskQuestion('A verification code has been sent to your email address: ' . $this->AccountFields['Login'], null, 'Question');
        return true;
    }

    public function ProcessStep($step)
    {
        $this->logger->notice(__METHOD__);
        if (!$code = $this->waitForElement(WebDriverBy::xpath('//input[@name="email-code"]'), 10)) {
            return $this->checkErrors();
        }

        $answer = $this->Answers[$this->Question];
        unset($this->Answers[$this->Question]);

        $code->clear();
        $code->sendKeys($answer);

        $this->logger->debug("ready to click");
        $this->saveResponse();

        $verifyCodeButton = $this->waitForElement(WebDriverBy::xpath('//button[contains(@class,"shangrila-react-login-box-btn-submit")]'), 0);
        $this->saveResponse();

        if (!$verifyCodeButton) {
            $this->logger->error("btn not found");

            return false;
        }

        $this->logger->debug("clicking next");
        $verifyCodeButton->click();
        sleep(5);
        $this->saveResponse();
        $this->logger->debug("Current URL: " . $this->http->currentUrl());

        $this->waitForElement(WebDriverBy::xpath('//div[contains(@class, "shangrila-react-login-box-common-err") and normalize-space(.) != ""]'), 10);

        if ($message = $this->http->FindSingleNode('//div[contains(@class, "shangrila-react-login-box-common-err") and normalize-space(.) != ""]')) {
            $this->logger->error("[Error]: {$message}");

            if (
                strstr($message, "Current login method is temporarily unavailable, please try using another login method.")
                || strstr($message, "It looks like you already have a Shangri-La Circle account")
            ) {
                $this->captchaReporting($this->recognizer);
                $this->markConfigAsSuccess();
                throw new CheckException($message, ACCOUNT_PROVIDER_ERROR);
            }

            if (strstr($message, 'Email/Verification code is not valid')) {
                $this->holdSession();
                $this->AskQuestion($this->Question, $message, 'Question');
                $this->markConfigAsSuccess();

                return false;
            }

            $this->DebugInfo = $message;

            return false;
        }

        $this->sendNotification('refs #24741 - need to check 2fa // IZ');

        return $this->Login();
    }

    private function selectProxy(TAccountChecker $selenium)
    {
        $proxyConfig = rand(0, 4);
        $this->logger->debug('proxy config: ' . $proxyConfig);

        switch ($proxyConfig) {
            case 0:
                $selenium->setProxyDOP();

                break;

            case 1:
                $selenium->setProxyBrightData(null, "static");

                break;

            case 2:
                $selenium->requestProxyManager(new MountRotatingRequest());

                break;

            case 3:
                $selenium->setProxyGoProxies();

                break;

            case 4:
                $selenium->setProxyNetNut();
                break;
        }
    }
}
