<?php

use AwardWallet\Engine\ProxyList;
use AwardWallet\Engine\Settings;

class TAccountCheckerChilis extends TAccountChecker
{
    use ProxyList;

    private const REWARDS_PAGE_URL = 'https://www.chilis.com/account/rewards';
    /**
     * @var CaptchaRecognizer
     */
    private $recognizer;

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
        $this->http->SetProxy($this->proxyDOP(Settings::DATACENTERS_USA));
        $this->http->setUserAgent('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.1 Safari/605.1.15');
    }

    public function IsLoggedIn()
    {
        if ($this->loginSuccessful()) {
            return true;
        }

        return false;
    }

    public function LoadLoginForm()
    {
        $this->http->removeCookies();
        $this->http->GetURL('https://www.chilis.com/login');

        if (
            !$this->http->FindSingleNode('//title[contains(text(), "Chili\'s")]')
            || $this->http->Response['code'] != 200
        ) {
            return $this->checkErrors();
        }

        return true;
    }

    public function Login()
    {
        if (filter_var($this->AccountFields['Login'], FILTER_VALIDATE_EMAIL) === false) {
            $this->AccountFields['Login'] = str_replace(['(', ')', '+', '-', ' '], '', $this->AccountFields['Login']);
        }

        $headers = [
            'Accept'       => 'application/json, text/plain, */*',
            'Content-Type' => 'application/json',
            /*
            'Alt-Used'     => 'www.chilis.com',
            */
            'newrelic'     => 'eyJ2IjpbMCwxXSwiZCI6eyJ0eSI6IkJyb3dzZXIiLCJhYyI6IjEyMTc1MzEiLCJhcCI6IjExMzQ0NzQ1MjAiLCJpZCI6ImZmZWIzNzA5Y2UxNDIyY2QiLCJ0ciI6IjA1MjdmMWZmZDJlZjY5MDFjMjQwNjFkMzMxZDQ2MzljIiwidGkiOjE3NTAxMDgzMDk3NDl9fQ==',
        ];
        $data = [
            'username' => $this->AccountFields['Login'],
        ];
        /*
        $captcha = $this->parseReCaptcha();

        if ($captcha) {
            $data['recaptchaToken'] = $captcha;
        }
        */
        
        // refs #25689
        // this provider is strange - recognized tokens gives error 400 on login, but hardcoded tokens copied from the browser allows to login
        $data['recaptchaToken'] = "HFYjA4cUkZdUh9NCQNTBocTV9eIHRmUjM5BGpvYnlsEGNaA0QsNEdtKWYAZGsRbncGMDJ7BkM6BkNAXjQtfjAvZyJ3NkZsNjIlQBZaMDxlfiAvIDxhQ3Y4bitndBgXBkNLDVYLJm55F1Msejo9Sj0vfxUrXXkvEHMkX2phYSU-dUh9d3M6E1RMZw";

        $this->http->RetryCount = 0;
        $this->http->PostURL('https://www.chilis.com/api/v1/loyalty/getAccountStatus', json_encode($data), $headers);
        $accountStatusData = $this->http->JsonLog();

        if (isset($accountStatusData->error, $accountStatusData->message)) {
            $message = $accountStatusData->message;

            if (
                strstr($message, "Incorrect password. Please try again.")
                || strstr($message, "Loyalty account not found.")
            ) {
                throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
            }

            $this->DebugInfo = $message;

            return false;
        }

        /*
        $this->captchaReporting($this->recognizer);
        */

        $phone = $accountStatusData->phone ?? null;

        if (!isset($phone)) {
            return $this->checkErrors();
        }

        $data = [
            'username'	=> $this->AccountFields['Login'],
            'password'	=> $this->AccountFields['Pass'],
        ];

        /*
        $captcha = $this->parseReCaptcha('6Lc2Jw0qAAAAAJTpals26Sjf0qfDjUQL1d4pydlo');

        if ($captcha) {
            $data['recaptchaToken'] = $captcha;
        }
        */
        $data['recaptchaToken'] = "HFYjA4cUkZdUh9NCQNTBocTV9eIHRmUjM5BGpvYnlsEGNaA0QsNEdtKWYAZGsRbncGMDJ7BkM6BkNAXjQtfjAvZyJ3NkZsNjIlQBZaMDxlfiAvIDxhQ3Y4bitndBgXBkNLDVYLJm55F1Msejo9Sj0vfxUrXXkvEHMkX2phYSU-dUh9d3M6E1RMZw";
        $this->http->PostURL('https://www.chilis.com/api/v1/loyalty/login', json_encode($data), $headers);
        $authResult = $this->http->JsonLog();

        if (isset($authResult->error, $authResult->message)) {
            $message = $authResult->message;

            if (
                strstr($message, "Incorrect password. Please try again.")
                || strstr($message, "Loyalty account not found.")
            ) {
                throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
            }

            $this->DebugInfo = $message;

            return false;
        }

        return isset($authResult->loyaltyID);
        $data = [
            'phone'    => $phone,
            'password' => $this->AccountFields['Pass'],
        ];

        $this->http->PostURL('https://www.chilis.com/login/dge', json_encode($data), $headers);
        $response = $this->http->JsonLog();

        if (isset($response->loggedIn) && $response->loggedIn && $this->loginSuccessful()) {
            return true;
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        $authResult = $this->http->JsonLog();

         // Name
        $this->SetProperty('Name', beautifulName("$authResult->firstName $authResult->lastName"));
        // REWARDS MEMBER ID
        $this->SetProperty('Number', $authResult->phone);
        // SubAccounts

        $this->http->PostURL('https://www.chilis.com/api/v1/loyalty/get-active-rewards', json_encode([
            'loyaltyID' => "".$authResult->loyaltyID,
            'storeGUID' => '',
            'terminalID' => '',
        ]), [
            'Accept' => 'application/json, text/plain, */*',
            'Content-Type' => 'application/json',
        ]);
        $rewards = $this->http->JsonLog();
        // Balance - YOU HAVE ... REWARDS
        $this->SetBalance(count($rewards->rewards));
        $this->logger->debug("Total " . count($rewards->rewards) . " rewards were found");

        foreach ($rewards->rewards as $reward) {
            $date = strtotime($reward->expireDate);
            $displayName = $reward->name;

            $this->AddSubAccount([
                'Code'           => 'chilis' . md5($displayName),
                'DisplayName'    => $displayName,
                'Balance'        => null,
                'ExpirationDate' => $date,
            ]);
        }
    }

    private function loginSuccessful(): bool
    {
        $this->logger->notice(__METHOD__);

        $this->http->RetryCount = 0;
        $this->http->GetURL('https://www.chilis.com/account', [], 20);
        $this->http->RetryCount = 2;

        if (
            $this->http->Response['code'] === 200
            && $this->http->FindSingleNode('//form[contains(@action, "logout")]/@action')
            || $this->http->FindSingleNode('//div[contains(text(), "Welcome, ")]')
        ) {
            return true;
        }

        return false;
    }

    private function checkErrors()
    {
        $this->logger->notice(__METHOD__);

        if ($message = $this->http->FindPreg('/An error occurred while fetching account status: Error: An error occurred while fetching account status./')) {
            throw new CheckException($message, ACCOUNT_PROVIDER_ERROR);
        }

        return false;
    }

    private function parseReCaptcha($key = null)
    {
        $this->logger->notice(__METHOD__);
        $key = $key ?? $this->http->FindPreg("/recaptcha\/enterprise\.js\?render=([^\"\'\&]+)/");

        if (!$key) {
            return false;
        }

        /*
        $postData = [
            "type"       => "RecaptchaV3TaskProxyless",
            "websiteURL" => $this->http->currentUrl(),
            "websiteKey" => $key,
            "minScore"   => 0.3,
            "pageAction" => "accountStatus",
        ];
        $this->recognizer = $this->getCaptchaRecognizer(self::CAPTCHA_RECOGNIZER_ANTIGATE_API_V2);
        $this->recognizer->RecognizeTimeout = 120;

        return $this->recognizeAntiCaptcha($this->recognizer, $postData);
        */

        $this->recognizer = $this->getCaptchaRecognizer();
        $this->recognizer->RecognizeTimeout = 120;
        $parameters = [
            "pageurl"   => $this->http->currentUrl(),
            "proxy"     => $this->http->GetProxy(),
            "version"   => "v3",
            "action"    => "accountStatus",
            "min_score" => 0.3,
        ];

        return $this->recognizeByRuCaptcha($this->recognizer, $key, $parameters);
    }
}
