<?php

class TAccountCheckerMabuhay extends TAccountChecker
{
    private const REWARDS_PAGE_URL = 'https://www.philippineairlines.com/en/overview-account-page';

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
        $this->http->setHttp2(true);
    }

    public function LoadLoginForm()
    {
        $this->http->removeCookies();

        if (!is_numeric($this->AccountFields['Login'])) {
            throw new CheckException("Input 9-digit Member ID without spaces and special characters.", ACCOUNT_INVALID_PASSWORD);
        }

        $this->http->GetURL("https://login.philippineairlines.com/authorize?client_id=QJn1DNBUiHkERG2vS5NGw5a4NODj8I3P&scope=openid+profile+email&audience=https%3A%2F%2Floy.api.philippineairlines.com&redirect_uri=https%3A%2F%2Fwww.philippineairlines.com%2Fuserprofile%2Fcallback.html&prompt=login&response_type=code&response_mode=query&state=eTVHQ2FTUjNNNFg1OXBBbUNjQ1RHSnhyLnREcmxsQ1I0Nk9IflVOUVlNdg%3D%3D&nonce=SU9qUGdnOVBmT1NLeF9aVzlEWXVXck9JMTFSMFJmZFdfdVA4cy5NSi5uUg%3D%3D&code_challenge=rEw4oh8W3-ZpBBTjDxWHrhaLj6WeiKM2VrWFET6mFc4&code_challenge_method=S256&auth0Client=eyJuYW1lIjoiQGF1dGgwL2F1dGgwLWFuZ3VsYXIiLCJ2ZXJzaW9uIjoiMi4yLjMiLCJlbnYiOnsiYW5ndWxhci9jb3JlIjoiMTguMi4xMyJ9fQ%3D%3D");

        if (!$this->http->ParseForm(null, "//form[contains(@class, '_form-login-id')]")) {
            return $this->checkErrors();
        }

        $this->http->SetInputValue("username", $this->AccountFields['Login']);

        if ($captcha = $this->parseHCaptcha()) {
            $this->http->SetInputValue("captcha", $captcha);
        }

        if (!$this->http->PostForm()) {
            return $this->checkErrors();
        }

        if (!$this->http->ParseForm(null, "//form[contains(@class, '_form-login-password')]")) {
            return $this->checkErrors();
        }

        $this->http->SetInputValue("username", $this->AccountFields['Login']);
        $this->http->SetInputValue("password", $this->AccountFields['Pass']);

        return true;
    }

    private function parseHCaptcha()
    {
        $this->logger->notice(__METHOD__);
        $key = $this->http->FindSingleNode('//div[contains(@class, "ulp-captcha-container")]/@data-captcha-sitekey');

        if (!$key) {
            return false;
        }

        $postData = [
            "type"       => "TurnstileTaskProxyless",
            "websiteURL" => $this->http->currentUrl(),
            "websiteKey" => $key,
        ];
        $this->recognizer = $this->getCaptchaRecognizer(self::CAPTCHA_RECOGNIZER_ANTIGATE_API_V2);
        $this->recognizer->RecognizeTimeout = 120;

        return $this->recognizeAntiCaptcha($this->recognizer, $postData);
    }

    public function checkErrors()
    {
        $this->logger->notice(__METHOD__);

        if ($this->http->Response['code'] == 404) {
            $this->http->GetURL("https://www.philippineairlines.com/");

            if ($message = $this->http->FindSingleNode("//h2[contains(text(), 'Maintenance')]")) {
                throw new CheckException($message, ACCOUNT_PROVIDER_ERROR);
            }
        }

        return false;
    }

    public function Login()
    {
        $this->http->RetryCount = 0;

        if (!$this->http->PostForm() && !in_array($this->http->Response['code'], [401, 400])) {
            return $this->checkErrors();
        }

        $this->http->RetryCount = 2;

        if ($this->http->FindNodes("//button[contains(text(), 'Sign out')]")) {
            return true;
        }

        if ($this->parseQuestion()) {
            return false;
        }

        if ($message = $this->http->FindSingleNode('//span[@id = "error-element-password"] | //div[@id = "prompt-alert"]')) {
            $this->logger->error("[Error]: {$message}");

            if (
                strstr($message, 'Incorrect email address, Mabuhay Miles Number, or password')
            ) {
                throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
            }

            if (
                strstr($message, 'Your account has been blocked')
            ) {
                throw new CheckException($message, ACCOUNT_LOCKOUT);
            }

            $this->DebugInfo = $message;

            return false;
        }

        return $this->checkErrors();
    }

    public function parseQuestion()
    {
        $this->logger->notice(__METHOD__);
        $question = $this->http->FindSingleNode("//p[contains(text(), 've sent an email with your code to')]");
        $email = $this->http->FindSingleNode("//span[contains(@class, 'ulp-authenticator-selector-text')]");

        if (!$question || !$email || !$this->http->ParseForm(null, "//form[@data-form-primary=\"true\"]", false)) {
            return false;
        }

        $this->Question = $question." ".$email;
        $this->ErrorCode = ACCOUNT_QUESTION;
        $this->Step = "Question";

        return true;
    }

    public function ProcessStep($step)
    {
        $this->logger->notice(__METHOD__);
        $answer = $this->Answers[$this->Question];
        unset($this->Answers[$this->Question]);

        $this->http->SetInputValue("code", $answer);

        if (!$this->http->PostForm()) {
            return false;
        }

        return $this->getToken();
    }

    private function getToken()
    {
        $code = $this->http->FindPreg("/code=([^&]+)/", false, $this->http->currentUrl());

        if (!$code) {
            $this->logger->debug('Authorization code not found');

            return $this->checkErrors();
        }

        $data = [
            "client_id"     => "QJn1DNBUiHkERG2vS5NGw5a4NODj8I3P",
            'code_verifier' => '1A57JJD4ZBcCiAKoayjFxmPp6yoU3F0_sP8cgWXDRPK',
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            "redirect_uri"  => "https://www.philippineairlines.com/userprofile/callback.html",
        ];
        $headers = [
            "Accept"       => "*/*",
            "Content-Type" => "application/x-www-form-urlencoded",
            "Auth0-Client" => "eyJuYW1lIjoiQGF1dGgwL2F1dGgwLWFuZ3VsYXIiLCJ2ZXJzaW9uIjoiMi4yLjMiLCJlbnYiOnsiYW5ndWxhci9jb3JlIjoiMTguMi4xMyJ9fQ==",
        ];
        $this->http->PostURL("https://login.philippineairlines.com/oauth/token", $data, $headers);
        $authResult = $this->http->JsonLog();

        if (!isset($authResult->access_token)) {
            $this->logger->debug('Token not found');

            return false;
        }

        $this->State['token'] = $authResult->access_token;
        $data = [
            "membershipNo" => $this->AccountFields['Login'],
        ];
        $headers = [
            "Accept"        => "application/json, text/plain, */*",
            "Content-Type"  => "application/json",
            "Authorization" => "Bearer {$this->State['token']}",
        ];
        $this->http->PostURL("https://www.philippineairlines.com/pal/profile-login-view-web/v1/ed", json_encode($data), $headers);
        $response = $this->http->JsonLog();

        if (isset($response->membershipNo)) {
            return true;
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        $response = $this->http->JsonLog(null, 0);
        // Balance - Mabuhay Miles
        $this->SetBalance($response->redeemableMiles);
        // Name
        $this->SetProperty("Name", beautifulName("{$response->firstName} {$response->lastName}"));
        // Your Membership Number
        $this->SetProperty("Number", $response->membershipNo);
        // Your Membership Status
        $this->SetProperty("Status", $response->tierDescription);
        // Expiration date  // refs #9378
        /*
        $expiringBalance = $data->nextExpiryMiles;
        $exp = $data->milesExpiryDate;
        $this->logger->debug("Expire {$expiringBalance} miles on {$exp}");
        // 3.a
        if ($expiringBalance != 0 && strtotime($exp)) {
            $this->SetExpirationDate(strtotime($exp));
            $this->SetProperty("ExpiringBalance", $expiringBalance);
        }
        // 3.b
        elseif ($expiringBalance == 0 && $this->Balance > 0 && $exp != "0001-01-01T00:00:00" && strtotime($exp)) {
            $this->SetExpirationDate(strtotime("+2 year", strtotime($exp)));
            $this->SetProperty("ExpiringBalance", $this->Balance);
        }
        */

        // milesFlownThisTier, sectorsFlownThisTier etc
//        $data = [
//            "membershipNo" => $this->AccountFields['Login'],
//        ];
//        $headers = [
//            "Accept"        => "application/json, text/plain, */*",
//            "Content-Type"  => "application/json",
//            "Authorization" => "Bearer {$this->State['token']}",
//        ];
//        $this->http->PostURL("https://www.philippineairlines.com/pal/profile-account-overview/v1", json_encode($data), $headers);
//        $response = $this->http->JsonLog();
    }
}
