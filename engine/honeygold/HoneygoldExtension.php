<?php

namespace AwardWallet\Engine\honeygold;

use AwardWallet\Common\Parsing\Exception\ProfileUpdateException;
use AwardWallet\ExtensionWorker\AbstractParser;
use AwardWallet\ExtensionWorker\AccountOptions;
use AwardWallet\ExtensionWorker\Credentials;
use AwardWallet\ExtensionWorker\EvaluateOptions;
use AwardWallet\ExtensionWorker\FindTextOptions;
use AwardWallet\ExtensionWorker\LoginResult;
use AwardWallet\ExtensionWorker\LoginWithIdInterface;
use AwardWallet\ExtensionWorker\Message;
use AwardWallet\ExtensionWorker\ParseInterface;
use AwardWallet\ExtensionWorker\Tab;
use AwardWallet\Schema\Parser\Component\Master;
use AwardWallet\Schema\Parser\ParserTraits\TextTrait;

class HoneygoldExtension extends AbstractParser implements LoginWithIdInterface, ParseInterface
{
    use TextTrait;
    public const PROVIDER_ERROR_MSG = 'The website is experiencing technical difficulties, please try to check your balance at a later time.';

    public $authType = '';

    public function getStartingUrl(AccountOptions $options): string
    {
        switch ($options->login2) {
            case 'paypal':
                $this->authType = 'paypal';

                break;

            default:
                break;
        }

        return "https://www.joinhoney.com/paypalrewards/browse";
    }

    public function isLoggedIn(Tab $tab): bool
    {
        $result = $tab->evaluate('//span[contains(@id,"header-log-in")] 
        | //div[@id="hamburger-log-in"] 
        | //a[@aria-label="Honey Gold Balance"]
        | //a[@id="Honey Gold"]', EvaluateOptions::new()->visible(false));

        return str_contains($result->getAttribute('aria-label'), 'Honey Gold');
    }

    public function getLoginId(Tab $tab): string
    {
        $tab->gotoUrl('https://www.joinhoney.com/settings');

        return $tab->findTextNullable('//input[@id="Settings:Profile:Email:Input"]/@value',
            FindTextOptions::new()->timeout(15));
    }

    public function logout(Tab $tab): void
    {
        $tab->evaluate('//div[contains(@class,"userProfile-")]', EvaluateOptions::new()->visible(false))->click();
        $tab->evaluate('//li/span[contains(text(),"Log Out")]', EvaluateOptions::new()->visible(false))->click();
        $tab->evaluate('//span[contains(@id,"header-log-in")] | //button[@aria-label="Log in"]', EvaluateOptions::new()->visible(false));

        $logoutMobElm = $tab->evaluate('//div[@id="hamburger-log-out"]',
            EvaluateOptions::new()
                ->allowNull(true));

        if ($logoutMobElm) {
            $logoutMobElm->click();
        }

        if (stristr($tab->getUrl(), '/settings')) {
            $tab->gotoUrl("https://www.joinhoney.com/paypalrewards/browse");
        }
    }

    public function login(Tab $tab, Credentials $credentials): LoginResult
    {
        sleep(3);
        $mobileMenu = $tab->evaluate('//div[@id="hamburger-menu"]',
            EvaluateOptions::new()
                ->allowNull(true));

        if ($mobileMenu) {
            $mobileMenu->click();
        }

        $tab->evaluate('//span[contains(@id,"header-log-in")] | //div[@id="hamburger-log-in"]', EvaluateOptions::new()->visible(false))->click();

        if ($this->authType === 'paypal') {
            $result = $this->loginForPayPal($tab, $credentials);
        } else {
            $result = $this->loginForEmail($tab, $credentials);
        }

        return $result;
    }

    public function loginForPayPal(Tab $tab, Credentials $credentials): LoginResult
    {
        $tab->evaluate('//button[@id="paypalAuth"]')->click();

        $chooseUserElm = $tab->evaluate('//div[@class="ssoInterstitialProfileRememberedEmail"]',
            EvaluateOptions::new()
                ->allowNull(true)
                ->timeout(5));

        if ($chooseUserElm) {
            $tab->showMessage('Please log in with PayPal to continue to Honey');

            return $this->errorOrSuccess($tab);
        }

        $result = $this->waiter->waitFor(function () use ($tab) {
            return $tab->evaluate('//input[@id="email"]', EvaluateOptions::new()->allowNull(true)->timeout(60));
        });

        if ($result) {
            $tab->evaluate('//input[@id="email"]')->setValue($credentials->getLogin());

            $nextBtnElm = $tab->evaluate('//button[@id="btnNext"]', evaluateOptions::new()->allowNull(true));

            if ($nextBtnElm) {
                $nextBtnElm->click();
            }
        }

        $result = $this->waiter->waitFor(function () use ($tab) {
            return $tab->evaluate('//input[@id="password"]', EvaluateOptions::new()->allowNull(true)->timeout(10));
        });

        if ($result) {
            if (!empty($credentials->getPassword())) {
                $tab->evaluate('//input[@id="password"]')->setValue($credentials->getPassword());
                $loginBtnElm = $tab->evaluate('//button[@id="btnLogin"]', EvaluateOptions::new()->allowNull(true));

                if ($loginBtnElm) {
                    $loginBtnElm->click();
                }
            } else {
                $tab->showMessage('The website requires you to log in. Please enter your password into the login form and hit the “Log in” button. After the successful login, you will be redirected to the target page, and we will continue updating your account.');
            }

            $tab->evaluate('//div[@id="captcha-standalone"] | //label[@for="sms-challenge-option"] | //a[@aria-label="Honey Gold Balance"] | //div[@class="verificationFailedSection"]', EvaluateOptions::new()->allowNull(true)->timeout(90));
        }

        $captchaElm = $tab->evaluate('//div[@id="captcha-standalone"]', EvaluateOptions::new()->allowNull(true)->timeout(5));

        if ($captchaElm) {
            $tab->showMessage(TAB::MESSAGE_RECAPTCHA);
            $tab->evaluate('//label[@for="sms-challenge-option"]', EvaluateOptions::new()->allowNull(true)->timeout(10));
        }

        $result = $tab->evaluate('//label[@for="sms-challenge-option"]', EvaluateOptions::new()->allowNull(true)->timeout(10));

        if ($result) {
            $sendSMS = $tab->evaluate('//button[@id="challenge-submit-button"]', EvaluateOptions::new()->allowNull(true));

            if ($sendSMS) {
                $sendSMS->click();
            }

            $smsElm = $tab->evaluate('//p[@data-nemo="smsChallengeDescription"]', EvaluateOptions::new()->allowNull(true)->timeout(5));

            if ($smsElm) {
                $tab->showMessage(Message::identifyComputer('Submit'));
            }
        }

        return $this->errorOrSuccess($tab);
    }

    public function errorOrSuccess(Tab $tab)
    {
        $errorOrSuccess = $tab->evaluate('
            //p[contains(@class, "notification-critical")]
            | //div[@id="keychainErrorMessage"]
            | //section[@id="verification"]/descendant::p[contains(@role, "alert")]
            | //div[contains(@class, "authCTAContainer")]
            | //div[contains(@class, "balanceRemainingGoldAmount")]/preceding::a[1]
            | //a[@aria-label="Honey Gold Balance"] 
            | //a[@id="Honey Gold"] 
            | //div[@data-nemo="verificationFailedPage"]/descendant::H3
            | //div[@class="verificationFailedSection"]',
            EvaluateOptions::new()
                ->timeout(70)
                ->allowNull(true));

        if ($errorOrSuccess) {
            if ($errorOrSuccess->getNodeName() === 'P') {
                return LoginResult::invalidPassword($errorOrSuccess->getInnerText());
            }

            if ($errorOrSuccess->getNodeName() === 'H3') {
                return LoginResult::invalidPassword($errorOrSuccess->getInnerText());
            }

            if ($errorOrSuccess->getNodeName() === 'DIV') {
                return LoginResult::providerError($errorOrSuccess->getInnerText());
            }

            if ($errorOrSuccess->getNodeName() === 'A') {
                return LoginResult::success();
            }
        }

        return new LoginResult(false);
    }

    public function loginForEmail(Tab $tab, Credentials $credentials): LoginResult
    {
        $tab->evaluate('//button[@aria-label="Log in with Email"]', EvaluateOptions::new()->visible(false))->click();

        $loginElm = $tab->evaluate('//input[@id="email-auth-modal"]');

        if ($loginElm) {
            $loginElm->setValue($credentials->getLogin());
        }

        $passwordElm = $tab->evaluate('//input[@id="pwd-auth-modal"]');

        if ($passwordElm) {
            $passwordElm->setValue($credentials->getPassword());
        }

        if (empty($loginElm->getValue())) {
            $loginElm->setValue($credentials->getLogin());
        }

        if (empty($passwordElm->getValue())) {
            $passwordElm->setValue($credentials->getPassword());
        }

        $tab->evaluate('//button[@id="auth-login-modal"]')->click();

        $errorOrSuccess = $tab->evaluate('
        //form//span[contains(text(),"Please enter a valid email.")]
        | //form//span[contains(text(),"Incorrect email and/or password.")]
        | //div[contains(text(),"To help keep your account as secure as possible, we now require all Honey accounts linked with PayPal to use Log in with PayPal.")]
        | //a[@aria-label="Honey Gold Balance"]
        | //div[contains(@class, "balanceRemainingGoldAmount")]/preceding::a[1]
        | //a[@id="Honey Gold"]
        | //div[contains(@class, "notificationCopy") and @role="alert"]',
            EvaluateOptions::new()
                ->timeout(120)
                ->allowNull(true));

        if (empty($errorOrSuccess)) {
            $url = $tab->getUrl();
            $tab->gotoUrl($url);

            $errorOrSuccess = $tab->evaluate('
            //form//span[contains(text(),"Please enter a valid email.")]
            | //form//span[contains(text(),"Incorrect email and/or password.")]
            | //div[contains(text(),"To help keep your account as secure as possible, we now require all Honey accounts linked with PayPal to use Log in with PayPal.")]
            | //a[@aria-label="Honey Gold Balance"]
            | //div[contains(@class, "balanceRemainingGoldAmount")]/preceding::a[1]
            | //a[@id="Honey Gold"]
            | //div[contains(@class, "notificationCopy") and @role="alert"]',
                EvaluateOptions::new()
                    ->timeout(120));
        }

        // To help keep your account as secure as possible, we now require all Honey accounts linked with PayPal to use Log in with PayPal.
        if (str_contains($errorOrSuccess->getInnerText(), 'To help keep your account as secure as possible, we now require all Honey accounts linked with PayPal to use Log in with PayPal.')) {
            throw new ProfileUpdateException();
        }

        if (strstr($errorOrSuccess->getInnerText(), 'You\'ve reached the max number of tries. Check back later.')) {
            return new LoginResult(false, $errorOrSuccess->getInnerText(), null, ACCOUNT_PROVIDER_ERROR);
        }

        if (str_contains($errorOrSuccess->getInnerText(), 'Please enter a valid email.')
            | str_contains($errorOrSuccess->getInnerText(), 'Incorrect email and/or password.')) {
            return LoginResult::invalidPassword($errorOrSuccess->getInnerText());
        }

        if ($errorOrSuccess->getNodeName() === 'A') {
            return LoginResult::success();
        }

        $buttonLoader = $tab->evaluate('//button[@id="auth-login-modal"]//svg[@class="loading-icon"]', EvaluateOptions::new()->allowNull(true));
        $emailInput = $tab->evaluate('//input[@id="email-auth-modal"]', EvaluateOptions::new()->allowNull(true));

        if ($buttonLoader && $emailInput) {
            return LoginResult::providerError(self::PROVIDER_ERROR_MSG);
        }

        $forgotPassword = $tab->evaluate('//a[contains(@class, "forgotPassword")]', EvaluateOptions::new()->allowNull(true));
        $repatcha = $tab->evaluate('//p[contains(normalize-space(), "This site is protected by reCAPTCHA Enterprise and the Google ")]', EvaluateOptions::new()->allowNull(true));

        if ($emailInput && $forgotPassword && $repatcha) {
            return LoginResult::providerError(self::PROVIDER_ERROR_MSG);
        }

        return new LoginResult(false);
    }

    public function parse(Tab $tab, Master $master, AccountOptions $accountOptions): void
    {
        $st = $master->createStatement();
        $token = str_replace('"', '', $tab->getFromLocalStorage('hckey'));
        $this->logger->debug("token -> $token");

        if (!$token) {
            $this->logger->error("token-access not found");

            return;
        }
        $options = [
            'mode'        => 'cors',
            'credentials' => 'include',
            'method'      => 'get',
            'headers'     => [
                "Accept"          => "*/*",
                "Content-Type"    => "application/json",
                "Service-Name"    => "honey-website",
                "Service-Version" => "40.4.1",
                "Csrf-Token"      => $token,
            ],
        ];
        $data = $tab->fetch('https://d.joinhoney.com/v3?operationName=web_getUserById',
            $options)->body;
        $this->logger->info($data);
        $data = json_decode($data);

        $st->addProperty('Name', "{$data->data->getUserById->firstName} {$data->data->getUserById->lastName}");
        $total = 0;

        if (isset($data->data->getUserById->points->pointsAvailable)) {
            $st->setBalance($data->data->getUserById->points->pointsAvailable);
            $total = $data->data->getUserById->points->pointsAvailable;

            // Lifetime PayPal Honey Savings
            $st->addProperty('LifetimePayPal', $data->data->getUserById->lifetimeSaving == null
                ? '$0.00' : $data->data->getUserById->lifetimeSaving->lifetimeSavingInUSD);

            // Pending
            if (isset($data->data->getUserById->points->pointsPendingDeposit)) {
                $st->addProperty('Pending', $data->data->getUserById->points->pointsPendingDeposit);
                $total = $total + $data->data->getUserById->points->pointsPendingDeposit;
            }

            // Lifetime Points worths
            if (isset($data->data->getUserById->points->pointsRedeemed)) {
                $total = $total + $data->data->getUserById->points->pointsRedeemed;
            }
            $st->addProperty('Total', '$' . ($total / 100));
            $this->logger->debug('Lifetime Points worths: ' . $st->getProperties()['Total']);
        } elseif ($data->data->getUserById->points === null) {
            $st->setBalance(0);
        }
    }
}
