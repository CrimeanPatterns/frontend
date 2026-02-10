<?php

class TAccountCheckerSavemart extends TAccountChecker
{
     use SeleniumCheckerHelper;

    private string $swiftlyUserId;
    private string $chainId;
    private string $tsmcCardId;
    public $regionOptions = [
        ""                   => "Select your brand",
        "savemart"           => "Save Mart",
        "luckysupermarkets"  => "Lucky Supermarkets"
    ];

    private $swiftlyDomain = 'sm';

    public function TuneFormFields(&$fields, $values = null)
    {
        parent::TuneFormFields($fields);
        $fields["Login2"]["Options"] = $this->regionOptions;
    }

    protected function checkRegionSelection($region)
    {
        if (empty($region) || !in_array($region, array_flip($this->regionOptions))) {
            $region = 'savemart';
        }

        if($region == 'savemart') {
            $this->swiftlyDomain = 'sm';
        } else {
            $this->swiftlyDomain = 'lu';
        }

        return $region;
    }

    public static function FormatBalance($fields, $properties)
    {
        if (isset($properties['SubAccountCode']) && strstr($properties['SubAccountCode'], 'AvailableCashback')) {
            return formatFullBalance($fields['Balance'], $fields['ProviderCode'], "$%0.2f");
        }

        return parent::FormatBalance($fields, $properties);
    }

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
        $this->AccountFields['Login2'] = $this->checkRegionSelection($this->AccountFields['Login2']);
        $this->logger->notice('Region => ' . $this->AccountFields['Login2']);

        $this->UseSelenium();
        $this->http->saveScreenshots = true;

        $this->useChromePuppeteer();
    }

    public function LoadLoginForm()
    {
        $this->http->removeCookies();
        $this->http->GetURL('https://'.$this->AccountFields['Login2'].'.com/account/rewards');
        $loginBtn = $this->waitForElement(WebDriverBy::xpath('//button[@aria-label="Select to sign in or sign up"]'), 10);
        $this->saveResponse();

        if (!$loginBtn) {
            return $this->checkErrors();
        }

        $loginBtn->click();

        $loginInput = $this->waitForElement(WebDriverBy::xpath('//input[@id = "email"]'), 10);
        $passwordInput = $this->waitForElement(WebDriverBy::xpath('//input[@id = "password"]'), 0);
        $button = $this->waitForElement(WebDriverBy::xpath('//button[contains(., "Sign In")]'), 0);
        $this->saveResponse();

        if (!$loginInput || !$passwordInput || !$button) {
            if ($this->loginSuccessful()) {
                return true;
            }

            return $this->checkErrors();
        }

        $this->logger->debug("set login");
        $loginInput->sendKeys($this->AccountFields['Login']);
        $this->logger->debug("set password");
        $passwordInput->sendKeys($this->AccountFields['Pass']);
        $this->logger->debug("set remember me");
        try {
            $this->driver->executeScript('document.querySelector(\'input[id = "rememberMe"]\').checked = true;');
        } catch (UnexpectedJavascriptException $e) {
            $this->logger->error("Exception: ".$e->getMessage(), ['HtmlEncode' => true]);
        }

        $this->saveResponse();
        $this->logger->debug("sign in");
        $button->click();

        return true;
    }

    public function Login()
    {
        $this->waitForElement(WebDriverBy::xpath('//div[contains(@class, "text-danger")] | //div[contains(text(), "Points Balance")] | //a[@href="/account"]//p[@data-size]'), 10);
        $this->saveResponse();
        $this->logger->debug("[Current URL]: {$this->http->currentUrl()}");

        if (
            $this->http->currentUrl() == 'https://'.$this->AccountFields['Login2'].'.com/wp/rewards-guest'
            || $this->loginSuccessful()
        ) {
            return true;
        }

        if ($message = $response->message ?? null) {
            $this->logger->error("[Error]: {$message}");

            if ($message == "Invalid Credentials") {
                throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
            }

            $this->DebugInfo = $message;

            return false;
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        if ($this->http->currentUrl() != 'https://'.$this->AccountFields['Login2'].'.com/account/rewards') {
            $this->http->GetURL('https://'.$this->AccountFields['Login2'].'.com/account/rewards');
        }

        $this->waitForElement(WebDriverBy::xpath('//div[contains(text(), "Points Balance")]/preceding-sibling::p[text() != "0"]'), 10);
        $this->waitForElement(WebDriverBy::xpath('//p[contains(text(), "No rewards added yet.")]'), 15);// TODO
        sleep(2);
        $this->saveResponse();

        // Name
        $this->SetProperty('Name', beautifulName($this->http->FindSingleNode('//p[contains(text(), "Hi ")]', null, true, "/Hi ([^!]+)/")));
        // Loyalty ID
        $this->SetProperty('Number', $this->http->FindSingleNode('//div[contains(@aria-label, "Loyalty ID: ")]/p[last()]'));
        /*
        // Available Cashback
        $this->AddSubAccount([
            'Code'           => ucfirst($this->AccountFields['Login2']) . 'AvailableCashback' . $userData->message->InitialCardID,
            'DisplayName'    => 'Available Cashback',
            'Balance'        => $this->http->FindPreg('/\d+/', false, $wallet->cashbackDisplay),
        ]);
        */
        // Available points
        $this->SetBalance($this->http->FindSingleNode('//div[contains(text(), "Points Balance")]/preceding-sibling::p'));

        if (!$this->http->FindSingleNode('//p[contains(text(), "No rewards added yet.")]')) {
            $this->sendNotification("rewards were found");
        }

        /*
        $summaryPoints = $rewards->data->loyaltySummary->summaryPoints ?? null;

        if(isset($summaryPoints)) {
            if(count($summaryPoints) > 1) {
                $this->sendNotification("refs #17975 - need to check exp date // IZ");
            }

            if(count($summaryPoints) == 1 && isset($summaryPoints[0]->points, $summaryPoints[0]->expiresOn)) {
                // Expiring Balance
                $this->SetProperty("ExpiringBalance", $summaryPoints[0]->points);
                // Expiration Balance
                $this->SetExpirationDate(strtotime($summaryPoints[0]->expiresOn));
            }
        }

        $issuedRewards = $rewards->data->loyaltySummary->issuedRewards ?? [];

        foreach($issuedRewards as $reward) {

            if(isset($reward->reward->pointCost) && $reward->reward->pointCost !== 0) {
                $this->sendNotification("refs #23078 -  need to check subaccount balance // IZ");
                continue;
            }

            if(strtotime($reward->expiryDateTime) < time()) {
                continue;
            }

            $this->AddSubAccount([
                'Code' => ucfirst($this->AccountFields['Login2']) . $reward->reward->rewardId,
                'DisplayName' => $reward->reward->description,
                'Balance' => NULL,
                'ExpirationDate' => strtotime($reward->expiryDateTime)
            ]);
        }
        */
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);

        if ($this->http->FindSingleNode('//div[contains(text(), "Points Balance")] | //a[@href="/account"]//p[@data-size]')) {
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
