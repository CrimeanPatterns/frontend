<?php

use AwardWallet\Engine\ProxyList;

class TAccountCheckerNectar extends TAccountChecker
{
    use ProxyList;

    private $headers = [
        "Accept"       => "application/json",
        "pianoChannel" => "JS-WEB",
    ];

    /**
     * @var CaptchaRecognizer
     */
    private $recognizer;

    private $retry = false;

    public function GetRedirectParams($targetURL = null)
    {
        $arg = parent::GetRedirectParams($targetURL);
        $arg["CookieURL"] = "https://www.nectar.com/login";

        return $arg;
    }

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
        $this->http->setHttp2(true);
        $this->http->setDefaultHeader("Accept-Encoding", "gzip, deflate, br");
        /*
        $this->http->SetProxy($this->proxyUK());
        $this->http->SetProxy($this->proxyDOP(['lon1']));
        */
        $this->setProxyGoProxies(null, 'gb');
//        $this->setProxyBrightData(null, 'static', 'uk');
    }

    public function IsLoggedIn()
    {
//        $this->enableProxy();

        if ($this->loginSuccessful()) {
            return true;
        }

        return false;
    }

    public function LoadLoginForm()
    {
        $this->http->removeCookies();

        if (
            (empty($this->AccountFields['Login2']) || filter_var($this->AccountFields['Login2'], FILTER_VALIDATE_EMAIL) === false)
        ) {
            throw new CheckException("To update this Nectar account you need to fill in the ‘Email’ field and update your password to your ID password. To do so, please click the ‘Edit’ button which corresponds to this account. Until you do so we would not be able to retrieve your bonus information.", ACCOUNT_PROVIDER_ERROR);
        }

        // do not use auth, not working
        if ($this->isBackgroundCheck()) {
            $this->Cancel();
        }

//        throw new CheckException(PROVIDER_CHECKING_VIA_EXTENSION_ONLY, ACCOUNT_PROVIDER_DISABLED);
        // Email addresses cannot be used to logon at this time
        if (filter_var($this->AccountFields['Login'], FILTER_VALIDATE_EMAIL) !== false) {
            throw new CheckException('Email addresses cannot be used to logon at this time', ACCOUNT_INVALID_PASSWORD);
        }

        if (strlen($this->AccountFields['Login']) > 11) {
            $this->AccountFields['Login'] = trim(substr($this->AccountFields['Login'], 8));

            if (strlen($this->AccountFields['Login']) == 12 && strpos($this->AccountFields['Login'], '0') === 0) {
                $this->AccountFields['Login'] = substr($this->AccountFields['Login'], 1);
            }

            if (strlen($this->AccountFields['Login']) == 12) {
                throw new CheckException('That card number isn’t recognised, please re enter the last 11 digits of your Nectar card number.', ACCOUNT_INVALID_PASSWORD);
            }
        }

        if (
            !is_numeric($this->AccountFields['Login'])
            || strlen($this->AccountFields['Login']) < 11
        ) {
            throw new CheckException('This Nectar card is not yet registered to New Nectar or password is incorrect.', ACCOUNT_INVALID_PASSWORD);
        }

//        $this->http->GetURL("https://www.nectar.com/signin");
        $this->http->RetryCount = 1;
        $this->http->GetURL("https://www.nectar.com/id/start/login");

        if (
            $this->http->Response['code'] == 403
            || strstr($this->http->Error, 'Network error 56 - Received HTTP code 503 from proxy after CONNECT')
            || strstr($this->http->Error, 'Network error 28 - Operation timed out after')
            || strstr($this->http->Error, 'Network error 56 - Received HTTP code 407 from proxy after CONNECT')
        ) {
            $this->setProxyBrightData(true, 'static', 'uk');
            $this->http->removeCookies();
            $this->http->GetURL("https://www.nectar.com/signin");
        }
        $this->http->RetryCount = 2;

        if ($this->http->Response['code'] != 200) {
            // Network error 56 - Received HTTP code 503 from proxy after CONNECT'
            if (
                strstr($this->http->Error, 'Network error 56 - Received HTTP code 503 from proxy after CONNECT')
                || strstr($this->http->Error, 'Network error 28 - Operation timed out after')
            ) {
                $this->DebugInfo = 'Received HTTP code 503 from proxy';

                if (ConfigValue(CONFIG_SITE_STATE) != SITE_STATE_DEBUG) {
                    throw new CheckRetryNeededException(3, 5);
                }
            }

            if ($this->http->Response['code'] == 403) {
                $this->markProxyAsInvalid();

                if (ConfigValue(CONFIG_SITE_STATE) != SITE_STATE_DEBUG) {
                    throw new CheckRetryNeededException(3, 0);
                }
            }

            return $this->checkErrors();
        }

//        $this->http->GetURL("https://account.sainsburys.co.uk/oauth2/auth?audience=www.nectar.com&missionId=nectar&client_id=nectar&redirect_uri=https%3A%2F%2Fwww.nectar.com%2Foauth_redirect&response_type=code&scope=openid%20offline&code_challenge=HpkTeEOoPZzDndtfGm3OUEBmiQSG3F0_gq016ZEHK20&code_challenge_method=S256&state={%22collectorId%22%3A%22%22}&platform=web&action=login");
//        $this->http->GetURL("https://account.sainsburys.co.uk/nectar/login");

        /*
        $data = [
            "cardNumber" => $this->AccountFields['Login'],
        ];
        $headers = [
            "Content-Type" => "application/json",
        ];

        $url = "https://www.nectar.com/recaptcha/identity-registration-api/progressive/card-check";

        if ($key = $this->http->FindPreg("/window.recaptchaSiteKey=\"([^\"]+)/")) {
            $captcha = $this->parseCaptcha($key, "https://www.nectar.com/id/start/enter-card-number");
            $headers['X-Recaptcha'] = "Bearer {$captcha}";
            $url = 'https://www.nectar.com/recaptcha/identity-registration-api/progressive/card-check';
        }

        $this->http->RetryCount = 0;
        $this->http->PostURL($url, json_encode($data), $this->headers + $headers);
        $this->http->RetryCount = 2;
        */

        return true;
    }

    public function checkErrors()
    {
        $this->logger->notice(__METHOD__);
        /*
         * Access blocked outside UK
         */
        if ($this->http->FindSingleNode('//td[contains(text(), "This page can\'t be displayed. Contact support for additional information.")]')) {
            $this->DebugInfo = 'Access has been blocked';
            $this->ErrorReason = self::ERROR_REASON_BLOCK;

            return false;
        }

        if (
            $this->http->FindSingleNode("//h1[contains(text(), 'HTTP Status 404 - /site/login')]")
            || $this->http->FindSingleNode("//title[contains(text(), '404 Not Found')]")
            || ($this->http->FindSingleNode("//p[contains(text(), 'There seems to be a problem.')]") && $this->http->Response['code'] == 404)
            || $this->http->FindSingleNode("//title[contains(text(), '503 Service Temporarily Unavailable')]")
        ) {
            throw new CheckException(self::PROVIDER_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
        }

        // retries
        if (($this->http->currentUrl() == 'http://www.nectar.com/' && $this->http->Response['code'] == 301)
            || ($this->http->currentUrl() == 'https://www.nectar.com/' && $this->http->Response['code'] == 200)) {
            throw new CheckRetryNeededException(3, 10);
        }

        $this->http->RetryCount = 0;
        $this->http->GetURL("https://www.nectar.com/", [], 10);
        $this->http->RetryCount = 2;

        if ($message = $this->http->FindSingleNode('//h2[contains(text(),"Hello! Don\'t worry, you have reached the Nectar site, but we\'re upgrading it at the moment")]')) {
            throw new CheckException($message, ACCOUNT_PROVIDER_ERROR);
        }

        return false;
    }

    public function Login()
    {
        /*
        $response = $this->http->JsonLog();
        $message = $response->description ?? null;
        // The card number and/or password is incorrect
        if ($message) {
            $this->logger->error($message);

            if (
                strstr($message, 'This Nectar card is not yet registered to New Nectar or password is incorrect.')
                || strstr($message, 'This Nectar card is not yet registered to Nectar or password is incorrect.')
                || strstr($message, 'Your Nectar card number or password is incorrect. Please try again or reset your password.')
                || $message == 'Sorry, your Nectar account has been closed. We’ve closed your Nectar account as you’ve not used it to collect or spend points for more than 12 months. If you’d like to join Nectar again, you can register for a new card.'
            ) {
                $this->captchaReporting($this->recognizer);

                throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
            }

            if ($message == 'We\'ve locked your account after too many incorrect password attempts. Please reset your password using the Forgot password link to continue.') {
                $this->captchaReporting($this->recognizer);

                throw new CheckException("Your Nectar account has been locked", ACCOUNT_LOCKOUT);
            }

            if (
                $message == 'Sorry, something failed.'
                || $message == 'The Nectar service is experiencing a problem - Please try again later'
                || $message == 'Sorry, there’s a problem with your Nectar card. Chat with us and we’ll get you going with Nectar again as quickly as possible.'
                || $message == 'Something went wrong. Try again'
            ) {
                $this->captchaReporting($this->recognizer);

                throw new CheckException($message, ACCOUNT_PROVIDER_ERROR);
            }

            if (
                strstr($message, 'Failed to create session - please try again later.')
            ) {
                $this->captchaReporting($this->recognizer, false);

                throw new CheckRetryNeededException();
            }

            if ($message != 'That card number isn’t recognised, please re enter the last 11 digits of your Nectar card number.') {
                return false;
            }
        }

        if (
            in_array($this->http->Response['code'], [502, 504])
            && $this->http->FindSingleNode('//h2[contains(text(), "The request could not be satisfied.")]')
        ) {
            $this->captchaReporting($this->recognizer);

            throw new CheckException(self::PROVIDER_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
        }

        /*
        // Access is allowed
        if ($this->loginSuccessful()) {
            $this->captchaReporting();
            return true;
        }
        * /

        $response = $this->http->JsonLog(null, 0);
//        $message = $response->description ?? null;
        $authToken = $response->authToken ?? null;
        $maskedEmailToken = $response->maskedEmailToken ?? null;

        $next = $response->next[0] ?? null;
        // Enter your email address
        if ($next == 'DO_EMAIL_CHECK') {
            $this->throwProfileUpdateMessageException();
        }

        if (
            ($authToken && (!empty($maskedEmailToken) || $next == 'DO_LOGIN_NECTAR'))
            || $message == 'That card number isn’t recognised, please re enter the last 11 digits of your Nectar card number.'
        ) {
            $this->captchaReporting($this->recognizer);
//            $this->logger->error($message);

            $username = null;

            foreach (explode('.', $maskedEmailToken) as $str) {
                $str = base64_decode($str);
                $this->logger->debug($str);

                if ($username = $this->http->FindPreg('/"sub":"(.+?)"/', false, $str)) {
                    break;
                }
            }

            $web_authn_device = false;

            if (!$username
                && (
                    $next == 'DO_LOGIN_NECTAR'
                    || $message == 'That card number isn’t recognised, please re enter the last 11 digits of your Nectar card number.'
                )
            ) {
            */
        $username = $this->AccountFields['Login2'];
        $web_authn_device = true;
        /*
            }
        */

        if (!$username) {
            $this->logger->error("username not found");

            return false;
        }

        /*
        // Soon we’ll be introducing My ID so you’ll only need your email and password to log in.
        if ($message == 'Identity Session Upgrade Required') {
            if (
                filter_var($this->AccountFields['Login'], FILTER_VALIDATE_EMAIL) === false
                && (empty($this->AccountFields['Login2']) || filter_var($this->AccountFields['Login2'], FILTER_VALIDATE_EMAIL) === false)
            ) {
                throw new CheckException("To update this Nectar account you need to fill in the ‘Email’ field and update your password to your ID password. To do so, please click the ‘Edit’ button which corresponds to this account. Until you do so we would not be able to retrieve your bonus information.", ACCOUNT_PROVIDER_ERROR);
            }
        */

        $this->http->RetryCount = 0;
        $code_challenge = 'OqZdT2vLnQQspCcM9VL14WxDF75kdxT8MnJV9-vFKC8';
        /*
        $this->http->GetURL('https://account.sainsburys.co.uk/oauth2/auth?audience=www.nectar.com&missionId=nectar&client_id=nectar&redirect_uri=https://www.nectar.com/oauth_redirect&response_type=code&scope=openid offline&code_challenge='.$code_challenge.'&code_challenge_method=S256&state={"collectorId":"' . $this->AccountFields['Login'] . '"}');
        */
        $this->http->GetURL("https://account.sainsburys.co.uk/oauth2/auth?audience=www.nectar.com&missionId=nectar&client_id=nectar&redirect_uri=https%3A%2F%2Fwww.nectar.com%2Foauth_redirect&response_type=code&scope=openid%20offline&code_challenge={$code_challenge}&code_challenge_method=S256&state=%7B%22collectorId%22%3A%22%22%7D&platform=web&action=login");
//            $this->http->GetURL("https://account.sainsburys.co.uk/oauth2/auth?audience=www.nectar.com&missionId=nectar&client_id=nectar&redirect_uri=https%3A%2F%2Fwww.nectar.com%2Foauth_redirect&response_type=code&scope=openid%20offline&code_challenge={$code_challenge}&code_challenge_method=S256&state={\"collectorId\":\"{$this->AccountFields['Login']}\"}&platform=web&action=login&masked_email_token={$maskedEmailToken}");
        $login_challenge = $this->http->FindPreg("/login_challenge=(.+?)$/", false, $this->http->currentUrl());
        $key = $this->http->FindPreg("/recaptcha\/api\.js\?render=([^\"&]+)/");

        if (!$login_challenge/* || !$key*/) {
            // Network error 56 - Received HTTP code 503 from proxy after CONNECT'
            if (strstr($this->http->Error, 'Network error 56 - Received HTTP code 503 from proxy after CONNECT')) {
                $this->DebugInfo = 'Received HTTP code 503 from proxy';

                if (ConfigValue(CONFIG_SITE_STATE) != SITE_STATE_DEBUG) {
                    throw new CheckRetryNeededException(3, 5);
                }
            }

            return false;
        }
//                $captcha = $this->parseCaptcha("6LeoSY8UAAAAAFHRKNIzkcuGBY0mKXMZdTHxoGu6");
//                https://account.sainsburys.co.uk/nectar/login-ui/4814/js/main.7d140ff0.chunk.js
//                $captcha = '03AGdBq27zd0TBijjtsivZk-G7d8-XNLcKYzolVg7Qq6WCsHd__z_2JWcpi1nNg6HhRhZs_chfM6e3THrQcVcWDacv_tg-wsaSaDETG8m_SI8EyQvEuTAzZLkqgWkyKAvnoXVzqRplJvc6pMWxNFRrhCPPqOAOAi2PgGq3tjvR6cRYsfnNGRSQTlxmiOUBGJpPxyvQ4ZberywTb09Cu54Fs83TyGCDnmpgGqDCcIAt1qiVAjJK8jyiPt8ZbNhduWOKODmBLPG0kp3Mybp5EYxGa9Hsh7f4RRFTepf5_etLr3Pa-Pd7T05SMqxAeAps1PjQ-KZEfoTCocflXHT4WbOERB6JoKnAsaRvCEEneVtYSYsjmEcrQiUZzqDHEQ7BMq6gf4LxRh5Whge8NLnrVFFlzrXYj7_vspgrqCRnx856g2IlNNaqllbohft8_Upg3WNre_z7wWvhjkUKBS-SyACCES96OQuFkMQT19VdoZgaHzC-1lC4jUMj4BDHHGj03Ey6v5JUfMHBR8pv';
//                $captcha = '';

        $headers = [
            "Accept"          => "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9",
            "Accept-Encoding" => "gzip, deflate, br",
            "Content-Type"    => "application/x-www-form-urlencoded",
        ];
        $data = [
            "login_challenge" => $login_challenge,
            //                    "Token"           => $captcha,
            "ioBlackBox"           => "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",
            //                    "username"        => $this->AccountFields['Login2'],
            "username"        => $username,
            "is_uuid"         => "1",
            "password"        => $this->AccountFields['Pass'],
        ];

        if ($web_authn_device === true) {
            $data['web_authn_device'] = "0";
            unset($data['is_uuid']);
        }

        $this->http->PostURL('https://account.sainsburys.co.uk/nectar/login', $data, $headers);

        // Great news, your My ID is ready to go
        if ($this->http->currentUrl() == 'https://account.sainsburys.co.uk/nectar/login/my-id-confirmation-mms') {
            throw new CheckRetryNeededException(2, 1);
        }

        if ($this->http->currentUrl() != 'https://account.sainsburys.co.uk/nectar/login/mfa') {
            // https://account.sainsburys.co.uk/login-ui/4738/js/main.3b92e906.chunk.js
            $errorCode = $this->http->FindSingleNode('//div[@id = "root"]/@data-error-code');
            $this->logger->error("[errorCode]: {$errorCode}");

            switch ($errorCode) {
                case '6053':
                    throw new CheckException('That email or password doesn’t look right. Please try again or reset your password.', ACCOUNT_INVALID_PASSWORD);

                    break;

                case '6044':
                    throw new CheckException('Your Nectar account has been locked', ACCOUNT_LOCKOUT);

                    break;

                case '6140':
                    $this->DebugInfo = "6140: need to upd ioBlackBox field"; // refs #21011
                    $this->ErrorReason = self::ERROR_REASON_BLOCK;

//                    throw new CheckException("Unfortunately, there has been an error in the request. Please try again later", ACCOUNT_INVALID_PASSWORD);

                    break;

                case '6050':
                    $this->logger->error("bad captcha answer");

                    break;

                default:
                    if (!empty($errorCode)) {
                        $this->DebugInfo = "[errorCode]: {$errorCode}";

                        return false;
                    }
            }
        }

        if ($this->http->currentUrl() == 'https://account.sainsburys.co.uk/nectar/login/mfa') {
            // <div id="root" data-customer-info="%7B%22email%22%3A%22...22mobile...">
            $info = urldecode($this->http->FindSingleNode('//div[@id = "root"]/@data-customer-info'));
            $this->logger->debug("data-customer-info: {$info}");
            $sentBy = $this->http->JsonLog($info);
            $mobile = $sentBy->mobile ?? null;
            $email = $sentBy->email ?? null;
            $question = "We've sent a code to your phone number ending in {$mobile}";

            if ($mobile == '') {
                if (empty($email)) {
                    $this->logger->error("something went wrong");

                    return false;
                }
                $question = "We've sent a code to your email ending in {$email}";
            }

            // prevent code spam    // refs #6042
            if ($this->isBackgroundCheck()) {
                $this->Cancel();
            }

            $headers = [
                "Accept-Encoding"  => "gzip, deflate, br",
                "Content-Type"     => "application/json",
                "X-Forwarded-From" => "nectar",
            ];
            $this->http->PostURL("https://account.sainsburys.co.uk/nectar/login/send-mfa", [], $this->headers + $headers);
            $response = $this->http->JsonLog();

            if (!isset($response->success) || $response->success != true) {
                $this->logger->error("something went wrong");

                return false;
            }

            $this->Question = $question;
            $this->ErrorCode = ACCOUNT_QUESTION;
            $this->Step = "QuestionEmailVerification";

            return false;
        }

        $code = $this->http->FindPreg("/code=(.+?)&/", false, $this->http->currentUrl());

        if (!$code) {
            // https://account.sainsburys.co.uk/login-ui/4738/js/main.3b92e906.chunk.js
            $errorCode = $this->http->FindSingleNode('//div[@id = "root"]/@data-error-code');
            $this->logger->error("[errorCode]: {$errorCode}");

            switch ($errorCode) {
                case '6053':
                    throw new CheckException('That email or password doesn’t look right. Please try again or reset your password.', ACCOUNT_INVALID_PASSWORD);

                    break;
            }

            $this->logger->debug("[errorCode URL]: {$this->http->currentUrl()}");

            if ($this->http->currentUrl() == 'https://account.sainsburys.co.uk/nectar/login/my-id-mm-email-verification') {
                /*
                    We need to verify your email address
                    Nectar is now using My ID, meaning you can use the same email and password to log in to Nectar and Sainsbury’s Groceries.

                    To get started, we’ve sent a code to your email to verify your email address. Please enter the code to continue logging in:

                    The code is only valid for 10 minutes
                *
                */
                $this->Question = "To get started, we’ve sent a code to your email to verify your email address.";
                $this->ErrorCode = ACCOUNT_QUESTION;
                $this->Step = "QuestionEmailVerification";
            }

            return false;
        }
        // check2fa
                $verificationCode = "Czl5X3b/h46RycKq4Cs6wlJP/uO6pnFl5gAWbAd5wRU+sXZMpZC72q0fD1GD/GvB2koVzE/+YIKTjjsS9MPn9ZH5kqa+kuN7y9UvcGFWtyuO/PJ+mlv7PxOH1ZshHtF3oe69WEIy+EHI6RbCfiotM+OoY580OFZNhVL3KYU8BEAOpGckOS2URXacmoO41zpMtg/Uyn/yJb6BkbW77apma9FUVvys9EoKt7fbntAOR9wfBnnei0hUlNTsa0QN8rCLGwOv7I1kNYqcqXeMKIsxEhNmqw8BToz1HAlL0Qn2wNr2uJ/RG8mhOytIC709OcSK5Cd4ZuceSa446xLGj/d39Q=="; // hard code
                $data = [
                    "code"                        => $code,
                    "redirectUri"                 => "https://www.nectar.com/oauth_redirect",
                    "unencryptedVerificationCode" => false,
                    "verificationCode"            => $verificationCode,
                ];

        $headers = [
            "Accept-Encoding" => "gzip, deflate, br",
            "Content-Type"    => "application/json",
        ];
        $this->http->PostURL("https://www.nectar.com/session-api/sessions/oauth/2fa-check", json_encode($data), $this->headers + $headers);
        $this->http->RetryCount = 2;

        $response = $this->http->JsonLog();
        $encryptedSessionJwt = $response->encryptedSessionJwt ?? null;
        $sentTo = $response->sentTo ?? null;
        $sentBy = $response->sentBy ?? null;

        if (empty($encryptedSessionJwt) || empty($sentTo) || empty($sentBy)) {
            $this->logger->error("something went wrong");

            if (!$response && $this->http->getCookieByName("pianoSession")) {
                return $this->loginSuccessful();
            }

            $message = $response->description ?? null;
            $code = $response->message ?? null;
            // Signin failed. Please try again
            if ($message == "Sorry, something failed." && $code == 'SERVER_ERROR') {
                throw new CheckException('Sign in failed. Please try again', ACCOUNT_PROVIDER_ERROR);
            }

            if ($message == "That email doesn't seem to be registered against that card number - please go back and try again. You can see a hint of the registered email on the log in screen when you go back. (NN_S_178)" && $code == 'CLIENT_ERROR') {
                throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
            }
            // Sorry, there was a problem registering your Nectar card.
            if ($message == "We’re having trouble with your account. For help, go to nectar.com/contact-us (NN_S_136)" && $code == 'CLIENT_ERROR') {
                throw new CheckException("Sorry, there was a problem registering your Nectar card.", ACCOUNT_INVALID_PASSWORD);
            }

            return false;
        }
        $this->State['encryptedSessionJwt'] = $encryptedSessionJwt;
        $question = "We've sent a code to your phone number ending in {$sentTo}";

        if ($sentBy == 'email') {
            $question = "We've sent a code to your email ending in {$sentTo}";
        }
        $this->Question = $question;
        $this->ErrorCode = ACCOUNT_QUESTION;
        $this->Step = "Question";

        return false;
        /*
        }
        /*
            // find an error through card verification
            $status = $response->status ?? null;
            $message =  $response->message ?? null;
            $description =  $response->description ?? null;
            if (
                $this->http->Response['code'] == 500
                && $status == 500
                && $message == "SERVER_ERROR"
                && $description == "Sorry, something failed."
            ){
                $headers = [
                    "Content-Type" => "application/json",
                ];
                $data = [
                    "cardNumber" => $this->AccountFields['Login']
                ];
                $this->http->GetURL("https://www.nectar.com/signin");
                if ($key = $this->http->FindPreg("/window.recaptchaSiteKey=\"([^\"]+)/")) {
                    $captcha = $this->parseCaptcha($key, "https://www.nectar.com/start/existing-nectar-check");
                    $headers['X-Recaptcha'] = "Bearer {$captcha}";
                }

                // Nectar card number
                $this->http->RetryCount = 0;
                $this->http->PostURL("https://www.nectar.com/recaptcha/identity-registration-api/progressive/card-check", json_encode($data), $this->headers + $headers);
                $this->http->RetryCount = 2;
                $response = $this->http->JsonLog();
                $message = $response->description ?? null;
                if (strstr($message,'Sorry, your Nectar account has been closed.')) {
                    $this->captchaReporting();
                    throw new CheckException($message, ACCOUNT_PROVIDER_ERROR);
                }
            }
            return false;
        }
            */

        return $this->checkErrors();
    }

    public function ProcessStep($step)
    {
        if ($step == 'QuestionEmailVerification') {
            $data = [
                "code" => $this->Answers[$this->Question],
            ];
            unset($this->Answers[$this->Question]);
            /*
                accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*
                            /*;q=0.8,application/signed-exchange;v=b3;q=0.9
                content-type: application/x-www-form-urlencoded
                origin: https://account.sainsburys.co.uk
                referer: https://account.sainsburys.co.uk/nectar/login/my-id-mm-email-verification
             */
            $this->http->RetryCount = 0;
            /*
            $this->http->PostURL("https://account.sainsburys.co.uk/nectar/login/my-id-mm-email-verification", $data);
            */
            $this->http->PostURL("https://account.sainsburys.co.uk/nectar/login/mfa", $data);

            // it helps
            if (
                strstr($this->http->Error, 'Network error 56 - Received HTTP code 503 from proxy after CONNECT')
                || strstr($this->http->Error, 'Network error 28 - Operation timed out after')
            ) {
                $this->setProxyBrightData(true, 'static', 'uk');
                $this->http->PostURL("https://account.sainsburys.co.uk/nectar/login/mfa", $data);
            }

            $this->http->RetryCount = 2;
            $code = $this->http->FindPreg("/code=(.+?)&/", false, $this->http->currentUrl());

            if (!$code) {
                $this->logger->error("something went wrong");

                // https://account.sainsburys.co.uk/login-ui/4738/js/main.3b92e906.chunk.js
                $errorCode = $this->http->FindSingleNode('//div[@id = "root"]/@data-error-code');
                $this->logger->error("[errorCode]: {$errorCode}");

                switch ($errorCode) {
                    case '6064':
                        $this->AskQuestion($this->Question, "Your code is incorrect. Please try again or request a new code.", 'QuestionEmailVerification');

                        return false;

                        break;
                }

                return false;
            }

            // check2fa
            $verificationCode = "Czl5X3b/h46RycKq4Cs6wlJP/uO6pnFl5gAWbAd5wRU+sXZMpZC72q0fD1GD/GvB2koVzE/+YIKTjjsS9MPn9ZH5kqa+kuN7y9UvcGFWtyuO/PJ+mlv7PxOH1ZshHtF3oe69WEIy+EHI6RbCfiotM+OoY580OFZNhVL3KYU8BEAOpGckOS2URXacmoO41zpMtg/Uyn/yJb6BkbW77apma9FUVvys9EoKt7fbntAOR9wfBnnei0hUlNTsa0QN8rCLGwOv7I1kNYqcqXeMKIsxEhNmqw8BToz1HAlL0Qn2wNr2uJ/RG8mhOytIC709OcSK5Cd4ZuceSa446xLGj/d39Q=="; // hard code
            $data = [
                "code"                        => $code,
                "redirectUri"                 => "https://www.nectar.com/oauth_redirect",
                "unencryptedVerificationCode" => false,
                "verificationCode"            => $verificationCode,
            ];

            $headers = [
                "Accept-Encoding" => "gzip, deflate, br",
                "Content-Type"    => "application/json",
            ];
            $this->http->RetryCount = 0;
            $this->http->PostURL("https://www.nectar.com/session-api/sessions/oauth/2fa-check", json_encode($data), $this->headers + $headers);
            $this->http->RetryCount = 2;
            $response = $this->http->JsonLog();

            return $this->loginSuccessful();
        }

        $data = [
            "twoFaCode"           => $this->Answers[$this->Question],
            "encryptedSessionJwt" => $this->State['encryptedSessionJwt'],
        ];
        $headers = [
            "Accept-Encoding" => "gzip, deflate, br",
            "Content-Type"    => "application/json",
        ];
        unset($this->Answers[$this->Question]);
        $this->http->RetryCount = 0;
        $this->http->PostURL("https://www.nectar.com/session-api/sessions/oauth/2fa-validate", json_encode($data), $this->headers + $headers);
        $this->http->RetryCount = 2;
        $response = $this->http->JsonLog();

        if (isset($response->description) && $response->description == "Invalid code. Try again or press re-send to be sent a new code.") {
            $this->AskQuestion($this->Question, $response->description, "Question");

            return false;
        }

        return $this->loginSuccessful();
    }

    public function Parse()
    {
        $response = $this->http->JsonLog(null, 0);
        // Name
        $this->SetProperty("Name", beautifulName(($response->firstName ?? null) . " " . ($response->lastName ?? null)));

        $this->http->GetURL("https://www.nectar.com/balance-api/balance", $this->headers);
        $response = $this->http->JsonLog();

        // Balance - You have (Your points total)
        $this->SetBalance($response->current ?? null);
        // Nectar points, worth £15.25
        if (isset($response->currentCurrencyValue)) {
            $this->SetProperty("BalanceWorth", '£' . ($response->currentCurrencyValue / 100));
        }

        // Account #
        $this->http->GetURL("https://www.nectar.com/customer-management-api/customer/card", $this->headers);
        $response = $this->http->JsonLog();
        $this->SetProperty("Number", $response->number ?? null);

        // Expiration date
        $this->http->GetURL("https://www.nectar.com/nectar-shared-transactions-api/transactions?pageSize=20", $this->headers);
        $response = $this->http->JsonLog();
        $items = $response->items ?? [];

        foreach ($items as $item) {
            $lastActivity = strtotime($item->transactionDate);
            $this->SetProperty("LastActivity", date('jS F Y', $lastActivity));

            if ($lastActivity !== false) {
                $this->logger->debug("Last Activity: " . $lastActivity);
                $this->SetExpirationDate(strtotime("+12 month", $lastActivity));
            }// if ($d !== false)

            break;
        }// foreach ($items as $item)
    }

    protected function parseCaptcha($key = null, $currentURL = null)
    {
        $this->logger->notice(__METHOD__);
        $this->logger->debug("data-sitekey: {$key}");

        if (!$key) {
            return false;
        }
        $this->recognizer = $this->getCaptchaRecognizer(self::CAPTCHA_RECOGNIZER_RUCAPTCHA);
        $this->recognizer->RecognizeTimeout = 120;
        $parameters = [
            "pageurl"   => $currentURL ?? $this->http->currentUrl(),
            "proxy"     => $this->http->GetProxy(),
            "invisible" => '1',
        ];

        return $this->recognizeByRuCaptcha($this->recognizer, $key, $parameters);
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);
        $this->http->RetryCount = 0;
        $this->http->GetURL("https://www.nectar.com/customer-management-api/customer", $this->headers, 20);
        $this->http->RetryCount = 2;
        $response = $this->http->JsonLog();

        if (
            $response->email ?? false
            || (
                ($this->AccountFields['Login'] == '35072060017'
                    || $this->AccountFields['Login'] == '85275835038'
                )
                && isset($response->description) && $response->description == 'Sorry, something failed.'
            )// 500 on account page (AccountID: 2958918, 4466406)
        ) {
            return true;
        }

        return false;
    }

    private function enableProxy()
    {
        $this->logger->notice(__METHOD__);

        if ($this->http->Response['code'] == 403) {
            /*
            $proxy = $this->http->getLiveProxy("https://www.nectar.com/login");
            $this->http->SetProxy($proxy);
            */
            $this->http->SetProxy($this->proxyUK());
            $this->http->RetryCount = 1;
            $this->http->GetURL($this->http->currentUrl(), [], 20);
            $this->http->RetryCount = 2;

            $retry = 0;

            while (
                ($this->http->Response['code'] == 403 || strstr($this->http->Error, 'Network error 28 - Operation timed out after'))
                && $retry < 3
            ) {
                $this->setProxyGoProxies(null, 'gb');
                $this->http->RetryCount = 1;
                $this->http->GetURL($this->http->currentUrl(), [], 20);
                $this->http->RetryCount = 2;
                $retry++;
            }
        }
    }
}
