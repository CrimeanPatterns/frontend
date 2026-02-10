<?php

namespace AwardWallet\Engine\barclaycard;

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
use SeleniumFinderRequest;
use SessionNotCreatedException;
use StaleElementReferenceException;
use TAccountChecker;
use TAccountCheckerAsia;
use TAccountCheckerBarclaycard;
use TimeOutException;
use UnexpectedAlertOpenException;
use UnexpectedJavascriptException;
use UnknownServerException;
use WebDriverBy;
use WebDriverException;

class TAccountCheckerBarclaycardSelenium extends TAccountChecker
{
    use SeleniumCheckerHelper;
    use ProxyList;

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
        $this->UseSelenium();
        $this->setProxyGoProxies();
        $this->useGoogleChrome(SeleniumFinderRequest::CHROME_94);
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
        $this->http->GetURL("https://www.barclaycardus.com/servicing/home?secureLogin=");

        $loginInput = $this->waitForElement(WebDriverBy::xpath('//input[@id = "username"]'), 10);
        // password
        $passwordInput = $this->waitForElement(WebDriverBy::xpath('//input[@id = "password"]'), 0);
        // Sign In
        $button = $this->waitForElement(WebDriverBy::xpath('//button[@id = "loginButton"]'), 0);
        // save page to logs
        $this->saveResponse();

        if (!$loginInput || !$passwordInput || !$button) {
            $this->logger->error("Something went wrong");
            // This site can’t be reached
            if ($this->http->FindSingleNode("//*[self::h1 or self::span][contains(text(), 'This site can’t be reached')]")) {
                $this->DebugInfo = "This site can’t be reached";

                throw new CheckRetryNeededException(5, 3);
            }

            return $this->checkErrors();
        }

        $loginInput->click();
        $loginInput->sendKeys($this->AccountFields['Login']);
        $passwordInput->click();
        $passwordInput->sendKeys($this->AccountFields['Pass']);
        $button->click();
        $this->overlayWorkaround($this, '//button[@id = "loginButton"]');

        $this->logger->notice("[Last selenium URL]: {$this->http->currentUrl()}");
        return true;
    }

    private function overlayWorkaround($selenium, $loginBtnXpath)
    {
        $this->logger->notice(__METHOD__);

        if ($selenium->waitForElement(WebDriverBy::xpath("//div[@id = 'sec-if-container']"), 7)) {
            $this->savePageToLogs($selenium);
            // "I'm not a robot"
            if ($iframe = $selenium->waitForElement(WebDriverBy::xpath("//iframe[@id = 'sec-cpt-if']"), 0)) {
                $selenium->driver->switchTo()->frame($iframe);

                $selenium->waitForElement(WebDriverBy::xpath("//input[@id = 'robot-checkbox']"), 5);
                $this->savePageToLogs($selenium);
                $this->logger->debug("click by checkbox");
                $this->savePageToLogs($selenium);
//                $selenium->driver->executeScript('document.querySelector(\'#sec-cpt-if\').contentWindow.document.querySelector(\'#robot-checkbox\').click()');
                $selenium->driver->executeScript('document.querySelector(\'#robot-checkbox\').click()');
                sleep(2);
                $this->savePageToLogs($selenium);
                $this->logger->debug("click by 'Proceed' btn");
                $btn = $selenium->waitForElement(WebDriverBy::xpath('//div[@id = "progress-button"]'), 2);
                $btn->click();
//                $selenium->driver->executeScript('document.querySelector(\'#sec-cpt-if\').contentWindow.document.querySelector(\'#proceed-button\').click()');
                sleep(2);
                $selenium->driver->switchTo()->defaultContent();
                $this->savePageToLogs($selenium);
            }// if ($iframe = $selenium->waitForElement(WebDriverBy::xpath("//iframe[@id = 'sec-cpt-if']"), 0))

            /*
            $selenium->waitFor(function () use ($selenium) {
                return !$selenium->waitForElement(WebDriverBy::xpath('//*[@id = "sec-if-container" or @id = "sec-text-if"]'), 0);
            }, 80);
            $this->savePageToLogs($selenium);
            */

            if ($btn = $selenium->waitForElement(WebDriverBy::xpath($loginBtnXpath), 3)) {
                $btn->click();
            }
        }
    }

    public function processSecurityCheckpoint()
    {
        $this->logger->notice(__METHOD__);
        $answer = $this->Answers[$this->Question];
        unset($this->Answers[$this->Question]);
        $this->logger->debug("Question to -> $this->Question=$answer");
        $otpInput = $this->waitForElement(WebDriverBy::xpath('//input[@id="otpPasscode"]'), 5);
        if (!$otpInput) {
            $this->saveResponse();

            return false;
        }
        $this->logger->debug("entering code...");
        $otpInput->click();
        $otpInput->sendKeys($answer);
        $this->saveResponse();

        $button = $this->waitForElement(WebDriverBy::xpath('//button[@id="otpEntryForm.btnContinue"]'), 1);

        if (!$button) {
            return false;
        }

        $button->click();
        $message = $this->waitForElement(WebDriverBy::xpath('
            //button[@id = "logoutButton"]
            | //a[contains(@href,"/servicing/logout?")]
            | //p[contains(text(),"Please enter the SecurPass™ code that we sent to you.")]
        '), 40);
        if (!$message) {
            $this->saveResponse();
            return false;
        }
        if (
            strstr($message->getText(), 'Please enter the SecurPass™ code that we sent to you.')
        ) {
            $this->holdSession();
            $this->AskQuestion($this->Question, $message->getText(), "question");

            return false;
        }
        sleep(5);
        $this->logger->debug("success");
        $this->logger->debug("[Current URL]: {$this->http->currentUrl()}");
        if (!strstr($this->http->currentUrl(), 'https://www.barclaycardus.com/servicing/accountSummaryOnLogin?__fsk=')) {
            $this->logger->notice("Try to open dashboard");
            $this->http->GetURL("https://www.barclaycardus.com/servicing/accountSummary");
            $this->logger->notice("[Current URL]: {$this->http->currentUrl()}");

            if ($skipUpdate = $this->waitForElement(WebDriverBy::xpath("//p[@class = 'b-greeting']"), 5)) {
                // save page to logs
                $this->saveResponse();
                $skipUpdate->click();
                sleep(5);
            }
        }

        $this->saveResponse();
        return true;
    }

    public function ProcessStep($step)
    {
        $this->logger->notice(__METHOD__);
        $this->logger->debug("Current URL: " . $this->http->currentUrl());

        if ($this->isNewSession()) {
            return $this->LoadLoginForm() && $this->Login();
        }

        if ($step == 'question') {
            if ($this->processSecurityCheckpoint()) {
                $this->saveResponse();
                return true;
            }
        }

        return false;
    }

    protected function getBarclaycard()
    {
        $this->logger->notice(__METHOD__);
        if (!isset($this->barclaycard)) {
            $this->barclaycard = new TAccountCheckerBarclaycard();
            $this->barclaycard->http = new HttpBrowser("none", new CurlDriver());
            $this->barclaycard->http->setProxyParams($this->http->getProxyParams());

            $this->http->brotherBrowser($this->barclaycard->http);
            $this->barclaycard->AccountFields = $this->AccountFields;
            $this->barclaycard->http->SetBody($this->http->Response['body']);
            $this->barclaycard->itinerariesMaster = $this->itinerariesMaster;
            $this->barclaycard->HistoryStartDate = $this->HistoryStartDate;
            $this->barclaycard->historyStartDates = $this->historyStartDates;
            $this->barclaycard->http->LogHeaders = $this->http->LogHeaders;
            $this->barclaycard->ParseIts = $this->ParseIts;
            $this->barclaycard->ParsePastIts = $this->ParsePastIts;
            $this->barclaycard->WantHistory = $this->WantHistory;
            $this->barclaycard->WantFiles = $this->WantFiles;
            $this->barclaycard->strictHistoryStartDate = $this->strictHistoryStartDate;

            $this->barclaycard->globalLogger = $this->globalLogger;
            $this->barclaycard->logger = $this->logger;
            $this->barclaycard->onTimeLimitIncreased = $this->onTimeLimitIncreased;
        }

        $cookies = $this->driver->manage()->getCookies();
        $this->logger->debug("set cookies");

        foreach ($cookies as $cookie) {
            $this->barclaycard->http->setCookie($cookie['name'], $cookie['value'], $cookie['domain'], $cookie['path'], $cookie['expiry'] ?? null);
        }

        return $this->barclaycard;
    }

    public function checkErrors()
    {
        return false;
    }


    public function Login()
    {
        $this->saveResponse();
        $this->waitForElement(WebDriverBy::xpath('
            //button[@id = "logoutButton"] | //a[contains(@href,"/servicing/logout?")]
            | //p[contains(text(), "Select an email address.")]
            | //p[contains(text(), "Email me at")]
            | //p[contains(text(), "Text me at")]
            | //div[@class="error-container error"]'), 30);

        $selectMethod = $this->waitForElement(WebDriverBy::xpath('
            (//button[@id = "logoutButton"] | //a[contains(@href,"/servicing/logout?")]
            | //p[contains(text(), "Select an email address.")]
            | //p[contains(text(), "Email me at")]
            | //div[@class="error-container error"])[1]'), 0);

        // Text message
        if (!isset($selectMethod)) {
            $selectMethod = $this->waitForElement(WebDriverBy::xpath('//p[contains(text(), "Text me at")]'), 0);
        }

        if (isset($selectMethod)) {
            if (stristr($selectMethod->getAttribute('id'), 'logoutButton')
            || stristr($selectMethod->getAttribute('class'), '/servicing/logout?')
            || stristr($selectMethod->getAttribute('href'), '/servicing/logout?')) {
                return true;
            } // Select an email address.
            elseif (stristr($selectMethod->getText(), 'Select an email address.')) {
                $this->waitForElement(WebDriverBy::xpath(
                    "//p[contains(text(), 'Select an email address.')]/preceding-sibling::label"), 0)
                    ->click();
                $point = $this->driver->executeScript(/** @lang JavaScript */ "
                const emailOption = document.querySelector('#emailOption');
                const value = emailOption.options[1].value;
                emailOption.value = value;
                emailOption.options[emailOption.selectedIndex].defaultSelected = true;
                document.querySelector('#cv').value = value;
                return emailOption.options[1].textContent;
            ");
            } // Email me at
            elseif (stristr($selectMethod->getText(), 'Email me at')) {
                $this->waitForElement(WebDriverBy::xpath(
                    "//p[contains(text(), 'Email me at')]/preceding-sibling::label"), 0)
                    ->click();
                $this->driver->executeScript(/** @lang JavaScript */ "
                const emailOption = document.querySelector('#emailOption');
                document.querySelector('#cv').value = emailOption.value;
                ");
                $point = $this->http->FindPreg('/Email me at (.+)/', false, $selectMethod->getText());
            } // Text message
            elseif (stristr($selectMethod->getText(), 'Text me at')) {
                $this->waitForElement(WebDriverBy::xpath(
                    "//p[contains(text(), 'Text me at')]/preceding-sibling::label"), 0)
                    ->click();
                $this->driver->executeScript(/** @lang JavaScript */ "
                const textOption = document.querySelector('#textOption');
                document.querySelector('#cv').value = textOption.value;
                ");
                $point = $this->http->FindPreg('/Text me at (.+)/', false, $selectMethod->getText());
                $method = 'sms';
            }

            if (isset($point)) {
                if (isset($method)) {
                    $question = "Please enter SecurPass™ code which was sent to the following phone number: {$point}. Please note: This SecurPass™ code can only be used once and it expires within 10 minutes.";
                } else {
                    $question = "Please enter SecurPass™ code which was sent to the following email address: {$point}. Please note: This SecurPass™ code can only be used once and it expires within 10 minutes.";
                }

                $button = $this->waitForElement(WebDriverBy::xpath('//button[@id="otpDecision.btnContinue"]'), 0);
                if ($button) {
                    $button->click();
                }
                $this->saveResponse();
                $this->holdSession();
                $this->AskQuestion($question, null, "question");

                return false;
            }
        }

        // Your username or password is incorrect. Please try again.
        if ($message = $this->http->FindSingleNode("//span[contains(text(), 'Your username or password is incorrect. Please try again.')]")) {
            throw new CheckRetryNeededException(2, 7, $message, ACCOUNT_INVALID_PASSWORD); // refs #14720 wrong error
        }
        $this->CheckError($this->http->FindSingleNode("//div[@class = 'errorIndicatorBlock' and contains(text(), 'we have locked online access')]"), ACCOUNT_LOCKOUT);
        $this->CheckError($this->http->FindSingleNode("//div[contains(text(), 're sorry, but for security reasons online access to your account is unavailable.')]"), ACCOUNT_LOCKOUT);
        $this->CheckError($this->http->FindPreg("/(For security reasons\, we have locked online access to your account\.)/ims"), ACCOUNT_LOCKOUT);
        $this->CheckError($this->http->FindPreg("/<h1>(Please reset your password)<\/h1>/ims"));
        // Your username must be 6-3(?:0|2) characters
        $this->CheckError($this->http->FindPreg("/(Your username must be 6-3(?:0|2) characters[^<\[]+)/ims"));
        //# There has been a problem processing your login, please try again in a few minutes.
        $this->CheckError($this->http->FindPreg("/(There has been a problem processing your login,\s*please try again in a few minutes\.)/ims"), ACCOUNT_PROVIDER_ERROR);
        // We apologize for the inconvenience, but we could not complete your request. Please try again.
        $this->CheckError($this->http->FindPreg("/(We apologize for the inconvenience, but we could not complete your request\.\s*Please try again\.)/ims"), ACCOUNT_PROVIDER_ERROR);
        // Online access enrollment not complete.
        $this->CheckError($this->http->FindPreg("/(Online access enrollment not complete\.)/ims"), ACCOUNT_PROVIDER_ERROR);
        // Our website is currently unavailable and we are working to resolve this as quickly as possible.
        $this->CheckError($this->http->FindPreg("/enableAlert: true,\s*alertHeader:\s*'(Our website is currently unavailable and we are working to resolve this as quickly as possible\. We apologize for any inconvenience\. Please check again later\.)\s*',/ims"), ACCOUNT_PROVIDER_ERROR);

        if ($this->waitForElement(WebDriverBy::xpath("//h3[contains(text(), 'For your security, please answer the question(s) below.')]"), 0)) {
            throw new CheckException("To update this Barclaycard account you need to enable verification via SecurPass in your profile.", ACCOUNT_PROVIDER_ERROR);
        }

        if ($skipUpdate = $this->waitForElement(WebDriverBy::xpath("//a[@id = 'remindLaterRemoveAu' or @id = 'remindLaterProfile']"), 5)) {
            // save page to logs
            $this->saveResponse();
            $skipUpdate->click();
            sleep(5);
        }
        $this->saveResponse();
        $this->logger->notice("[Current URL]: {$this->http->currentUrl()}");

        if (strstr($this->http->currentUrl(), 'https://www.barclaycardus.com/servicing/accountSummaryOnLogin?__fsk=')) {
            $this->logger->notice("Try to open dashboard");
            $this->http->GetURL("https://www.barclaycardus.com/servicing/accountSummary");
            $this->logger->notice("[Current URL]: {$this->http->currentUrl()}");

            if ($skipUpdate = $this->waitForElement(WebDriverBy::xpath("//p[@class = 'b-greeting']"), 5)) {
                // save page to logs
                $this->saveResponse();
                $skipUpdate->click();
                sleep(5);
            }
        }
        return $this->checkErrors();
    }

    public function Parse()
    {
        $checker = $this->getBarclaycard();

        $checker->Parse();
        $this->SetBalance($checker->Balance);
        $this->Properties = $checker->Properties;
        $this->ErrorCode = $checker->ErrorCode;
        $this->logger = $checker->logger;

        if ($this->ErrorCode != ACCOUNT_CHECKED) {
            $this->ErrorMessage = $checker->ErrorMessage;
            $this->DebugInfo = $checker->DebugInfo;
        }

        $this->http->GetURL('https://www.barclaycardus.com/servicing/nextgen-banking');
        sleep(10);
        $this->saveResponse();
        if ($this->http->FindSingleNode('//*[contains(.,"Credit Cards")]'))
            $this->sendNotification('all cards //MI');
    }


}
