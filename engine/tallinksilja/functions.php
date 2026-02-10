<?php

class TAccountCheckerTallinksilja extends TAccountChecker
{
    use \AwardWallet\Common\OneTimeCode\OtcHelper;

    private $headers = [
        'Accept'       => 'application/json',
        'Content-Type' => 'application/json;charset=UTF-8',
    ];

    public function IsLoggedIn()
    {
        return $this->loginSuccessful();
    }

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
        $this->http->setHttp2(true);
    }

    public function LoadLoginForm()
    {
        if (!isset($this->AccountFields['Login2'], $this->AccountFields['Login3'])) {
            throw new CheckException('We couldn’t find your account, try to use different details.', ACCOUNT_INVALID_PASSWORD);
        }

        $this->http->removeCookies();
        $this->http->GetURL("https://clubone.tallink.com/");

        if (!$this->http->FindSingleNode('//title[contains(text(), "Club One")]')) {
            return false;
        }

        $data = [
            "email"    => $this->AccountFields["Login"],
            "language" => "en-US",
        ];

        $this->http->RetryCount = 0;
        $this->http->PostURL('https://sso.tallink.com/api/login', json_encode($data), $this->headers);
        $response = $this->http->JsonLog();

        $message = $response->message ?? null;

        if (isset($message)) {
            if (
                strstr($message, "CLIENT_NOT_FOUND")
                || strstr($message, "EMAIL_NOT_VALID")
            ) {
                throw new CheckException('We couldn’t find your account, try to use different details.', ACCOUNT_INVALID_PASSWORD);
            }

            $this->DebugInfo = $message;

            return false;
        }

        if (!isset($response->requiredFields)) {
            return $this->CheckErrors();
        }

        $data = [
            'email'	    => $this->AccountFields['Login'],
            'firstName'	=> strtoupper($this->AccountFields['Login2']),
            'lastName'	 => strtoupper($this->AccountFields['Login3']),
            'language'	 => "en-US",
        ];

        $this->http->PostURL('https://sso.tallink.com/api/login', json_encode($data), $this->headers);
        $this->http->RetryCount = 2;

        return true;
    }

    public function Login()
    {
        if ($this->processQuestion()) {
            return false;
        }

        $response = $this->http->JsonLog();

        if (isset($response->requiredFields)) {
            throw new CheckException('We couldn’t find your account, try to use different details.', ACCOUNT_INVALID_PASSWORD);
        }

        $message = $response->message ?? null;

        if (isset($message)) {
            if (
                strstr($message, "CLIENT_NOT_FOUND")
                || strstr($message, "EMAIL_NOT_VALID")
            ) {
                throw new CheckException('We couldn’t find your account, try to use different details.', ACCOUNT_INVALID_PASSWORD);
            }

            $this->DebugInfo = $message;

            return false;
        }

        return $this->checkErrors();
    }

    public function ProcessStep($step)
    {
        $answer = $this->Answers[$this->Question];
        $authID = $this->State['authID'] ?? null;
        $otpMethod = $this->State['otpMethod'] ?? null;

        unset($this->Answers[$this->Question]);

        if (!isset($authID, $otpMethod)) {
            return $this->CheckErrors();
        }

        $data = [
            'authId'         => $authID,
            'originalMethod' => strtolower($otpMethod),
            'otp'            => $answer,
        ];

        $this->http->PostURL('https://sso.tallink.com/api/login', json_encode($data), $this->headers);
        $response = $this->http->JsonLog();

        if (
            isset($response->authId)
            && !isset($response->token)
        ) {
            /*
            $this->sendNotification('refs #2618 tallinksilja - need to check invalid code // IZ');
            */
            $this->AskQuestion($this->Question, 'The code is incorrect. Check the code and try again', 'Question');

            return false;
        }

        if ($this->loginSuccessful()) {
            unset($this->State['authID']);
            unset($this->State['otpMethod']);

            return true;
        }

        return $this->CheckErrors();
    }

    public function Parse()
    {
        $response = $this->http->JsonLog();
        $balance = $response->loyalty->points ?? null;

        if (isset($balance)) {
            // Balance - Bonusa punkti
            $this->SetBalance($balance);
            // Bonus Points, duplicate for elite
            $this->SetProperty('BonusPoints', $balance);
        }

        $status = $response->loyalty->level ?? null;

        if (isset($status)) {
            // Club One kartes līmenis
            $this->SetProperty('Status', beautifulName($status));
        }

        $number = $response->loyalty->number ?? null;

        if (isset($number)) {
            // Kartes numurs
            $this->SetProperty('AccountNumber', $number);
        }

        $fullName = $response->fullName ?? null;

        if (isset($fullName)) {
            // Name window.clientFullName = 'FirstName LastName';
            $this->SetProperty('Name', beautifulName($fullName));
        }
    }

    private function processQuestion()
    {
        /*
        if ($this->getWaitForOtc()) {
            $this->sendNotification('refs #2618 tallinksilja - user with mailbox was found // IZ');
        }
        */

        if ($this->isBackgroundCheck() && !$this->getWaitForOtc()) {
            $this->Cancel();
        }

        $response = $this->http->JsonLog();

        if (!isset($response->otpMethod)) {
            return false;
        }

        if (!isset($response->authId)) {
            return $this->CheckErrors();
        }

        $this->State['authID'] = $response->authId;
        $this->State['otpMethod'] = $response->otpMethod;
        $this->AskQuestion('A code has been sent to this e-mail - ' . $this->AccountFields['Login'] . '. Check your messages and type in the code here.', null, 'Question');

        return true;
    }

    private function loginSuccessful()
    {
        $response = $this->http->JsonLog();

        if (!isset($response) && !isset($this->State['token'])) {
            return false;
        }

        if (isset($response->token)) {
            $token = $response->token;
        } else {
            $token = $this->State['token'];
        }

        $headers = array_merge(
            $this->headers,
            [
                'token'           => $token,
                'Accept-Language' => 'en-XZ',
            ]
        );

        $this->http->GetURL('https://cms-web-api.tallink.com/api/torpedo/clients/', $headers);
        $response = $this->http->JsonLog();

        if (
            isset($response->email)
            && $response->email === $this->AccountFields["Login"]
        ) {
            $this->State['token'] = $token;
            $this->http->setDefaultHeader("token", $token);
            $this->http->setDefaultHeader('Accept-Language', 'en-XZ');

            return true;
        }

        return false;
    }

    private function CheckErrors()
    {
        return false;
    }
}
