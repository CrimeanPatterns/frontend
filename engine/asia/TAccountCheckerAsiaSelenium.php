<?php

namespace AwardWallet\Engine\asia;

use AwardWallet\Engine\ProxyList;
use CaptchaRecognizer;
use CheckException;
use CheckRetryNeededException;
use CurlDriver;
use HttpBrowser;
use MouseMover;
use NoAlertOpenException;
use NoSuchDriverException;
use NoSuchWindowException;
use ScriptTimeoutException;
use SeleniumCheckerHelper;
use SessionNotCreatedException;
use StaleElementReferenceException;
use TAccountChecker;
use TAccountCheckerAsia;
use TimeOutException;
use UnexpectedAlertOpenException;
use UnexpectedJavascriptException;
use UnknownServerException;
use WebDriverBy;
use WebDriverException;

class TAccountCheckerAsiaSelenium extends TAccountChecker
{
    use SeleniumCheckerHelper;
    use ProxyList;

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
        $this->UseSelenium();
        $this->setProxyGoProxies();
        $this->useChromePuppeteer();
        $this->seleniumOptions->fingerprintOptions = ['devices' => ['desktop'], 'operatingSystems' => ['linux']];
        $this->seleniumOptions->addHideSeleniumExtension = false;
        $this->seleniumOptions->userAgent = null;
        $this->disableImages();
        $this->keepCookies(false);
        $this->usePacFile(false);
        $this->http->saveScreenshots = true;
    }

    public function IsLoggedIn()
    {

        return false;
    }

    public function LoadLoginForm()
    {
        $this->http->GetURL("https://www.cathaypacific.com/cx/en_US/sign-in.html?switch=Y");
        sleep(random_int(4, 6));
        if (filter_var($this->AccountFields['Login'], FILTER_VALIDATE_EMAIL) === false) {
            $typeAuth = 'Sign in with membership number';
            $typeLogin = 'membership';
        } else {
            $typeAuth = 'Sign in with email';
            $typeLogin = 'email';
        }

        $this->waitForElement(WebDriverBy::xpath("//button[contains(normalize-space(),'{$typeAuth}')] 
        | //h1[contains(text(), 'Access Denied')] 
        | //span[contains(text(), 'This site can’t be reached')]"),
            15);
        $this->saveResponse();

        $overlayClose = $this->waitForElement(WebDriverBy::xpath('//div[contains(@class, "ot-overlay-close")]//*[@aria-label="Close"]'),
            0);
        if ($overlayClose) {
            $overlayClose->click();
            $this->saveResponse();
        }

        $overlayClose = $this->waitForElement(WebDriverBy::xpath('//button[contains(text(),"Accept all")]'), 5);
        if ($overlayClose) {
            $overlayClose->click();
            $this->saveResponse();
        }

        $btn = $this->waitForElement(WebDriverBy::xpath("//button[contains(normalize-space(),'{$typeAuth}')]"), 0);
        if (!$btn) {
            $this->logger->error("{$typeAuth} not found");
            $this->saveResponse();

            if ($this->http->FindSingleNode('(//h1[contains(text(), "Access Denied")] | //div[contains(text(), "Error loading chunks!")] | //span[contains(text(), \'This site can’t be reached\')])[1]')) {
                throw new CheckRetryNeededException(3, 0);
            }

            return false;
        }

        $btn->click();

        // login
        $login = $this->waitForElement(WebDriverBy::xpath("//input[@name='{$typeLogin}']"), 5);
        $this->saveResponse();

        if (!$login && ($btn = $this->waitForElement(WebDriverBy::xpath("//button[contains(normalize-space(),'{$typeAuth}')]"),
                0))) {
            $this->logger->notice("scroll to btn");
            $x = $btn->getLocation()->getX();
            $y = $btn->getLocation()->getY() - 200;
            $this->driver->executeScript("window.scrollBy($x, $y)");
            $this->saveResponse();

            $btn->click();
            $login = $this->waitForElement(WebDriverBy::xpath("//input[@name='{$typeLogin}']"), 2);
            $this->saveResponse();
        }

        if (!$login) {
            $this->logger->error("login field(s) not found");
            $this->saveResponse();

            return false;
        }

        $mover = new MouseMover($this->driver);
        $mover->logger = $this->logger;
        $mover->sendKeys($login, $this->AccountFields['Login'], 5);
        $this->saveResponse();
        sleep(2);

        if ($contBtn = $this->waitForElement(WebDriverBy::xpath('//button[contains(@data-tealium-event-action, "SIGN_IN::CONTINUE_BTN::")]'),
            0)) {
            $this->saveResponse();
            $contBtn->click();
            $this->waitForElement(WebDriverBy::xpath("//input[@name='password']"), 5);
        }

        // password
        $pwd = $this->waitForElement(WebDriverBy::xpath("//input[@name='password']"), 0);
        if (!$pwd) {
            $this->logger->error("password field(s) not found");
            $this->saveResponse();
            if ($msg = $this->http->FindSingleNode('//span[contains(text(),"Your account is no longer active. Please contact ")]')) {
                throw new CheckException($msg, ACCOUNT_LOCKOUT);
            }
            if ($msg = $this->http->FindSingleNode('//span[contains(text(),"Your sign-in ID is incorrect. Please try again.")]')) {
                throw new CheckException($msg, ACCOUNT_INVALID_PASSWORD);
            }
            if ($msg = $this->http->FindSingleNode('//span[contains(text(),"Input contains invalid characters. Valid characters is 0-9.")]')) {
                throw new CheckException($msg, ACCOUNT_INVALID_PASSWORD);
            }
            if (!$this->waitForElement(WebDriverBy::xpath("//input[@name='{$typeLogin}']"), 0)) {
                throw new CheckRetryNeededException(3, 0);
            }
        }

        $mover->sendKeys($pwd, $this->AccountFields['Pass'], 5);
        sleep(2);
        $btn = $this->waitForElement(WebDriverBy::xpath("//button[@data-tealium-event-action = 'SIGN_IN::ACCOUNT_CHECK::SIGN_IN_WITH_PASSWORD_BTN::WITH_PASSKEY' or @data-tealium-event-action = 'SIGN_IN::ACCOUNT_CHECK::SIGN_IN_WITH_PASSWORD_BTN::NO_PASSKEY']"),
            0);
        $this->saveResponse();
        $btn->click();

        sleep(5);

        if ($btn = $this->waitForElement(WebDriverBy::xpath("//button[@data-tealium-event-action = 'SIGN_IN::VERIFY_ID::SIGN_IN_WITH_OTP_BTN']"),
            0)) {
            $this->saveResponse();
            $btn->click();
        }

        $this->waitForElement(WebDriverBy::xpath("
                //span[contains(@class, 'welcomeLabel ')][starts-with(normalize-space(),'Welcome,')] 
                | //h2[contains(text(), 'Confirm your mobile phone number') or contains(text(), 'We need to verify your identity') or contains(text(), 'Enter your mobile phone number')] 
                | //a[contains(., 'Continue to sign in')] 
                | //label[contains(@class, 'textfield__errorMessage')] 
                | //div[contains(@class, 'serverSideError__messages')] 
                | //h1[contains(text(), 'Access Denied')]
                | //h1[contains(text(), 'Update your password')]
                | //span[contains(text(), 'This site can’t be reached')]
                | //h1[contains(text(), 'Secure Connection Failed')]
                | //h2[contains(text(), 'Enter verification code')]
                | //h2[contains(text(), 'Passwordless sign-in with passkey')]
            "), 30);
        $this->saveResponse();

        $question = $this->http->FindSingleNode("//h2[contains(text(), 'Enter verification code')]/following-sibling::p");
        if ($this->http->FindSingleNode("//h2[contains(text(), 'Enter verification code')]")) {
            $this->holdSession();
            $this->AskQuestion($question, null, 'phone');
            return false;
        }
        return true;
    }

    public function processSecurityCheckpoint()
    {
        $this->logger->notice(__METHOD__);
        $answer = $this->Answers[$this->Question];
        unset($this->Answers[$this->Question]);
        $this->logger->debug("Question to -> $this->Question=$answer");
        $otpInput = $this->waitForElement(WebDriverBy::xpath('(//div[@class="cpc-otp"]//input)[1]'), 5);
        if (!$otpInput) {
            $this->saveResponse();

            return false;
        }
        $this->logger->debug("entering code...");
        $elements = $this->driver->findElements(WebDriverBy::xpath('//div[@class="cpc-otp"]//input'));

        foreach ($elements as $key => $element) {
            $this->logger->debug("#{$key}: {$answer[$key]}");
            $element->click();
            $element->sendKeys($answer[$key]);
            $this->saveResponse();
        }

        $button = $this->waitForElement(WebDriverBy::xpath('//button[contains(@data-tealium-event-action, "SIGN_IN::OTP_VERIFICATION::SIGN_IN_BTN")]'), 1);

        if (!$button) {
            return false;
        }

        $button->click();

        $message = $this->waitForElement(WebDriverBy::xpath('
            //span[contains(@class, "welcomeLabel ")][starts-with(normalize-space(),"Welcome,")]
            | //span[contains(text(),"Incorrect verification code. Please try again or request a new code to proceed.")]
        '), 15);
        if (!$message) {
            $this->saveResponse();
            return false;
        }
        if (
            strstr($message->getText(), 'Incorrect verification code. Please try again or request a new code to proceed.')
        ) {
            $this->holdSession();
            $this->AskQuestion($this->Question, $message->getText(), "phone");

            return false;
        }
        $this->logger->debug("success");
        $this->logger->debug("[Current URL]: {$this->http->currentUrl()}");
        return $this->loginSuccessful();
    }

    public function ProcessStep($step)
    {
        $this->logger->notice(__METHOD__);
        $this->logger->debug("Current URL: " . $this->http->currentUrl());

        if ($this->isNewSession()) {
            return $this->LoadLoginForm() && $this->Login();
        }

        if ($step == 'phone') {
            if ($this->processSecurityCheckpoint()) {
                $this->saveResponse();
                return true;
            }
        }

        return false;
    }

    protected function getAsia()
    {
        $this->logger->notice(__METHOD__);
        if (!isset($this->asia)) {
            $this->asia = new TAccountCheckerAsia();
            $this->asia->http = new HttpBrowser("none", new CurlDriver());
            $this->asia->http->setProxyParams($this->http->getProxyParams());

            $this->http->brotherBrowser($this->asia->http);
            $this->asia->AccountFields = $this->AccountFields;
            $this->asia->http->SetBody($this->http->Response['body']);
            $this->asia->itinerariesMaster = $this->itinerariesMaster;
            $this->asia->HistoryStartDate = $this->HistoryStartDate;
            $this->asia->historyStartDates = $this->historyStartDates;
            $this->asia->http->LogHeaders = $this->http->LogHeaders;
            $this->asia->ParseIts = $this->ParseIts;
            $this->asia->ParsePastIts = $this->ParsePastIts;
            $this->asia->WantHistory = $this->WantHistory;
            $this->asia->WantFiles = $this->WantFiles;
            $this->asia->strictHistoryStartDate = $this->strictHistoryStartDate;
            $this->logger->debug("set headers");
            $defaultHeaders = $this->http->getDefaultHeaders();

            foreach ($defaultHeaders as $header => $value) {
                $this->asia->http->setDefaultHeader($header, $value);
            }

            $this->asia->globalLogger = $this->globalLogger;
            $this->asia->logger = $this->logger;
            $this->asia->onTimeLimitIncreased = $this->onTimeLimitIncreased;
        }

        $cookies = $this->driver->manage()->getCookies();
        $this->logger->debug("set cookies");

        foreach ($cookies as $cookie) {
            $this->asia->http->setCookie($cookie['name'], $cookie['value'], $cookie['domain'], $cookie['path'], $cookie['expiry'] ?? null);
        }

        return $this->asia;
    }

    public function checkErrors()
    {
        return false;
    }


    public function Login()
    {
        $this->saveResponse();
        $msg = $this->http->FindSingleNode('//label[contains(@class, "textfield__errorMessage")]')
            ?? $this->http->FindSingleNode('//div[contains(@class, "serverSideError__messages")]');

        if ($msg) {
            $this->logger->error("[Error]: {$msg}");

            if (
                $msg === 'Your sign-in details are incorrect. Please check your details and try again. [ Error Code: 2004 ]'
                || strpos($msg, 'Your member account is temporarily locked after too many unsuccessful login attempts. You can reset your password by confirming your personal information') !== false
                || strpos($msg, 'Sorry, we are experiencing technical issues. Please try again later.') !== false
            ) {
                throw new CheckException($msg, ACCOUNT_PROVIDER_ERROR);
            }

            if (
                strpos($msg, 'You have reached the daily quota for verification code attempts.') !== false
                || strpos($msg, 'Your account has been locked') !== false
                || strpos($msg, 'You have exceeded the maximum number of failed attempts') !== false
            ) {
                throw new CheckException($msg, ACCOUNT_LOCKOUT);
            }

            if (stripos($msg, 'The password you entered is incorrect. Please try again or reset your password') !== false
                || strpos($msg, 'The email address you have entered is incorrect') !== false
                || strpos($msg, 'The email address you have entered is incorrect') !== false
                || strpos($msg, 'Email has not been linked and verified. Please sign in using your membership number') !== false
                || strpos($msg, 'Inactive Membership number / Username.') !== false
                || strpos($msg, 'Inactive Membership number.') !== false
                || strpos($msg, 'Your email has not been set as a sign-in ID. Please sign in using your membership number.') !== false
                || strpos($msg, 'We couldn\'t verify your account.') !== false
            ) {
                throw new CheckException($msg, ACCOUNT_INVALID_PASSWORD);
            }

            $this->DebugInfo = $msg;

            return false;
        }

        if ($this->http->FindSingleNode("//h1[contains(text(), 'Update your password')]")) {
            $this->throwProfileUpdateMessageException();
        }

        if ($this->http->FindSingleNode("
            //h2[contains(text(), 'Confirm your mobile phone number') or contains(text(), 'We need to verify your identity') or contains(text(), 'Enter your mobile phone number')] 
            | //a[contains(., 'Continue to sign in')] 
            | //a[contains(@class,'postponedPasskeyLink')]")) {

            $later = $this->waitForElement(WebDriverBy::xpath("//a[contains(normalize-space(),'Remind me later')] 
                | //a[contains(., 'Continue to sign in')]
                | //a[contains(@class,'postponedPasskeyLink')]"), 0);

            if (!$later) {
                $this->saveResponse();

                return false;
            }

            $later->click();
            $this->waitForElement(WebDriverBy::xpath("
                    //span[contains(@class, 'welcomeLabel ')][starts-with(normalize-space(),'Welcome,')] 
                    | //label[contains(@class, 'textfield__errorMessage')] 
                    | //h1[contains(text(), 'Access Denied')]
                    | //span[contains(text(), 'This site can’t be reached')]
                    | //h1[contains(text(), 'Secure Connection Failed')]
                "), 15);
        }

        $this->saveResponse();

        if ($this->http->FindSingleNode("//span[contains(@class, 'welcomeLabel ')][starts-with(normalize-space(),'Welcome,')]")
            || ($this->attempt == 0) // debug
        ) {
            $this->logger->debug("[Current URL]: {$this->http->currentUrl()}");
            return $this->loginSuccessful();
        }

        if ($this->http->FindSingleNode("//span[contains(text(), 'This site can’t be reached')] | //h1[contains(text(), 'Secure Connection Failed')]")
            || $this->http->FindSingleNode("//h1[contains(text(), 'Access Denied')]")) {
            $this->markProxyAsInvalid();
            throw new CheckRetryNeededException(3, 0);
        }
        return $this->checkErrors();
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);
        $checker = $this->getAsia();

        $preLogin = false;
        $cookies = $this->driver->manage()->getCookies();
        foreach ($cookies as $cookie) {
            if ($cookie['name'] == 'cx_prelogin' && $cookie['value'] == 1) {
                $preLogin = true;
                break;
            }
        }

        if (!$preLogin) {
            $this->logger->error('Something went wrong.');
            return false;
        }

        $checker->http->RetryCount = 0;
        $checker->http->GetURL("https://api.cathaypacific.com/mpo-common-services/v3/profile", [
            'Accept' => 'application/json, text/plain, */*',
            'Origin' => 'https://www.cathaypacific.com'
        ]);
        $checker->http->RetryCount = 2;
        $response = $checker->http->JsonLog();

        if (isset($response->errors[0]->code) && $response->errors[0]->code == 'ERR_COMM_003') {
            return false;
        }

        if (isset($response->membershipNumber)) {
            return true;
        }

        return false;
    }

    public function Parse()
    {
        $checker = $this->getAsia();
        $host = $this->http->getCurrentHost();
        $this->logger->debug("host: $host");
        $checker->Parse();
        $this->SetBalance($checker->Balance);
        $this->Properties = $checker->Properties;
        $this->ErrorCode = $checker->ErrorCode;

        if ($this->ErrorCode != ACCOUNT_CHECKED) {
            $this->ErrorMessage = $checker->ErrorMessage;
            $this->DebugInfo = $checker->DebugInfo;
        }
    }

    public function ParseItineraries()
    {
        $this->http->GetURL('https://www.cathaypacific.com/mb/');
        sleep(10);
        $this->saveResponse();
        $checker = $this->getAsia();
        $checker->ParseItineraries();
        return [];
    }
}
