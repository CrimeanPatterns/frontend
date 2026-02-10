<?php

class TAccountCheckerFirsthotels extends TAccountChecker
{
    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
        $this->http->setDefaultHeader("Accept-Encoding", "gzip, deflate, br");
    }

    public function IsLoggedIn()
    {
        $this->http->RetryCount = 0;
        $this->http->GetURL("https://www.firsthotels.com/first-member/my-page/", [], 30);
        $this->http->RetryCount = 2;

        if ($this->loginSuccessful()) {
            return true;
        }

        return false;
    }

    public function LoadLoginForm()
    {
        if (filter_var($this->AccountFields['Login'], FILTER_VALIDATE_EMAIL) === false) {
            throw new CheckException('Please enter a valid email address', ACCOUNT_INVALID_PASSWORD);
        }

        $this->http->removeCookies();
        $this->http->GetURL("https://first-member.firsthotels.com/members");

        if (!$this->http->ParseForm("login-form")) {
            return $this->checkErrors();
        }

        $this->http->SetInputValue('username', $this->AccountFields['Login']);
        $this->http->SetInputValue('password', $this->AccountFields['Pass']);
        $this->http->SetInputValue('submit', "");

        return true;
    }

    public function checkErrors()
    {
        $this->logger->notice(__METHOD__);
        /*
        // Page could not be loaded
        if ($message = $this->http->FindPreg("#(Page could not be loaded)#ims"))
            throw new CheckException(self::PROVIDER_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
        // The elephant stays at room 404
        if ($message = $this->http->FindSingleNode('//h2[contains(text(), "The elephant stays at room 404")]'))
            throw new CheckException(self::PROVIDER_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
        */

        return false;
    }

    public function Login()
    {
        if (!$this->http->PostForm()) {
            return $this->checkErrors();
        }

        if ($location = $this->http->FindPreg("/window.location = \"([^\"]+)/")) {
            $this->http->NormalizeURL($location);
            $this->http->GetURL($location);
        }
        // Access is allowed
        if ($this->loginSuccessful()) {
            return true;
        }

        if ($message = $this->http->FindSingleNode('//ul[contains(@class, "error")]/li')) {
            $this->logger->error("[Error]: {$message}");

            if (
                strstr($message, "Please verify that you have properly entered your current information")
            ) {
                throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
            }

//            if (
//                strstr($message, "Internal server error")
//            ) {
//                throw new CheckException($message, ACCOUNT_PROVIDER_ERROR);
//            }

            $this->DebugInfo = $message;

            return false;
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        // Balance - FirstCoins
        $this->SetBalance($this->http->FindSingleNode('//div[contains(@class, "member-overview")]/div/div[contains(., "You have")]//strong[@class = "h3"]'));
        // Name
        $name = $this->http->FindPreg("/name=([^=&]+)/", false, $this->http->getCookieByName("FirstMemberUser-TC"));

        if (!$name) {
            // Good ..., [Name]!
            $name = $this->http->FindSingleNode('//div[contains(@class, "member-overview")]/div/div[contains(., "You have")]/h1', null, true, "/,\s*([^!]+)/");
        }
        $this->SetProperty("Name", beautifulName($name));
        // Your current membership level
        $this->SetProperty("MemberLevel", $this->http->FindSingleNode('//span[contains(text(), "current membership level:")]/strong'));
        // ... nights during the qualifying period
        $this->SetProperty("Nights", $this->http->FindSingleNode('//span[contains(text(), "nights during the")]', null, true, "/(\d+)\s*nights during the/"));
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);

        if ($this->http->FindNodes('//a[contains(@href, "log-out")]')
            && $this->http->FindPreg('#/first-member/my\-page/#', false, $this->http->currentUrl())) {
            return true;
        }

        return false;
    }
}
