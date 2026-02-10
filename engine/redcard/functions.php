<?php

use AwardWallet\Engine\redcard\QuestionAnalyzer;

class TAccountCheckerRedcard extends TAccountChecker
{
    use SeleniumCheckerHelper;

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;

        $this->UseSelenium();
        $this->http->saveScreenshots = true;

        $this->useFirefoxPlaywright();
        $this->seleniumOptions->addHideSeleniumExtension = false;
        $this->seleniumOptions->userAgent = null;
    }

    public function LoadLoginForm()
    {
        $this->Answers = [];

        $this->http->removeCookies();
        $this->http->GetURL("https://rcam.target.com/?");


        $login = $this->waitForElement(WebDriverBy::xpath('//input[@name="username"]'), 15);
        $password = $this->waitForElement(WebDriverBy::xpath('//input[@name="password"]'), 0);

        if (!$login || !$password) {
            $this->saveResponse();

            return $this->checkErrors();
        }

        $login->click();
        $login->sendKeys($this->AccountFields['Login']);
        $password->click();
        $password->sendKeys($this->AccountFields['Pass']);

        $btn = $this->waitForElement(WebDriverBy::xpath('//button[@id = "login" and not(contains(@class, "disabled"))]'), 5);
        $this->saveResponse();

        if (!$btn) {
            return $this->checkErrors();
        }

        $btn->click();

        return true;
    }

    private function checkErrors()
    {
        $this->logger->notice(__METHOD__);

        return false;
    }

    public function Login()
    {
        $this->waitForElement(WebDriverBy::xpath('//h1[contains(text(), "One Time Passcode")] 
        | //div[@id = "loginError" and normalize-space(.) != ""] 
        | //a[@class="log_out"]'), 15);
        $this->saveResponse();

        if ($email = $this->waitForElement(WebDriverBy::xpath('//label[contains(text(), "Email")]'), 0)) {
            $email->click();
            $this->saveResponse();

            if ($rememberMe = $this->waitForElement(WebDriverBy::xpath('//input[@name = "rememberMe"]'), 0)) {
                $rememberMe->click();
            }

            $btn = $this->waitForElement(WebDriverBy::xpath('//button[not(contains(@class, "disabled"))]'), 0);
            $this->saveResponse();

            if (!$btn) {
                return $this->checkErrors();
            }

            if ($this->isBackgroundCheck() && !$this->getWaitForOtc()) {
                $this->Cancel();
            }

            $btn->click();

            if ($this->processQuestion()) {
                return false;
            }
        }

        if ($message = $this->http->FindSingleNode('//div[@id = "loginError" and normalize-space(.) != ""]')) {
            $this->logger->error("[Error]: {$message}");

            if (strstr($message, 'The username and/or password is incorrect.')) {
                throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
            }

            if (strstr($message, 'We\'re unable to log you into your account.')) {
                throw new CheckException($message, ACCOUNT_PROVIDER_ERROR);
            }

            $this->DebugInfo = $message;

            return false;
        }

        if ($this->waitForElement(WebDriverBy::xpath('//a[@class="log_out"]'), 0    , false)) {
            return true;
        }

        return false;
    }

    private function processQuestion()
    {
        $this->logger->notice(__METHOD__);
        $question = $this->waitForElement(WebDriverBy::xpath('//p[contains(text(), "We sent a temporary six-digit passcode to ")]'), 10);
        $passcode = $this->waitForElement(WebDriverBy::xpath('//input[@name = "passcode"]'), 0);
        $this->saveResponse();

        if (!$question || !$passcode) {
            return false;
        }

        if (!QuestionAnalyzer::isOtcQuestion($question->getText())) {
            $this->sendNotification("need to check QuestionAnalyzer");
        }

        if (!isset($this->Answers[$question->getText()])) {
            $this->holdSession();
            $this->AskQuestion($question->getText(), null, "Question");
            return true;
        }

        $answer = $this->Answers[$this->Question];
        unset($this->Answers[$this->Question]);

        $passcode->sendKeys($answer);

        $btn = $this->waitForElement(WebDriverBy::xpath('//button[not(contains(@class, "disabled")) and contains(., "Continue")]'), 5);
        $this->saveResponse();

        if (!$btn) {
            return $this->checkErrors();
        }

        $btn->click();

        if ($this->waitForElement(WebDriverBy::xpath('//a[@class="log_out"]'), 10, false)) {
            return true;
        }

        $error = $this->waitForElement(WebDriverBy::xpath('//rrrr[@id = "login" and not(contains(@class, "disabled"))]'), 0);
        $this->saveResponse();

        if ($error) {
            $message = $error->getText();
            $this->logger->error("[Error]: {$message}");
//            $this->holdSession();
            return false;
        }

        return true;
    }

    public function ProcessStep($step)
    {
        $this->logger->notice(__METHOD__);

        return $this->processQuestion();
    }

    public function Parse()
    {
        $this->http->GetURL("https://mytargetcirclecard.target.com/plprewards");

        if ($this->waitForElement(WebDriverBy::xpath('//h2[contains(text(), "We\'ve updated our Terms")]'), 10)) {
            $this->saveResponse();
            $this->throwAcceptTermsMessageException();
        }

        sleep(5);
        $this->saveResponse();

        // Name
        $this->SetProperty("Name", beautifulName(
            $this->http->FindSingleNode('//a[@class="acc_info"]/span[2]') ??
            $this->waitForElement(WebDriverBy::xpath('//a[@class="acc_info"]/span[2]'), 0)->getText()
            ?? null
        ));

        // Current Rewards Balance
        $this->SetBalance($this->http->FindSingleNode('//p[contains(text(),"Current Rewards Balance")]/following-sibling::p') ??
            $this->waitForElement(WebDriverBy::xpath('//p[contains(text(),"Current Rewards Balance")]/following-sibling::p'), 0)->getText()
            ?? null);

        return;

        $this->http->GetURL("https://rcam.target.com/Secure");
        $token = $this->http->FindPreg("/'X-Security-Token':\s*'([^']+)/");

        if (!$token) {
            return;
        }
        $this->http->setDefaultHeader("X-Security-Token", $token);
        $this->http->GetURL("https://rcam.target.com/api/User/current", $this->headers);
        $response = $this->http->JsonLog(null, 3, true);
        // Name
        $this->SetProperty("Name", beautifulName(ArrayVal($response, 'FirstName') . " " . ArrayVal($response, 'LastName')));

        $accounts = ArrayVal($response, 'Accounts', []);

        foreach ($accounts as $account) {
            if (ArrayVal($account, 'AssociatedAccountTypeCode') != 'MANUAL') {
                continue;
            }
            // Account Identification Number
            $this->SetProperty("Number", ArrayVal($account, 'ProxyNumber'));
            $cardholderSince = ArrayVal($account, 'CardholderSince', null);
            // Cardholder Since ...
            if ($cardholderSince && strtotime($cardholderSince)) {
                $this->SetProperty("CardholderSince", date("F Y", strtotime($cardholderSince)));
            }
        }// foreach ($accounts as $account)

        // REDcard Savings This Year
        $this->http->GetURL("https://rcam.target.com/api/YouSave/", $this->headers);
        // Balance -  REDcard Savings This Year
        $this->SetBalance($this->http->FindPreg("/^([\d.\-,\s]+)$/"));
    }
}
