<?php

use AwardWallet\Common\Selenium\FingerprintFactory;
use AwardWallet\Common\Selenium\FingerprintRequest;
use AwardWallet\Engine\ProxyList;

class TAccountCheckerOfficedepotSelenium extends TAccountChecker
{
    use SeleniumCheckerHelper;
    use ProxyList;
    private const REWARDS_PAGE_URL = "https://www.officedepot.com/account/accountSummaryDisplay.do";
    private const WAIT_TIMEOUT = 10;
    private const SHORT_TIMEOUT = 5;
    private const WITHOUT_TIMEOUT = 0;
    private $profileDataResponce = null;

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
        $this->http->setHttp2(true);
        /*
        $this->http->SetProxy($this->proxyReCaptcha(), false);
        */
        $this->setProxyGoProxies();
        $this->UseSelenium();

        if ($this->attempt == 0) {
            $this->useGoogleChrome();
            $request = FingerprintRequest::chrome();
            $request->browserVersionMin = HttpBrowser::BROWSER_VERSION_MIN;
            $request->platform = 'Win32';
            $fingerprint = $this->services->get(FingerprintFactory::class)->getOne([$request]);
            if ($fingerprint !== null) {
                $this->seleniumOptions->fingerprint = $fingerprint->getFingerprint();
                $this->http->setUserAgent($fingerprint->getUseragent());
            }
        } else if ($this->attempt == 1) {
            $this->useCamoufox();
            $this->seleniumOptions->addPuppeteerStealthExtension = false;
            $this->seleniumOptions->addHideSeleniumExtension = false;
            $this->seleniumOptions->userAgent = null;
            $this->http->saveScreenshots = true;
        } else {
            $this->useChromeExtension();
            $this->seleniumRequest->setOs(SeleniumFinderRequest::OS_MAC);
            $this->seleniumOptions->addPuppeteerStealthExtension = false;
            $this->seleniumOptions->addHideSeleniumExtension = false;
            $this->seleniumOptions->userAgent = null;
            $this->http->saveScreenshots = true;
        }

        $this->usePacFile(false);
        $this->http->saveScreenshots = true;
        $this->seleniumOptions->recordRequests = true;
    }

    public function IsLoggedIn()
    {
        $this->http->RetryCount = 0;
        $this->http->GetURL(self::REWARDS_PAGE_URL, [], 20);
        $this->http->RetryCount = 2;

        if ($this->loginSuccessful()) {
            return true;
        }

        return false;
    }

    public function LoadLoginForm()
    {
        $this->http->removeCookies();
        $this->driver->manage()->window()->maximize();
        $this->http->GetURL("https://www.officedepot.com/account/loginAccountSet.do");
        $username = $this->waitForElement(WebDriverBy::xpath('//input[@name="username"]'), self::WAIT_TIMEOUT);
        $password = $this->waitForElement(WebDriverBy::xpath('//input[@name="password"]'), self::WITHOUT_TIMEOUT);
        $rememberMe = $this->waitForElement(WebDriverBy::xpath('//label[@for="login-checkbox"]'), self::WITHOUT_TIMEOUT);
        $this->driver->executeScript('var c = document.getElementById("onetrust-accept-btn-handler"); if (c) c.click();');
        $this->saveResponse();

        if (!isset($username, $password)) {
            return $this->checkErrors();
        }

        if (isset($rememberMe)) {
            $rememberMe->click();
        }
        $username->clear();
        $username->sendKeys($this->AccountFields['Login']);
        $password->clear();
        $password->sendKeys($this->AccountFields['Pass']);
        $submit = $this->waitForElement(WebDriverBy::xpath('//div[@class="button-container"]/button[contains(@class, "login-submit-button")]'), self::SHORT_TIMEOUT);
        $this->saveResponse();

        if (!isset($submit)) {
            return $this->checkErrors();
        }
        $submit->click();

        return true;
    }

    public function checkErrors()
    {
        $this->logger->notice(__METHOD__);

        if (
            $this->http->FindSingleNode('//p[contains(normalize-space(), "Rest assured we are working diligently to resolve this issue. If you would like to place an order by phone or speak with one of our Customer Service representatives please contact us")]')
            && $this->http->Response['code'] == 403
        ) {
            throw new CheckRetryNeededException(2, 1);
        }

        if ($message = $this->http->FindSingleNode('//h1[contains(text(), "We\'ll Be Back Online Soon!")]')) {
            throw new CheckException($message, ACCOUNT_PROVIDER_ERROR);
        }

        return false;
    }

    public function Login()
    {
        $this->waitForElement(WebDriverBy::xpath('
            //div[contains(@class, "login-error")]//p
            | //button[contains(@class, "send-code-btn")]
            | //div[@id="userInfo"]//strong
        '), self::WAIT_TIMEOUT);
        $this->saveResponse();

        if ($this->loginSuccessful()) {
            return true;
        }

        if ($this->processQuestion()) {
            return false;
        }

        if ($this->checkLoginErrors()) {
            return false;
        }

        return $this->checkErrors();
    }

    public function ProcessStep($step)
    {
        $this->logger->notice(__METHOD__);
        $this->saveResponse();

        return $this->processQuestion();
    }

    public function Parse()
    {
        $this->markProxySuccessful();
        $this->logger->debug($this->http->currentUrl());
        $this->getRecordedRequests();
        $this->saveResponse();
        $response = $this->profileDataResponce;

        if (!isset($response)) {
            $this->logger->debug('Failed to catch profile data request. Trying to reload page and catch again');
            $this->http->GetURL(self::REWARDS_PAGE_URL);
            $this->waitForElement(WebDriverBy::xpath('//div[@id="userInfo"]//strong'), self::WAIT_TIMEOUT);
            $this->saveResponse();
        }

        $this->getRecordedRequests();
        $response = $this->profileDataResponce;

        if (!isset($response)) {
            $this->logger->debug('Failed to catch profile data request. Throwing retry exception');

            throw new CheckRetryNeededException(3, 0, self::PROVIDER_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
        }

        if (
            isset($response->wlrActiveLoyaltyID, $response->showPoints)
            && $response->wlrActiveLoyaltyID == false && $response->showPoints == false
        ) {
            $this->SetWarning(self::NOT_MEMBER_MSG);

            return;
        }

        // Balance - Available Rewards:
        $this->SetBalance($response->totalAvailableRewards ?? null);
        // Member Number:
        $this->SetProperty("Number", $response->loyaltyID ?? null);
        $vipTierEndDate = $response->vipTierEndDate ?? null;
        $vip = $response->vip ?? null;

        if (
            $vip == true
            && $vipTierEndDate
        ) {
            // VIP status expires
            $this->SetProperty("Status", "VIP");
            $this->SetProperty("StatusExpiration", $vipTierEndDate);
        }

        if (
            $vip == false
            && isset($response->vipTierNeedToSpend, $response->pilotTotalSpent)
        ) {
            // Spend to Next Level
            $this->SetProperty("SpendNextLevel", "$" . $response->vipTierNeedToSpend);
            // Spent YTD
            $this->SetProperty("SpentYTD", "$" . $response->pilotTotalSpent);
        }
        $loyaltyPoints = $response->loyaltyPoints ?? null;
        $totalPendingRewards = $response->totalPendingRewards ?? null;

        // refs #19977
        if (
            !empty($loyaltyPoints)
            || !empty($totalPendingRewards)
        ) {
            $this->sendNotification('Properties not empty, check on site, perhaps need to collect. - refs #19977');
        }

        // Reward Certificates
        if (!empty($response->loyaltyRewards) && is_array($response->loyaltyRewards)) {
            $this->SetProperty("CombineSubAccounts", false);

            foreach ($response->loyaltyRewards as $reward) {
                // Reward Certificate
                $code = $reward->fullRewardNumber;
                // Expiration Date
                $exp = $reward->expirationDate;
                // Balance
                $balance = $reward->balance;

                if (strtotime($exp) && isset($code)) {
                    $this->AddSubAccount([
                        'Code'           => 'officedepotCertificate' . str_replace('************', '', $code),
                        'DisplayName'    => "Reward Certificate #" . $code,
                        'Balance'        => $balance,
                        'Number'         => $code,
                        'ExpirationDate' => strtotime($exp),
                        'Pin'            => $reward->pin,
                    ]);
                }
            }
        }
    }

    private function processQuestion()
    {
        $this->logger->notice(__METHOD__);

        if ($this->http->FindSingleNode('//button[contains(@class, "send-code-btn")]')) {
            if ($this->isBackgroundCheck() && !$this->getWaitForOtc()) {
                $this->Cancel();
            }

            $emailOption = $this->waitForElement(WebDriverBy::xpath('//input[@value="email"]/following-sibling::label'), self::WITHOUT_TIMEOUT);
            $smsOption = $this->waitForElement(WebDriverBy::xpath('//input[@value="textMessage"]/following-sibling::label'), self::WITHOUT_TIMEOUT);
            $voiceOption = $this->waitForElement(WebDriverBy::xpath('//input[@value="voice"]/following-sibling::label'), self::WITHOUT_TIMEOUT);
            $this->saveResponse();

            if (isset($emailOption)) {
                $emailOption->click();
                $this->saveResponse();
            } elseif (isset($smsOption)) {
                $smsOption->click();
                $this->saveResponse();
            } elseif (isset($voiceOption)) {
                $voiceOption->click();
                $this->saveResponse();
            }

            $submit = $this->waitForElement(WebDriverBy::xpath('//button[contains(@class, "send-code-btn")]'), self::SHORT_TIMEOUT);
            $this->saveResponse();

            if (!$submit) {
                $this->logger->error("btn not found");

                return false;
            }

            $this->logger->debug("clicking next");
            $submit->click();
        }

        $this->waitForElement(WebDriverBy::xpath('//p[@class="validate-code-btn"]'), self::WAIT_TIMEOUT);
        $this->saveResponse();
        $question = $this->http->FindSingleNode('//p[@class="verify-code-greeting"]');
        $input = $this->waitForElement(WebDriverBy::xpath('//input[@id="validation-code-input-element"]'), self::WITHOUT_TIMEOUT);

        if (!isset($question, $input)) {
            return false;
        }

        if (!$input) {
            $this->logger->error("question input not found");

            return false;
        }

        if (!isset($this->Answers[$question])) {
            $this->holdSession();
            $this->AskQuestion($question, null, "Question");

            return true;
        }

        $answer = $this->Answers[$question];
        unset($this->Answers[$question]);

        $input->clear();
        $input->sendKeys($answer);
        $this->logger->debug("ready to click");
        $this->saveResponse();
        $submit = $this->waitForElement(WebDriverBy::xpath('//button[contains(@class, "validate-code-btn") and not(@disabled)]'), self::SHORT_TIMEOUT);
        $this->saveResponse();

        if (!$submit) {
            $this->logger->error("btn not found");

            return false;
        }

        $this->logger->debug("clicking next");
        $submit->click();

        sleep(5);
        $this->saveResponse();

        if ($error = $this->waitForElement(WebDriverBy::xpath('//div[contains(@class, "validation-code-error")]//div[contains(@class, "od-callout-description")]'), self::SHORT_TIMEOUT)) {
            $message = $error->getText();
            $this->logger->error("[Error]: {$message}");

            if (strstr($message, 'Re-enter your validation code or request a new code')) {
                $this->holdSession();
                $this->AskQuestion($question, $message, "Question");
            }
            $this->DebugInfo = $message;
        }

        $this->saveResponse();

        if ($this->loginSuccessful()) {
            return true;
        }

        if ($this->checkLoginErrors()) {
            return true;
        }

        return false;
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);
        $welcomeMessage = $this->waitForElement(WebDriverBy::xpath('//div[contains(@class, "od-header-account-status-message")]'), self::WAIT_TIMEOUT);
        $this->saveResponse();
        $url = $this->http->currentUrl();
        $this->logger->debug("[Current URL]: " . $url);

        if (!isset($welcomeMessage)) {
            return false;
        }

        if (
            isset($welcomeMessage)
            && strstr($welcomeMessage->getText(), 'Welcome')
        ) {
            $this->http->GetURL(self::REWARDS_PAGE_URL);
            $this->waitForElement(WebDriverBy::xpath('//div[@id="userInfo"]//strong'), self::WAIT_TIMEOUT);
            $this->saveResponse();

            return true;
        }

        return false;
    }

    private function getRecordedRequests()
    {
        $this->logger->notice(__METHOD__);

        try {
            $requests = $this->http->driver->browserCommunicator->getRecordedRequests();
        } catch (AwardWallet\Common\Selenium\BrowserCommunicatorException $e) {
            $this->logger->error("BrowserCommunicatorException: " . $e->getMessage(), ['HtmlEncode' => true]);

            $requests = [];
        }

        foreach ($requests as $xhr) {
            $this->logger->info('CATCHED REQUEST: ' . $xhr->request->getUri());
            /*
            $this->logger->debug(var_export($xhr->response->getBody(), true));
            */
            /*
            if (strstr($xhr->request->getUri(), 'getRewards.do') && !isset($this->profileDataResponce)) {
                $this->profileDataResponce = $this->http->JsonLog(json_encode($xhr->response->getBody()));
                $this->logger->debug('Catched profile data request');
            }
            */
            if (strstr($xhr->request->getUri(), 'getRewards.do') && !isset($this->profileDataResponce)) {
                $this->profileDataResponce = $this->http->JsonLog(json_encode($xhr->response->getBody()));
                $this->logger->debug('Catched profile data request');
            }
        }
    }

    private function checkLoginErrors()
    {
        $message = $this->http->FindSingleNode('//div[contains(@class, "login-error")]//p');

        if (isset($message)) {
            $this->logger->error("[Error]: {$message}");

            if (strstr($message, 'We were unable to validate your credentials. Please verify and try again.')) {
                throw new CheckRetryNeededException(3, 0, self::CAPTCHA_ERROR_MSG);
            }

            if (
                strstr($message, 'Your login name or Password is incorrect ')
                || strstr($message, 'You have entered invalid information (passwords are case sensitive). The next incorrect login will deactivate your login ID.')
                || strstr($message, 'Due to security reasons, your login ID is no longer active. Please contact Customer Service at ')
                || strstr($message, 'The login name or Password is incorrect (passwords are case sensitive). Please try again.')
            ) {
                throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
            }

            if (
                strstr($message, 'The features of your contract account are supported at business.officedepot.com')
                || strstr($message, 'Sorry we could not log you in at this time. Please try again later.')
                || strstr($message, 'Something went wrong, please refresh and try again.')
            ) {
                /*
                throw new CheckException($message, ACCOUNT_PROVIDER_ERROR);
                */
                throw new CheckRetryNeededException(3, 10, $message, ACCOUNT_PROVIDER_ERROR);
            }

            if (
                strstr($message, "A backend is not available.")
            ) {
                throw new CheckException(self::PROVIDER_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
            }

            if (
                strstr($message, 'Due to security reasons, your password has been deactivated')
                || strstr($message, 'Due to multiple failed login attempts, your account has been locked.')
            ) {
                throw new CheckException($message, ACCOUNT_LOCKOUT);
            }

            $this->DebugInfo = $message;

            return true;
        }

        // broken account, no errors, no auth
        if (in_array($this->AccountFields['Login'], [
            'Djfeliciano35@yahoo.com',
        ])) {
            throw new CheckException(self::PROVIDER_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
        }
    }
}
