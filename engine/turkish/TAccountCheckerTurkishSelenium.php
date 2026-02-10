<?php

class TAccountCheckerTurkishSelenium extends TAccountChecker
{
    use SeleniumCheckerHelper;
    use PriceTools;

    private const XPATH_SIGN_IN = '//div[contains(@class, "signin-dropdown") or contains(@class, "signinDropdown")]';
    private const XPATH_SUCCESSFUL = '//button[contains(@class,"signoutBTN")] | //button[./span[normalize-space()="SIGNOUT"]] | //div[@data-bind="text: ffpNumber()"]';
    /**
     * @var HttpBrowser
     */
    public $browser;

    private $turkish;

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->UseSelenium();
        $this->KeepState = true;
        $this->http->saveScreenshots = true;

        $this->useFirefoxPlaywright();
        $this->seleniumRequest->setOs(SeleniumFinderRequest::OS_MAC);
        $this->seleniumOptions->addHideSeleniumExtension = false;
        $this->seleniumOptions->userAgent = null;
    }

    public function LoadLoginForm()
    {
        $this->http->removeCookies();
        $this->http->GetURL('https://www.turkishairlines.com/en-int/');
        $this->waitForElement(WebDriverBy::xpath('
            //h1[contains(text(), "Access Denied")]
            | //h1[contains(text(), "Secure Connection Failed")]
            | ' . self::XPATH_SIGN_IN . '
            | //span[contains(text(), "This site can’t be reached")]
            | //button[@id = \'allowCookiesButton\']
            | ' . self::XPATH_SUCCESSFUL
        ), 10);
        $this->saveResponse();

        $this->driver->executeScript("
            var cookieWarningAcceptId = document.querySelector('#allowCookiesButton');
            if (cookieWarningAcceptId) {
                cookieWarningAcceptId.click();
            }
        ");

        $signin = $this->waitForElement(WebDriverBy::xpath(self::XPATH_SIGN_IN), 0);
        $this->saveResponse();

        if (!$signin) {
            if ($this->waitForElement(WebDriverBy::xpath(self::XPATH_SUCCESSFUL), 0)) {
                $this->logger->notice("session is active, let's parse");

                return true;
            }

            $this->logger->error('something went wrong');
            $this->saveResponse();

            return false;
        }

        $this->waitFor(function () {
            return !$this->waitForElement(WebDriverBy::xpath("//img[@alt='Loading Overlay']"), 0);
        }, 30);
        $but = $this->waitForElement(WebDriverBy::xpath("//button[normalize-space()='Award ticket - Buy a ticket with Miles' or contains(@class,'AwardTicketButton_awardTicketButton')]"),
            0);
        $this->saveResponse();

        if ($but) {
            $but->click();

            if (!$this->waitForElement(WebDriverBy::xpath('//input[@id="tkNumber"]'), 5)) {
                // it's better restart, 99% block
                $this->driver->executeScript("document.getElementById('signinbtn').click();");

                if (!$this->waitForElement(WebDriverBy::xpath('//input[@id="tkNumber"]'), 5)) {
                    throw new CheckRetryNeededException(5, 0);
                }
            }
        } else {
            $this->logger->debug('signin click');
            $signin->click();
        }

        $this->selectLoginType();
        $usernameInput = $this->waitForElement(WebDriverBy::xpath('//input[@id="tkNumber"] | //input[@id="emailAddress"] | //input[@id="idNumber"]'), 10);
        $this->saveResponse();

        $this->AccountFields["Login"] = preg_replace("/\s/ims", '', $this->AccountFields["Login"]);
        $this->AccountFields["Login"] = preg_replace("/^TK/ims", "", $this->AccountFields["Login"]);

        if ($this->AccountFields["Login2"] == "3") {
            $this->AccountFields["Login"] = str_replace("-", "", $this->AccountFields["Login"]);
        }

        // AccountID: 2915447
        if ($this->AccountFields["Login2"] == '1') {
            $this->AccountFields["Login"] = substr($this->AccountFields["Login"], 0, 9);
        }

        // it helps, sometimes click by 'sign in' not wroking
        if (!$usernameInput && $this->waitForElement(WebDriverBy::xpath(self::XPATH_SIGN_IN), 0)) {
            $this->saveResponse();
            unset($usernameInput);
            $this->driver->executeScript("if (document.querySelector('#signin')) document.querySelector('#signin').click(); else document.querySelector('#signinbtn').click();");
            $usernameInput = $this->waitForElement(WebDriverBy::xpath('//input[@id="tkNumber"] | //input[@id="emailAddress"] | //input[@id="idNumber"]'), 5);

            if (!$usernameInput) {
                $usernameInput = $this->waitForElement(WebDriverBy::xpath('//input[@id="tkNumber"] | //input[@id="emailAddress"] | //input[@id="idNumber"]'), 0);
            }
        }

        if (!($passwordInput = $this->waitForElement(WebDriverBy::id('password'), 5))) {
            $passwordInput = $this->waitForElement(WebDriverBy::id('msPassword'), 0);
        }

        $signinBtn = $this->waitForElement(WebDriverBy::xpath('//a[@class="signinBTN"] | // button[@id="msLoginButton"]'), 0);

        if (!$usernameInput || !$passwordInput || !$signinBtn) {
            $this->logger->error('something went wrong');
            $this->saveResponse();

            if ($this->waitForElement(WebDriverBy::xpath(self::XPATH_SUCCESSFUL), 0)) {
                $this->logger->notice("session is active, let's parse");

                return true;
            }

            return false;
        }

        $usernameInput->sendKeys($this->AccountFields['Login']);
        $passwordInput->sendKeys($this->AccountFields['Pass']);
        $this->saveResponse();

        /*
        if ($signinBtn = $this->waitForElement(WebDriverBy::xpath('//a[@class="signinBTN"] | // button[@id="msLoginButton"]'), 0)) {
            $signinBtn->click();
        }
        */
        sleep(2);

//        $mover->duration = random_int(100000, 200000);
//        $mover->steps = random_int(5, 10);

        $signinBtn = $this->waitForElement(WebDriverBy::xpath('//a[@class="signinBTN"] | // button[@id="msLoginButton"]'), 3);
        $this->saveResponse();

        if ($signinBtn) {
            try {
                $this->logger->error('Mouse');
                $mover = new MouseMover($this->driver);
                $mover->logger = $this->logger;
                $mover->moveToElement($signinBtn);
                $mover->click();
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
                $this->logger->debug("click sign in Btn by mouseMover was prevented by StaleElementException");
                $signinBtn->click();
            }
            sleep(1);
        }

//        if ($signinBtn = $this->waitForElement(WebDriverBy::xpath('//a[@class="signinBTN"] | // button[@id="msLoginButton"]'), 0)) {
//            $this->saveResponse();
//            $this->logger->debug("click sign in Btn");
//            $this->driver->executeScript("
//                if (document.querySelector(\"button[id='msLoginButton']\")) {
//                    document.querySelector(\"button[id='msLoginButton']\").click();
//                }
//            ");
//        }

        return true;
    }

    public function checkErrors()
    {
        $this->logger->notice(__METHOD__);

        return false;
    }

    public function Login()
    {
        $this->waitForElement(WebDriverBy::xpath('//div[contains(@class, "style_error-modal-header") or contains(@class, "style_error-message")] | //div[contains(text(), "Please verify your one-time password sent to your")]'), 15);
        $loginSuccess = $this->waitForElement(WebDriverBy::xpath('//li/button/span[contains(text(),"SIGNOUT")]'), 0);
        $this->saveResponse();

        if ($loginSuccess) {
            return true;
        }

        if ($this->http->FindSingleNode('//div[contains(text(), "Please verify your one-time password sent to your")]')) {
            return $this->processSecurityCheckpoint();
        }

        if ($message = $this->http->FindSingleNode('
                //div[contains(@class, "style_error-modal-header") or contains(@class, "style_error-message")]                
            ')
        ) {
            $this->logger->error("[Error]: {$message}");

            if (
                stristr($message, "You have entered an invalid information.")
                || stristr($message, "Smiles membership number or password and try again.")
                || strstr($message, "Smiles membership number you entered is incorrect")
                || $message == 'Your password must be 6 digits long.'
                || $message == 'Turkish ID number has been entered incorrectly.'
                || $message == 'Please enter a valid e-mail address or password and try again.'
            ) {
                throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
            }

            if (
                stristr($message, "The account was protected because too many incorrect attempts were made.")
            ) {
                throw new CheckException($message, ACCOUNT_LOCKOUT);
            }

            if (
                stristr($message, "We are currently unable to process your request. Please try again later.")
                || stristr($message, "We are unable to process your request at this time")
            ) {
                throw new CheckException($message, ACCOUNT_PROVIDER_ERROR);
            }

            $this->DebugInfo = $message;

            return false;
        }

        return $this->checkErrors();
    }

    public function processSecurityCheckpoint()
    {
        $this->logger->notice(__METHOD__);
        $this->logger->info('Security Question', ['Header' => 3]);

        $q = $this->waitForElement(WebDriverBy::xpath('//div[contains(text(), "Please verify your one-time password sent to your")]'), 0);

        if (!$q) {
            return false;
        }

        $question = $q->getText();
        $codeInput = $this->waitForElement(WebDriverBy::xpath('//input[@data-testid = "otpTimerModalInput"]'), 0);
        $button = $this->waitForElement(WebDriverBy::xpath('//button[@data-testid = "otpTimerModalVerifyButton"]'), 0);
        $this->saveResponse();

        if (!isset($question) || !$codeInput || !$button) {
            return false;
        }

        if ($question && !isset($this->Answers[$question])) {
            $this->holdSession();
            $this->AskQuestion($question, null, "Question");

            return false;
        }

        $answer = $this->Answers[$question];
        unset($this->Answers[$question]);

        $this->logger->debug("entering answer...");
        $codeInput->clear();
        $codeInput->sendKeys($answer);
        $this->saveResponse();

        $this->logger->debug("click button...");
        $button->click();

        $error = $this->waitForElement(WebDriverBy::xpath("//div[contains(text(), 'Invalid code. Please try again.')]"), 5); // TODO
        $this->saveResponse();

        if ($error) {
            $this->logger->notice("resetting answers");
            $this->holdSession();
            $this->AskQuestion($question, $error->getText(), "Question");

            return false;
        }

        $this->logger->debug("success");
        $this->waitForElement(WebDriverBy::xpath(self::XPATH_SUCCESSFUL), 10);
        $this->saveResponse();

        return $this->loginSuccessful();
    }

    public function ProcessStep($step)
    {
        $this->logger->notice(__METHOD__);
        $this->logger->debug("Current URL: " . $this->http->currentUrl());

        if ($this->isNewSession()) {
            return $this->LoadLoginForm() && $this->Login();
        }

        if ($step == "Question") {
            return $this->processSecurityCheckpoint();
        }

        return false;
    }

    public function Parse()
    {
        $this->turkish = $this->getTurkish();
        $this->turkish->Parse();
        $this->SetBalance($this->turkish->Balance);
        $this->Properties = $this->turkish->Properties;
        $this->ErrorCode = $this->turkish->ErrorCode;

        if ($this->ErrorCode != ACCOUNT_CHECKED) {
            $this->ErrorMessage = $this->turkish->ErrorMessage;
            $this->DebugInfo = $this->turkish->DebugInfo;
        }
    }

    public function ParseItineraries()
    {
        $this->turkish = $this->getTurkish();
        $this->turkish->ParseItineraries();

        return [];
    }

    public function GetHistoryColumns()
    {
        return [
            "Date"        => "PostingDate",
            "Type"        => "Info",
            "Description" => "Description",
            "Miles"       => "Miles",
        ];
    }

    public function ParseHistory($startDate = null)
    {
        $this->turkish = $this->getTurkish();
        $this->turkish->ParseItineraries();

        return $this->turkish->ParseHistory($startDate);
    }

    protected function getTurkish()
    {
        $this->logger->notice(__METHOD__);

        if (!isset($this->turkish)) {
            $this->turkish = new TAccountCheckerTurkish();
            $this->turkish->http = new HttpBrowser("none", new CurlDriver());
            $this->turkish->http->setProxyParams($this->http->getProxyParams());

            $this->http->brotherBrowser($this->turkish->http);
            $this->turkish->AccountFields = $this->AccountFields;
            $this->turkish->itinerariesMaster = $this->itinerariesMaster;
            $this->turkish->HistoryStartDate = $this->HistoryStartDate;
            $this->turkish->historyStartDates = $this->historyStartDates;
            $this->turkish->http->LogHeaders = $this->http->LogHeaders;
            $this->turkish->ParseIts = $this->ParseIts;
            $this->turkish->ParsePastIts = $this->ParsePastIts;
            $this->turkish->WantHistory = $this->WantHistory;
            $this->turkish->WantFiles = $this->WantFiles;
            $this->turkish->strictHistoryStartDate = $this->strictHistoryStartDate;

            $this->turkish->globalLogger = $this->globalLogger;
            $this->turkish->logger = $this->logger;
            $this->turkish->onTimeLimitIncreased = $this->onTimeLimitIncreased;
        }

        $cookies = $this->driver->manage()->getCookies();
        $this->logger->debug("set cookies");

        foreach ($cookies as $cookie) {
            $this->turkish->http->setCookie($cookie['name'], $cookie['value'], $cookie['domain'], $cookie['path'], $cookie['expiry'] ?? null);
        }

        return $this->turkish;
    }

    private function selectLoginType()
    {
        $this->logger->notice(__METHOD__);

        if (empty($this->AccountFields['Login2'])) {
            $this->logger->debug('login2 is empty');

            return;
        }

        $selectLoginTypeButton = $this->waitForElement(webdriverBy::xpath('//button[contains(@class, "Login") and contains(@class, "dropdown") and not(@id)]'), 10);
        $this->saveResponse();
        $selectLoginTypeButton->click();

        if (!isset($selectLoginTypeButton)) {
            $this->logger->debug('login button not found');

            return;
        }

        switch ($this->AccountFields['Login2']) {
            case '1':
                $loginOptionXpath = '//button[@id="preferencesMemberNumber"]';

                break;

            case '2':
                $loginOptionXpath = '//button[@id="preferencesMail"]';

                break;

            case '4':
                $loginOptionXpath = '//button[@id="preferencesIdNumber"]';

                break;
        }

        $option = $this->waitForElement(WebDriverBy::xpath($loginOptionXpath), 5);
        $this->saveResponse();

        if (!isset($option)) {
            $this->logger->debug('login option not found');

            return;
        }

        $option->click();
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);

        return $this->turkish->loginSuccessful();
    }
}
