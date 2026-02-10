<?php

use AwardWallet\Common\Selenium\FingerprintFactory;
use AwardWallet\Common\Selenium\FingerprintRequest;
use AwardWallet\Engine\ProxyList;

class TAccountCheckerQdobaSelenium extends TAccountChecker
{
    use ProxyList;
    use SeleniumCheckerHelper;

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->UseSelenium();

        $this->useChromePuppeteer();
        $this->seleniumOptions->fingerprintOptions = ['devices' => ['desktop'], 'operatingSystems' => ['macos']];
        $this->seleniumOptions->addHideSeleniumExtension = false;
        $this->seleniumOptions->userAgent = null;

        $this->KeepState = true;
        $this->usePacFile(false);
        $this->http->saveScreenshots = true;
    }

    /*
    public function IsLoggedIn()
    {
        $this->http->GetURL('https://order.qdoba.com/order/rewards');

        $isLoggedIn = $this->waitForElement(WebDriverBy::xpath("//h1[contains(@class,'recent__header__greeting')]"),
            10);
        $this->saveResponse();
        if ($isLoggedIn) {
            return true;
        }
        return false;
    }
    */

    public function LoadLoginForm()
    {
        $this->http->removeCookies();
        $this->http->GetURL('https://order.qdoba.com/order/rewards');
        $this->waitForElement(WebDriverBy::xpath("//input[@value = 'Verify you are human'] 
            | //div[@id = 'turnstile-wrapper']//iframe 
            | //div[contains(@class, 'cf-turnstile-wrapper')] 
            | //button[span[contains(text(),'Log In')]]
            | //div[@class='px-captcha-error-message']"), 10);
        $this->saveResponse();

        if ($this->clickCloudFlareCheckboxByMouse($this)) {
            sleep(5);
            $this->saveResponse();
        }

        if ($this->http->FindSingleNode("//span[contains(text(), 'Your connection was interrupted')]")) {
            throw new CheckRetryNeededException(3, 0);
        }

        if ($loginBtn = $this->waitForElement(WebDriverBy::xpath("//button[span[contains(text(),'Log In')]]"), 0)) {
            $loginBtn->click();

            if ($loginBtnTwo = $this->waitForElement(WebDriverBy::xpath('//button[contains(@class, "app-button") and .//*[@aria-label="Log In"]]'), 5)) {
                $loginBtnTwo->click();
            }
        } else {
            $this->http->GetURL('https://nomnom-prod-migration.qdoba.com/api/profiles/login?redirectUri=https://order.qdoba.com/oauth/callback');
        }

        $this->waitForElement(WebDriverBy::xpath("//form[contains(@method,'POST')]//input[@id = 'username'] | //h1[contains(text(), 'Sorry, you have been blocked')]"), 10);

        $loginInput = $this->waitForElement(WebDriverBy::xpath("//input[@id='username']"), 0);
        $passwordInput = $this->waitForElement(WebDriverBy::xpath("//input[@id='password']"), 0);
        $button = $this->waitForElement(WebDriverBy::xpath("//button[@type='submit']"), 0);

        if ($agreeBtn = $this->waitForElement(WebDriverBy::xpath("//button[contains(text(), 'I Agree') or contains(text(), 'OK, I understand')]"), 0)) {
            $agreeBtn->click();
        }

        $this->saveResponse();

        if (!$loginInput || !$passwordInput || !$button) {
            $this->logger->debug("[Current URL]: {$this->http->currentUrl()}");

            return false;
        }

        $loginInput->sendKeys($this->AccountFields['Login']);

        // canes
        if (empty($this->AccountFields['Pass'])) {
            throw new CheckException("The username could not be found or the password you entered was incorrect. Please try again.",
                ACCOUNT_PROVIDER_ERROR);
        }

        $passwordInput->sendKeys($this->AccountFields['Pass']);
        $this->logger->debug("click by btn");
        $button->click();
        $this->saveResponse();

        return true;
    }

    public function checkErrors()
    {
        $this->logger->notice(__METHOD__);

        return false;
    }

    public function Login()
    {
        $this->waitForElement(WebDriverBy::xpath("
            //h1[contains(@class,'recent__header__greeting')]
            | //button[contains(@class,'c-navbar__buttons__auth')]
            | //span[contains(@class,'ulp-input-error-message') and normalize-space()!='']
        "), 10);
        $isLoggedIn = $this->waitForElement(WebDriverBy::xpath("//h1[contains(@class,'recent__header__greeting')] | //button[contains(@class,'c-navbar__buttons__auth')]"), 0);
        $this->saveResponse();

        if ($isLoggedIn) {
            return true;
        }

        $error = $this->waitForElement(WebDriverBy::xpath("//span[contains(@class,'ulp-input-error-message') and normalize-space()!='']"), 0);

        if ($error) {
            $message = $error->getText();
            $this->logger->error("[Error]: {$message}");

            if (stristr($message, 'Invalid credentials provided.')
                || stristr($message, 'Your email address and/or password are incorrect. Please try again.')
            ) {
                throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
            }

            $this->DebugInfo = $message;
        }

        if (
            $this->waitForElement(WebDriverBy::xpath("//p[contains(text(),'ve sent an email with your code to')]"), 0)
            && $this->processSecurityCheckpoint()
        ) {
            return false;
        }

        return $this->checkErrors();
    }

    public function processSecurityCheckpoint()
    {
        $this->logger->notice(__METHOD__);
        $this->logger->info('Security Question', ['Header' => 3]);
        $q = $this->waitForElement(WebDriverBy::xpath("//p[contains(text(),'ve sent an email with your code to')]"), 0);

        if (!$q) {
            $this->logger->error("Question not found");
            $this->saveResponse();

            return false;
        }

        $question = $q->getText();

        if (!isset($this->Answers[$question])) {
            // prevent code spam    // refs #6042
            if ($this->isBackgroundCheck() && !$this->getWaitForOtc()) {
                $this->Cancel();
            }

            $this->sendNotification('check 2fa // MI');
            $this->holdSession();
            $this->AskQuestion($question, null, 'Question');

            return true;
        }

        $answer = $this->Answers[$question];
        unset($this->Answers[$question]);

        $codeInput = $this->waitForElement(WebDriverBy::xpath("//input[@name='code']"), 0);
        $this->saveResponse();

        if (!isset($codeInput)) {
            $this->saveResponse();

            return false;
        }

        $codeInput->clear();
        $codeInput->sendKeys($answer);
        $btn = $this->waitForElement(WebDriverBy::xpath("//button[@name='action']"), 0);

        if (!$btn) {
            $this->saveResponse();
            return false;
        }

        $btn->click();
        sleep(1);

        $this->waitForElement(WebDriverBy::xpath("
            //h1[contains(@class,'recent__header__greeting')]
            | //span[contains(@class,'ulp-input-error-message') and normalize-space()!='']
        "), 7);
        $error = $this->waitForElement(WebDriverBy::xpath("//span[contains(@class,'ulp-input-error-message') and normalize-space()!='']"), 0);
        $this->saveResponse();

        if ($error) {
            $message = $error->getText();
            $this->logger->error("[Error]: {$message}");

            if (stristr($message, 'The code you entered is invalid')) {
                $this->holdSession();
                $this->AskQuestion($question, $error->getText(), 'Question');

                return false;
            }

            $this->DebugInfo = $message;
        }

        return true;
    }

    public function ProcessStep($step) {
        $this->logger->debug("Current URL: ".$this->http->currentUrl());

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
        // 65/125 points earned toward your next free entrée!
        $balance = $this->waitForElement(WebDriverBy::xpath("//div[contains(@class,'recent__header-point-details')]/p/span"), 0);
        $this->saveResponse();
        if ($balance) {
            $this->SetBalance($this->http->FindPreg('#(\d+)/\d+ points#', false, $balance->getText()));
        }
        // Status
        $status = $this->waitForElement(WebDriverBy::xpath("//div[contains(@class,'recent__header-personalized')]/h1/following-sibling::div/p"), 0);
        if ($status) {
            $this->SetProperty("Tier", $status->getText());
        } elseif ($this->http->FindSingleNode('//p[contains(text(), "toward Gold Status level")]')) {
            $this->SetProperty("Tier", "Foodie");
        }
        // 0/12 visits toward 'Chef' level
        $visits = $this->waitForElement(WebDriverBy::xpath("//div[contains(@class,'recent__header-point-details')]/p[contains(text(),'visits toward')]"), 0);
        if ($visits) {
            $this->SetProperty("AnnualVisits", $this->http->FindPreg('#(\d+)/\d+ visits#', false, $visits->getText()));
        }

        $this->http->GetURL('https://order.qdoba.com/account/settings');
        $name = $this->waitForElement(WebDriverBy::xpath("//div[contains(@class,'settings__contact-info-filled')]/div/p[@class='name']"), 10);
        $this->saveResponse();
        $this->SetProperty("Name", beautifulName($name->getText()));
    }

}
