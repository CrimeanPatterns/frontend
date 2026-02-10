<?php

use AwardWallet\Engine\ProxyList;

class TAccountCheckerColumbia extends TAccountChecker
{
    use SeleniumCheckerHelper;
    use ProxyList;
    private const REWARDS_PAGE_URL = 'https://www.columbia.com/on/demandware.store/Sites-Columbia_US-Site/en_US/Loyalty-Dashboard';

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;

        $this->UseSelenium();
        $this->http->saveScreenshots = true;

//        $this->setProxyGoProxies();

        $this->useFirefoxPlaywright();
//        $this->seleniumRequest->setOs(SeleniumFinderRequest::OS_MAC);
        $this->seleniumOptions->addHideSeleniumExtension = false;
        $this->seleniumOptions->userAgent = null;
    }

    public function LoadLoginForm()
    {
        if (filter_var($this->AccountFields['Login'], FILTER_VALIDATE_EMAIL) === false) {
            throw new CheckException('Please enter a valid email address.', ACCOUNT_INVALID_PASSWORD);
        }

        $this->http->removeCookies();
        $this->http->GetURL('https://www.columbia.com/on/demandware.store/Sites-Columbia_US-Site/en_US/Login-Show');

        $loginBtn = $this->waitForElement(WebDriverBy::xpath('//div[contains(@class, "siteheader__utility__inner")]//a[@href = "/?signin=true"]'), 15);

        if (!$loginBtn) {
            $this->saveResponse();
            return $this->checkErrors();
        }

        $loginBtn->click();
        $login = $this->waitForElement(WebDriverBy::xpath('//input[@name = "gatewayEmail"]'), 5);
        $this->saveResponse();

        if (!$login) {

            return $this->checkErrors();
        }

        $login->click();
        $login->sendKeys($this->AccountFields['Login']);

        $btn = $this->waitForElement(WebDriverBy::xpath('//button[contains(text(), "Continue") and not(contains(@class, "disabled"))]'), 5);
        $this->saveResponse();

        if (!$btn) {
            $this->checkProviderErrors();

            return $this->checkErrors();
        }

        $btn->click();
        $password = $this->waitForElement(WebDriverBy::xpath('//input[@name="password"]'), 5);
        $this->saveResponse();

        if (!$password) {
            $this->checkProviderErrors();

            return $this->checkErrors();
        }

        $password->click();
        $password->sendKeys($this->AccountFields['Pass']);
        $btn = $this->waitForElement(WebDriverBy::xpath('//button[not(contains(@class, "disabled"))]'), 5);
        $this->saveResponse();

        if (!$btn) {
            return $this->checkErrors();
        }

        $btn->click();

        return true;
    }

    private function checkProviderErrors()
    {
        $this->logger->notice(__METHOD__);

        if ($message = $this->http->FindSingleNode('//div[contains(@class, "alert-danger")]')) {
            $this->logger->error("[Error]: {$message}");

            if (strstr($message, 'Login unsuccessful. Please try to login again.')) {
                throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
            }

            $this->DebugInfo = $message;

            return false;
        }

        return false;
    }

    public function Login()
    {
        $this->waitForElement(WebDriverBy::xpath('//brrrrr'), 5);
        $this->saveResponse();

        $data = $this->http->JsonLog(null, 3, true);

        if ($data === null && $this->http->FindSingleNode('//div[@id = "px-captcha"]')) {
            $this->DebugInfo = 'Captcha Press and Hold';

            throw new CheckRetryNeededException();
        }

        if (isset($data['redirectUrl'])) {
            $url = $data['redirectUrl'];
            $this->http->NormalizeURL($url);
            $this->http->GetURL($url);
        }

        if ($this->loginSuccessful()) {
            return true;
        }
        // login or password incorrect
        if (isset($data['error'][0]) && strstr($data['error'][0], 'Invalid login or password') !== false) {
            $error = strip_tags($data['error'][0]);
            $this->logger->error("[Error]: {$error}");

            throw new CheckException($error, ACCOUNT_INVALID_PASSWORD);
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        if ($this->http->currentUrl() != self::REWARDS_PAGE_URL) {
            $this->http->GetURL(self::REWARDS_PAGE_URL);
        }
        // Balance - Rewards Balance
        $this->SetBalance($this->http->FindSingleNode('//div[contains(@class, "rewards__tracker-balance")] | //p[contains(text(), "Rewards Balance:")]/following-sibling::p'));
        // Name
        $this->SetProperty('Name', beautifulName($this->http->FindPreg("/\"firstName\":\s*\"([^\"]+)/") . " " . $this->http->FindPreg("/\"lastName\":\s*\"([^\"]+)/")));
        // Spend .... to earn your next $5 reward.
        $this->SetProperty("SpendToNextTier", $this->http->FindSingleNode('//div[contains(@class, "rewards__tracker-disclaimer")]/span'));
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);

        if ($this->http->FindPreg("/\"emailId\":\s*\"([^\"]+)/") && !strstr($this->http->currentUrl(), '/Login-Show')) {
            return true;
        }

        return false;
    }

    private function checkErrors()
    {
        $this->logger->notice(__METHOD__);

        return false;
    }
}
