<?php

namespace AwardWallet\Engine\airmilesca;

use AwardWallet\Common\Parsing\Web\Captcha\RucaptchaProvider;
use AwardWallet\ExtensionWorker\AbstractParser;
use AwardWallet\ExtensionWorker\AccountOptions;
use AwardWallet\ExtensionWorker\Credentials;
use AwardWallet\ExtensionWorker\EvaluateOptions;
use AwardWallet\ExtensionWorker\FindTextOptions;
use AwardWallet\ExtensionWorker\LoginResult;
use AwardWallet\ExtensionWorker\LoginWithIdInterface;
use AwardWallet\ExtensionWorker\Message;
use AwardWallet\ExtensionWorker\ParseInterface;
use AwardWallet\ExtensionWorker\QuerySelectorOptions;
use AwardWallet\ExtensionWorker\Tab;
use AwardWallet\Schema\Parser\Component\Master;
use AwardWallet\Schema\Parser\ParserTraits\TextTrait;

class AirmilescaExtension extends AbstractParser implements LoginWithIdInterface, ParseInterface
{
    use TextTrait;

    public function getStartingUrl(AccountOptions $options): string
    {
        return 'https://www.airmiles.ca/en/profile.html';
    }

    public function isLoggedIn(Tab $tab): bool
    {
        $this->logger->info($tab->getUrl());
        $this->logger->notice('[IS SERVER CHECK]: ' . print_r($this->context->isServerCheck(), true));
        $el = $tab->evaluate('
            //input[@id="email"]
            | //span[@id="collector-number"]
            | //p[contains(text(), "Verify you are human by completing the action below.")]
            | //body[contains(text(), "If you need to contact the AIR MILES Reward Program, please contact customer care at")]
        ');

        if ($el->getNodeName() == 'P') {
            $tab->showMessage(Message::MESSAGE_RECAPTCHA);
            $el = $tab->evaluate('//input[@id="email"] | //span[@id="collector-number"]', EvaluateOptions::new()->timeout(180));
        }

        if ($el->getNodeName() == 'BODY') {
            throw new \CheckException($el->getInnerText(), ACCOUNT_PROVIDER_ERROR);
        }

        return $el->getNodeName() == "SPAN";
    }

    public function getLoginId(Tab $tab): string
    {
        return $tab->evaluate('//span[@id="collector-number"]', EvaluateOptions::new()->nonEmptyString())->getInnerText();
    }

    public function login(Tab $tab, Credentials $credentials): LoginResult
    {
        $login = $tab->evaluate('//input[@id="email"]');
        $login->setValue($credentials->getLogin());

        $tab->evaluate('//a[@id="login-continue"]')->click();

        $submitResult = $tab->evaluate('
            //p[contains(@class, "hasError")]
            | //span[contains(@class, "cmp-form-text") and contains(@class, "error")]/span
            | //input[@id="password"]
        ');

        $this->logger->debug("submitResult: " . $submitResult->getNodeName());

        if (
            in_array($submitResult->getInnerText(), ['P', 'SPAN'])
        ) {
            return new LoginResult(false, $submitResult->getInnerText(), null, ACCOUNT_INVALID_PASSWORD);
        }

        $password = $submitResult;
        $password->setValue($credentials->getPassword());
        $tab->logPageState();

        if (
            $this->context->isServerCheck()
        ) {
            $captcha = $this->parseCaptcha($tab);

            if ($captcha !== false) {
                $tab->querySelector('[name="g-recaptcha-response"]', QuerySelectorOptions::new()->visible(false))->setValue($captcha);
            }
        }

        $tab->evaluate('//button[@id="login-form-submit"]')->click();
        $submitResult = $tab->evaluate('
            //p[contains(@class, "hasError")]
            | //p[contains(@class, "error-message")]
            | //span[contains(@class, "cmp-form-text") and contains(@class, "error")]/span
            | //iframe[contains(@title, "recaptcha")]
            | //div[contains(@class, "V2Alert")]//span[text() and not(contains(text(), "Please wait..."))]
            | //span[@id="collector-number"] | //a[@data-track-id="skip-conversion"]
            | //a[@href="/en/profile/convert.html"]
        ', EvaluateOptions::new()->timeout(30));
        $this->logger->debug("submitResult: " . $submitResult->getNodeName());
        $tab->logPageState();

        if (
            $submitResult->getNodeName() == 'IFRAME'
            && $this->context->isServerCheck()
        ) {
            return LoginResult::captchaNotSolved();
        }

        if (
            $submitResult->getNodeName() == 'IFRAME'
            && !$this->context->isServerCheck()
        ) {
            $tab->showMessage(Message::MESSAGE_RECAPTCHA);
            $submitResult = $tab->evaluate('
                //p[contains(@class, "hasError")]
                | //p[contains(@class, "error-message")]
                | //span[contains(@class, "cmp-form-text") and contains(@class, "error")]/span
                | //div[contains(@class, "V2Alert")]//span[text() and not(contains(text(), "Please wait..."))]
                | //span[@id="collector-number"] | //a[@data-track-id="skip-conversion"]
                | //a[@href="/en/profile/convert.html"]
            ', EvaluateOptions::new()->timeout(180)->allowNull(true));

            if (!isset($submitResult)) {
                return LoginResult::captchaNotSolved();
            }
        }

        $this->logger->debug("submitResult: " . $submitResult->getNodeName());

        if (strstr($tab->getUrl(), 'intercept')) {
            $tab->gotoUrl('https://www.airmiles.ca/en/profile.html');

            return new LoginResult(true);
        }

        if (
            $submitResult->getNodeName() == 'P'
            || (
                $submitResult->getNodeName() == 'SPAN'
                && strstr($submitResult->getAttribute('id'), 'collector-number')
            )
        ) {
            return new LoginResult(true);
        } else {
            $error = $submitResult->getInnerText();

            if (
                strstr($error, "The sign-in credentials you've entered do not match our records.")
                || strstr($error, "Your PIN must be only 4 digits.")
                || strstr($error, "What you’ve entered does not match our records. You may not be set up to sign in with your email address. ")
            ) {
                return new LoginResult(false, $error, null, ACCOUNT_INVALID_PASSWORD);
            }

            return new LoginResult(false, $error);
        }
    }

    public function logout(Tab $tab): void
    {
        $this->logger->notice(__METHOD__);
        $tab->evaluate('//a[contains(@href, "logout")]')->click();
        $tab->evaluate('//div[contains(@id, "text")]//a[@href="/en/login.html"]');
    }

    public function parse(Tab $tab, Master $master, AccountOptions $accountOptions): void
    {
        try {
            $json = $tab->fetch('https://bff.api.airmiles.ca/dombff-profile/profile?language=ENGLISH')->body;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return;
        }

        $this->logger->info($json);
        $response = json_decode($json) ?? [];
        $profile = $response->profile;
        $statement = $master->createStatement();

        // Name
        $firstName = $profile->personalDetails->firstName ?? null;
        $lastName = $profile->personalDetails->lastName ?? null;

        if (isset($firstName, $lastName)) {
            $statement->addProperty("Name", beautifulName("{$firstName} {$lastName}"));
        }

        // Collector Number
        if (isset($profile->cardNumber)) {
            $statement->addProperty('Number', $profile->cardNumber);
        }

        // Status
        switch ($profile->tier) {
            case 'B':
                $statement->addProperty('Status', 'Blue');

                break;

            case 'G':
                $statement->addProperty('Status', 'Gold');

                break;

            case 'O':
                $statement->addProperty('Status', 'Onyx®');

                break;

            default:
                $this->notificationSender->sendNotification("refs #25258 airmilesca - need to check status // IZ");
        }

        // You've collected: ... Miles this year
        $from = str_replace('+00:00', '.000Z', date('c', strtotime("-1 year -1 day"))); // 2019-12-14
        $to = str_replace('+00:00', '.000Z', date('c')); // 2020-12-15T18:59:59.999Z

        try {
            $json = $tab->fetch("https://bff.api.airmiles.ca/dombff-contents/services/airmiles/sling/no-cache/transactions?page=1&size=19999&from={$from}&to={$to}&sort=transactionDate,desc&locale=en")->body;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return;
        }
        $this->logger->info($json);
        $response = json_decode($json) ?? [];
        // refs#23697
        $lastActivity = 0;
        $genericActivityDtoList = $response->_embedded->genericActivityDtoList ?? [];

        foreach ($genericActivityDtoList as $activity) {
            $expDate = strtotime($this->findPreg('/^(.+?)T/', $activity->transactionDate));

            if ($expDate && $expDate > $lastActivity) {
                $this->logger->debug("Expiration Date: $expDate");
                $lastActivity = $expDate;
            }
        } // foreach ($genericActivityDtoList as $activity)

        if ($lastActivity > 0) {
            // Last Activity
            $statement->addProperty("LastActivity", date('F j, Y', $lastActivity));
            /*
             * refs #23697 note #11
            // Expiration Date
            $statement->SetExpirationDate(strtotime('+2 year', $lastActivity));
            */
        }

        $expirations = [
            'cashBalance'  => $this->calcalculateExpirationDate($genericActivityDtoList, 'cashBalance'),
            'dreamBalance' => $this->calcalculateExpirationDate($genericActivityDtoList, 'dreamBalance'),
        ];

        if (isset(
            $response->_embedded,
            $response->_embedded->transactionSummary->cashMilesEarned,
            $response->_embedded->transactionSummary->dreamMilesEarned
        )) {
            $statement->addProperty("YTDMiles", $response->_embedded->transactionSummary->cashMilesEarned + $response->_embedded->transactionSummary->dreamMilesEarned);
        }

        // Sub Accounts  // refs #4470

        try {
            $json = $tab->fetch('https://bff.api.airmiles.ca/dombff-profile/services/airmiles/sling/no-cache/member-banner')->body;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return;
        }
        $this->logger->info($json);
        $response = json_decode($json) ?? [];
        $nodes = [
            'Cash Miles'  => 'cashBalance',
            'Dream Miles' => 'dreamBalance',
        ];
        $i = 0;

        foreach ($nodes as $key => $value) {
            $balance = $response->{$value} ?? null;
            $displayName = $key;

            if (isset($balance, $displayName) && ($balance != 0 || $i == 0)) {
                $statement->AddSubAccount([
                    'Code'           => 'airmilesca' . str_replace([' ', 'ê'], ['', 'e'], $displayName),
                    'DisplayName'    => $displayName,
                    'Balance'        => $balance,
                    'ExpirationDate' => $expirations[$value],
                ]);
            } // if (isset($balance))
            else {
                $this->logger->notice("Skip -> {$displayName}: {$balance}");
            }
            $i++;
        } // for ($i = 0; $i < $nodes->length; $i++)

        if (!empty($statement->getSubAccounts())) {
            $statement->setNoBalance(true);
        }

        if ($message = $tab->findText('//title[contains(text(), "We apologize, we are experiencing technical difficulties")]', FindTextOptions::new()->allowNull(true))) {
            throw new \CheckException($message, ACCOUNT_PROVIDER_ERROR);
        }

        $message = $tab->findText('//p[contains(@class, "page_title")]/following-sibling::p[1]', FindTextOptions::new()->allowNull(true));

        if (isset($message) && $this->findPreg("/Until 06:00 on Aug 09 2020,we'll be making some improvements atairmiles\.ca to better serve you in the future\./", $message)) {
            $cleanedMessage = "Until 06:00 on Aug 09 2020, we'll be making some improvements at airmiles.ca to better serve you in the future.";

            throw new \CheckException($cleanedMessage, ACCOUNT_PROVIDER_ERROR);
        }

        if ($tab->findText('//p[contains(normalize-space(),"Until 06:00 on Dec 13 2020,we\'ll be making some improvements atairmiles.ca to better serve you in the future.In the meantime, check out what\'s new")]', FindTextOptions::new()->allowNull(true))) {
            $cleanedMessage = "Until 06:00 on Dec 13 2020, we'll be making some improvements at airmiles.ca to better serve you in the future.";

            throw new \CheckException($cleanedMessage, ACCOUNT_PROVIDER_ERROR);
        }
    }

    private function calcalculateExpirationDate(array $transactions, string $balanceCode)
    {
        if (!in_array($balanceCode, ['cashMiles', 'dreamMiles'])) {
            return null;
        }

        $lastActivity = 0;

        foreach ($transactions as $activity) {
            if ($activity->transactionSummary->{$balanceCode} <= 0) {
                continue;
            }

            $expDate = strtotime($this->findPreg('/^(.+?)T/', $activity->transactionDate));

            if ($expDate && $expDate > $lastActivity) {
                $this->logger->debug("Expiration Date: $expDate");
                $lastActivity = $expDate;
            }
        }

        return strtotime('+2 year', $lastActivity);
    }

    private function parseCaptcha(Tab $tab)
    {
        $this->logger->notice(__METHOD__);
        /*
        $keyElement = $tab->evaluate('//div[@class="g-recaptcha"]', EvaluateOptions::new()->timeout(5)->allowNull(true)->visible(false));

        if (!isset($keyElement)) {
            $this->logger->debug('KEY ELEMENT NOT FOUND');

            return false;
        }

        $key = $keyElement->getAttribute('data-sitekey');
        */
        $key = "6LdhQd4ZAAAAALjx6VSEzBl47vrl4Y0nbrcIRN6u";

        if (!isset($key)) {
            $this->logger->debug('KEY ATTRIBUTE NOT FOUND');

            return false;
        }

        $parameters = [
            "pageurl"   => $tab->getUrl(),
            "invisible" => 1,
            "version"   => "v2",
            "min_score" => 0.9,
        ];

        return $this->captchaServices->recognize($key, RucaptchaProvider::ID, $parameters);
    }
}
