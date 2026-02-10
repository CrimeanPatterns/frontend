<?php

use AwardWallet\Common\Parsing\Html;
use AwardWallet\Common\Parsing\JsExecutor;
use AwardWallet\Engine\ProxyList;

class TAccountCheckerMilleniumnclc extends TAccountChecker
{
    use ProxyList;
    use SeleniumCheckerHelper;
    /**
     * @var CaptchaRecognizer
     */
    private $recognizer;

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;

        $this->UseSelenium();
        $this->useFirefoxPlaywright();
        $this->http->saveScreenshots = true;

        $this->seleniumOptions->addAntiCaptchaExtension = true;
    }

    /*
    public function IsLoggedIn()
    {
        $this->http->RetryCount = 0;
        $headers = [
            "X-Requested-With" => "XMLHttpRequest",
            "Accept"           => "application/json, text/javascript, *
    /*; q=0.01",
        ];
        $this->http->GetURL('https://www.millenniumhotels.com/api/account/profile', $headers, 20);
        $response = $this->http->JsonLog(null, 3, true);
        $this->http->RetryCount = 2;

        if (ArrayVal($response, 'loyaltyno')) {
            return true;
        }

        return false;
    }
    */

    public function LoadLoginForm()
    {
        $this->http->removeCookies();
        $this->http->GetURL("https://www.millenniumhotels.com/en/my-millennium/sign-in/?returnUrl=https://www.millenniumhotels.com/");
        $login = $this->waitForElement(WebDriverBy::xpath('//input[@name = "username"]'), 10);
        $pass = $this->waitForElement(WebDriverBy::xpath('//input[@name = "password"]'), 0);
        $this->saveResponse();

        if (!$login || !$pass) {
            if ($this->loginSuccessful()) {
                return true;
            }

            return $this->checkErrors();
        }

        $login->sendKeys($this->AccountFields['Login']);
        $pass->sendKeys($this->AccountFields['Pass']);

        $this->waitForElement(WebDriverBy::xpath('//a[contains(text(), "Solving is in process...")]'), 10);

        $this->waitFor(function () {
            $this->logger->warning("Solving is in process...");
            sleep(3);
            $this->saveResponse();

            return !$this->http->FindNodes('//a[contains(text(), "Solving is in process...")]');
        }, 250);

        $btn = $this->waitForElement(WebDriverBy::xpath('//a[@data-gtm-ga4 and contains(text(), "Sign In")]'), 0);
        $this->saveResponse();

        if (!$btn) {
            return $this->checkErrors();
        }

        $btn->click();

        /*
        $xCsrf = $this->http->FindPreg('/<input name="__RequestVerificationToken" type="hidden" value="(.+?)"/');

        if (!$xCsrf) {
            return false;
        }
//        $captcha = $this->parseCaptcha();
        $captcha = $this->parseReCaptcha("6LeyWKUeAAAAAG4e4cS_otba9BdXvU-NgmowmR3p");

        if ($captcha === false) {
            return false;
        }

        $data = [
            "urls"        => [
                "forget_pwd" => "https://www.millenniumhotels.com/en/my-millennium/forgot-password",
                "sign_up"    => "https://www.millenniumhotels.com/en/my-millennium/sign-up",
                "checkout"   => "/en/",
            ],
            "signup_url"  => "https://www.millenniumhotels.com/en/my-millennium/sign-up#callbackurl=/",
            "email"       => $this->AccountFields['Login'],
            "password"    => $this->AccountFields['Pass'],
            "remember_me" => "1",
            "captcha"     => $captcha,
            "anchor"      => "center-center",
        ];
        $headers = [
            "X-Requested-With" => "XMLHttpRequest",
            "Content-Type"     => "application/json",
            "Accept"           => "application/json, text/javascript, *
        /*; q=0.01",
            "X-CSRF"           => $xCsrf,
        ];
        $this->State['form'] = $data;
        $this->State['headers'] = $headers;
        $this->http->PostURL('https://www.millenniumhotels.com/mapi/account/login', json_encode($data), $headers);
        */

        return true;
    }

    public function GetRedirectParams($targetURL = null)
    {
        $arg = parent::GetRedirectParams($targetURL);
//        $arg['SuccessURL'] = 'http://www.mcloyaltyclub.com/Secure/Points.aspx';
        return $arg;
    }

    public function checkErrors()
    {
        $this->logger->notice(__METHOD__);

        if ($message = $this->http->FindSingleNode('
                //img[contains(@alt, "We will be gone for a short while. The good news is we will be back up and running on 1 March 2016 with our brand new loyalty programme")]/@alt
                | //p[contains(text(), "Our website is temporarily unavailable, we\'re working hard to ensure it is available in the next few minutes.")]
            ')
        ) {
            throw new CheckException($message, ACCOUNT_PROVIDER_ERROR);
        }
        // Oops! Something went wrong. Please try again in a few minutes.
        if ($message = $this->http->FindSingleNode('//h1[contains(text(), "Something went wrong")]')) {
            throw new CheckException("Oops! Something went wrong. Please try again in a few minutes.", ACCOUNT_PROVIDER_ERROR);
        }

        $response = $this->http->JsonLog();
        $status = $response->status ?? null;
        $message = $response->message ?? null;

        if (
            $status === 403
            && $message === "User_Access_Limit_Error"
        ) {
            $this->DebugInfo = $message;
        }

        return false;
    }

    private function parseQuestion()
    {
        $this->logger->notice(__METHOD__);

        $q = $this->waitForElement(WebDriverBy::xpath('//p[contains(text(), "Please enter the code that has been sent to your email.")]'), 0);
        $questionInput = $this->waitForElement(WebDriverBy::xpath('//input[@placeholder="Enter code"]'), 0);

        if (!$q || !$questionInput) {
            $this->logger->error("something went wrong");
            $this->saveResponse();

            return false;
        }

        $question = $q->getText();

        if (!isset($this->Answers[$question])) {
            $this->holdSession();
            $this->AskQuestion($question, null, "Question");

            return true;
        }

        $answer = $this->Answers[$question];
        unset($this->Answers[$question]);

        $questionInput->clear();
        $questionInput->sendKeys($answer);
        $this->logger->debug("ready to click");
        $btn = $this->waitForElement(WebDriverBy::xpath('//a[contains(@class, "v-base-button") and contains(text(), "Sign In") and not(@disabled)]'), 0);
        $this->saveResponse();

        if (!$btn) {
            $this->logger->error("btn not found");
            $this->saveResponse();

            return false;
        }

        $this->waitForElement(WebDriverBy::xpath('//a[contains(text(), "Solving is in process...")]'), 5);
        $this->saveResponse();

        $this->waitFor(function () {
            $this->logger->warning("Solving is in process...");
            sleep(3);
            $this->saveResponse();

            return !$this->http->FindNodes('//a[contains(text(), "Solving is in process...")]');
        }, 250);

        $this->logger->debug("clicking next");
        $btn->click();

        sleep(10);
        $this->saveResponse();

        if ($error = $this->waitForElement(WebDriverBy::xpath('//p[@class = "error-msg" and normalize-space(.) != ""]'), 0)) {
            $message = $error->getText();
            $this->logger->error("[Question Error]: {$message}");

            if (strstr($message, 'The code is invalid or expired.')) {
                unset($this->Answers[$question]);
                $this->holdSession();
                $this->AskQuestion($question, $message, "Question");
            }

            if (strstr($message, 'Invalid email or password')) {
                throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
            }

            $this->DebugInfo = $message;

            $this->saveResponse();

            return true;
        }

        return false;
    }

    public function Login()
    {
        $this->waitForElement(WebDriverBy::xpath('//input[@placeholder="Enter code"] | //a[contains(@class, "user-info")] | //div[contains(@class, "not-null input-error")]//span[@class = "error-tip not-null"]'), 20);
        $this->saveResponse();

        if ($this->loginSuccessful()) {
            return true;
        }

        if ($this->parseQuestion()) {
            return false;
        }

        if ($message = $this->http->FindSingleNode('//div[contains(@class, "not-null input-error")]//span[@class = "error-tip not-null"]')) {
            $this->logger->error("[Error]: {$message}");

            if ($message == 'This is not a valid email address') {
                throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
            }

            $this->DebugInfo = $message;

            return false;
        }

        return $this->checkErrors();


        $response = $this->http->JsonLog();
        // Access is allowed
        if (isset($response->success) && $response->success == true) {
            $this->captchaReporting($this->recognizer);
            $data = [
                "username" => $this->AccountFields['Login'],
                "password" => $this->AccountFields['Pass'],
            ];
            $headers = [
                "X-Requested-With" => "XMLHttpRequest",
                "Content-Type"     => "application/json",
                "Accept"           => "application/json, text/javascript, */*; q=0.01",
            ];
            $this->http->PostURL('https://www.millenniumhotels.com/', $data, $headers);

            return true;
        }

        if (is_array($response) && isset($response[0]->msg)) {
            $message = $response[0]->msg;
            $this->logger->error("[Error]: {$message}");
            // Incorrect login name or password
            if (strstr($message, 'Sorry, your email address is not registered with us.')) {
                throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
            }
            // Invalid email or password. Click on the forgot password link to reset your password
            if (strstr($message, 'Invalid email or password.')
                || strstr($message, 'Your password must be at least 8 characters')
                || strstr($message, 'Please enter a valid email address.')) {
                throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
            }
            // System is busy. Please try again in 5 minutes.
            if ($message == 'System is busy. Please try again in 5 minutes.'
                || $message == 'Sorry, we are not able to get rates right now. Please try again in 5 minutes.'
                || strstr($message, 'Our member services are currently not available.')
            ) {
                throw new CheckException($message, ACCOUNT_PROVIDER_ERROR);
            }
            // bad message
            if ($message == 'Logon_Failed_Error') {
                throw new CheckException('Incorrect login name or password', ACCOUNT_INVALID_PASSWORD);
            }
        }// if (is_array($response) && isset($response[0]->msg))

        if (isset($response->Message)) {
            $message = $response->Message;
            $this->logger->error($message);
            // An error has occurred
            if ($message == 'An error has occurred.') {
                throw new CheckException($message, ACCOUNT_PROVIDER_ERROR);
            }
        }

        if (isset($response->message)) {
            $message = $response->message;
            $this->logger->error("[Error]: {$message}");

            if ($message == 'The captcha is invalid.') {
                $this->captchaReporting($this->recognizer, false);

                throw new CheckRetryNeededException(3, 1, self::CAPTCHA_ERROR_MSG);
            }

            $this->captchaReporting($this->recognizer);

            if (strstr($message, 'Please enter the code that has been sent to your email. ')) {
                $this->AskQuestion($message, null, "Question2fa");

                return false;
            }

            // Invalid email or password. Click on the forgot password link to reset your password
            if ($message == 'Invalid email or password. Click on the forgot password link to reset your password') {
                throw new CheckException("Invalid email or password", ACCOUNT_INVALID_PASSWORD);
            }

            if (
                // Sorry, we are not able to get rates right now. Please try again in 5 minutes.
                strpos($message, 'Sorry, we are not able to get rates right now.') !== false
                // System is busy. Please try again in 5 minutes.
                || strpos($message, 'System is busy. Please try again in 5 minutes.') !== false
                || strstr($message, 'We are maintaining our system')
                || strstr($message, 'The system is currently unavailable due to scheduled maintenance')
            ) {
                throw new CheckException($message, ACCOUNT_PROVIDER_ERROR);
            }

            if (
                // Oops! Something went wrong. Please try again in a few minutes.
                strpos($message, 'login.request_time_out_please_try_again') !== false
            ) {
                throw new CheckException("Oops! Something went wrong. Please try again in a few minutes.", ACCOUNT_PROVIDER_ERROR);
            }

            if (strstr($message, 'Our member services are currently not available.')) {
                throw new CheckException($message, ACCOUNT_PROVIDER_ERROR);
            }

            $this->DebugInfo = $message;

            return false;
        }

        return $this->checkErrors();
    }

    public function ProcessStep($step)
    {
        if ($this->parseQuestion()) {
            return false;
        }

        return true;

        if (!isset($this->State['form'], $this->State['headers'])) {
            return true;
        }

        $this->State['form']['emailcode'] = $this->Answers[$this->Question];
        $this->State['form']['message'] = $this->Question;
        $this->State['form']['status'] = 11;
        $this->State['form']['success'] = false;
        $this->State['form']['error'] = [];
        unset($this->Answers[$this->Question]);

        $this->http->RetryCount = 0;
        $this->http->PostURL('https://www.millenniumhotels.com/mapi/account/login', json_encode($this->State['form']), $this->State['headers'], 80);
        $this->http->RetryCount = 2;
        $response = $this->http->JsonLog();
        $message = $response->message ?? null;
        $this->logger->error("[Error]: {$message}");

        if (
            strstr($message, 'The code is invalid or expired.')
            || strstr($message, ' failed attempts. Please try again after')
        ) {
            $this->AskQuestion($this->Question, $message, "Question2fa");

            return false;
        }

        if (strstr($message, 'Invalid email or password.')) {
            throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
        }

        if (strstr($message, 'Our member services are currently not available.')
            || strstr($message, 'We are currently experiencing technical difficulties')
        ) {
            throw new CheckException($message, ACCOUNT_PROVIDER_ERROR);
        }

        return true;
    }

    public function Parse()
    {
        $headers = [
            "X-Requested-With" => "XMLHttpRequest",
            "Accept"           => "application/json, text/javascript, */*; q=0.01",
        ];
        $logLevel = 3;

        if ($this->http->currentUrl() != 'https://www.millenniumhotels.com/api/account/profile') {
            $this->http->GetURL('https://www.millenniumhotels.com/api/account/profile', $headers);
            $logLevel = 3;
        }
        $response = $this->http->JsonLog($this->http->FindSingleNode("//pre[not(@id)]"), $logLevel, true);
        // Member #
        $this->SetProperty("MemberNumber", ArrayVal($response, 'loyaltyno'));
        // Name
        $name = Html::cleanXMLValue(ArrayVal($response, 'first_name') . ' ' . ArrayVal($response, 'last_name'));
        $this->SetProperty("Name", beautifulName($name));
        // Balance - Points Available
        $points = ArrayVal($response, 'points');
        $this->SetBalance(ArrayVal($points, 'balance'));
        // Member's Tier
        if (ArrayVal($points, 'tier') !== 'None') {
            $this->SetProperty("Tier", ArrayVal($points, 'tier'));
        }
        // Bonus Points
        $this->SetProperty("BonusPoints", ArrayVal($points, 'bonuse'));
        // Normal Points
        $this->SetProperty("NormalPoints", ArrayVal($points, '_base'));
        // Redeemed
        $this->SetProperty("Redeemed", ArrayVal($points, 'redeemed'));
        // Expired
        $this->SetProperty("Expired", ArrayVal($points, 'expired'));
        // Member since
        $this->SetProperty("MemberSince", ArrayVal($response, 'register_date'));
        // Room nights to next tier
        $this->SetProperty("RoomNights", ArrayVal($points, 'upgrade'));
        // Points to next tier
        $this->SetProperty("PointsToNextTier", ArrayVal($points, 'upgradepoints'));
        // Expiring Points in next 3 months
        $this->SetProperty("ExpiringBalance", ArrayVal($points, 'expiring_next_3_months'));

        // refs #22173
        $this->logger->info("Expiration Date", ['Header' => 3]);
        $this->http->GetURL("https://www.millenniumhotels.com/api/account/pointshistory?page=0&pagesize=20");
        $pointshistory = $this->http->JsonLog($this->http->FindSingleNode("//pre[not(@id)]"))[0]->data ?? [];
        $expiration = null;
        $expPoints = null;

        foreach ($pointshistory as $key => $row) {
            if (
                $expiration === null
                || (
                    strtotime($row->expirationdate) > time()
                    && strtotime($expiration) > strtotime($row->expirationdate)
                )
            ) {
                $expiration = $row->expirationdate;
                $expPoints = $row->pointsearned;
            } elseif ($expiration == $row->expirationdate) {
                $expiration = $row->expirationdate;
                $expPoints += $row->pointsearned;
            }

            $this->logger->debug("[{$key}]: {$expiration} - {$expPoints}");
        }// foreach ($pointshistory as $row)

        if ($expPoints) {
            // Expiration Date
            $this->SetExpirationDate(strtotime($expiration));
            // Expiring Points
            $this->SetProperty("ExpiringBalance", $expPoints);
        }

        // provider error
        if ($this->ErrorCode == ACCOUNT_ENGINE_ERROR) {
            if (
                ArrayVal($response, 'Message') == 'An error has occurred.'
                || $this->http->FindSingleNode('//h1[contains(text(), "Something went wrong")]/following-sibling::p[contains(text(), "Please try again in a few minutes.")]')
            ) {
                throw new CheckException(self::PROVIDER_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
            }
            // AccountID: 4676730
            if (
                $this->http->Response['code'] == 500
                || $this->http->FindSingleNode('//h1[contains(text(), "We’re sorry.")]/following-sibling::p[contains(text(), "Our website is temporarily unavailable, we\'re working hard to ensure it is available in the next few minutes.")]')
            ) {
                throw new CheckException(self::PROVIDER_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
            }
        }// if ($this->ErrorCode == ACCOUNT_ENGINE_ERROR)
    }

    protected function parseReCaptcha($key)
    {
        $this->logger->notice(__METHOD__);
        $this->logger->debug("data-sitekey: {$key}");

        if (!$key) {
            return false;
        }
        $this->recognizer = $this->getCaptchaRecognizer();
        $this->recognizer->RecognizeTimeout = 120;
        $parameters = [
            "pageurl" => $this->http->currentUrl(),
            "proxy"   => $this->http->GetProxy(),
        ];

        return $this->recognizeByRuCaptcha($this->recognizer, $key, $parameters);
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);

        if ($this->http->FindNodes('//a[contains(@class, "user-info")]/@class')) {
            return true;
        }

        return false;
    }

    private function parseCaptcha()
    {
        $this->logger->notice(__METHOD__);
        $jsExecutor = $this->services->get(JsExecutor::class);

        $captchaHttp = clone $this->http;
        $captchaHttp->GetURL("https://www.millenniumhotels.com/mapi/Captcha/GetCaptchaValue");
        $response = $captchaHttp->JsonLog();

        $encryptedCaptcha = $response->data->captcha ?? '';
        $auth = $response->data->auth ?? '';

        if (empty($encryptedCaptcha) || empty($auth)) {
            return $this->checkErrors();
        }

        $captcha = $jsExecutor->executeString("
                              var u = CryptoJS;
                              var n = '{$encryptedCaptcha}';
                              var t = '{$auth}';
                              var c = u.AES.decrypt(n, u.enc.Utf8.parse(t.substr(32, 32)), {
                                mode: u.mode.ECB,
                                padding: u.pad.Pkcs7
                               });
                             sendResponseToPhp(c.toString(u.enc.Utf8).toString());",
        5, ['https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.0.0/crypto-js.js']);

        return $captcha;
    }
}
