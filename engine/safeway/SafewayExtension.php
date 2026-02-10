<?php

namespace AwardWallet\Engine\safeway;

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
use DateTime;

class SafewayExtension extends AbstractParser implements LoginWithIdInterface, ParseInterface
{
    use TextTrait;
    private $baseUrl;
    private $loginUrl;

    public function getStartingUrl(AccountOptions $options): string
    {
        $login2 = $options->login2;

        if (!isset($login2) || empty($login2)) {
            $login2 = 'safeway';
        }

        $this->baseUrl = "https://www.{$login2}.com/";
        $this->loginUrl = "{$this->baseUrl}account/sign-in.html";

        return $this->baseUrl;
    }

    public function isLoggedIn(Tab $tab): bool
    {
        $this->logger->info($tab->getUrl());

        if (strstr($tab->getUrl(), 'short-registration')) {
            return false;
        }

        $el = $tab->evaluate('//span[contains(@class, "user-greeting")]');

        if (strstr($el->getInnerText(), "Account")) {
            $this->logout($tab);

            return false;
        }

        return !strstr(strtolower($el->getInnerText()), "sign in");
    }

    public function getLoginId(Tab $tab): string
    {
        $el = $tab->evaluate('//span[contains(@class, "user-greeting") and not(contains(text(), "Sign In / Up")) and not(contains(text(), "Sign in"))]', EvaluateOptions::new()->nonEmptyString());

        return $el->getInnerText();
    }

    public function login(Tab $tab, Credentials $credentials): LoginResult
    {
        $tab->gotoUrl($this->loginUrl);
        $login = $tab->evaluate('//input[@id="enterUsername"]', EvaluateOptions::new()->timeout(30));
        $login->setValue($credentials->getLogin());

        $tab->evaluate('//button[contains(text(),"Sign in with password") and not(@disabled)]')->click();

        $submitResult = $tab->evaluate('
            //input[@id="password"]
            | //div[@id="error-username"]//div[contains(@class, "help-text")]
            | //div[contains(@class, "error") and contains(@class, "alert")]//div[text()]
        ');

        if ($submitResult->getNodeName() == "DIV") {
            return new LoginResult(false, $submitResult->getInnerText(), null, ACCOUNT_INVALID_PASSWORD);
        }

        $password = $submitResult;
        $password->setValue($credentials->getPassword());
        $tab->evaluate('//button[@aria-label="Sign in" and contains(text(), "Sign In")]')->click();
        $submitResult = $tab->evaluate('
            //input[contains(@class, "otp-code")]
            | //span[contains(@class, "user-greeting")]
            | //div[@id="error-pw"]//div[contains(@class, "help-text")]
            | //div[contains(@class, "error") and contains(@class, "alert")]//div[text() and not(@id="errorMsg-pw")]
        ');

        if ($submitResult->getNodeName() == 'INPUT') {
            $tab->showMessage(Message::identifyComputer('Sign in'));
            $el = $tab->evaluate('//span[contains(@class, "user-greeting")]', EvaluateOptions::new()->timeout(120)->allowNull(true));

            if (isset($el)) {
                return new LoginResult(true);
            }

            return LoginResult::captchaNotSolved();
        }

        if ($submitResult->getNodeName() == "SPAN") {
            return new LoginResult(true);
        } elseif ($submitResult->getNodeName() == "DIV") {
            return new LoginResult(false, $submitResult->getInnerText(), null, ACCOUNT_INVALID_PASSWORD);
        } else {
            $error = $submitResult->getInnerText();

            if (
                strstr($error, "The password entered doesn't match our records. Please make sure your info is correct or")
            ) {
                return new LoginResult(false, $error, null, ACCOUNT_INVALID_PASSWORD);
            }

            return new LoginResult(false, $error);
        }
    }

    /* NOTICE: for old login form
    public function login(Tab $tab, Credentials $credentials): LoginResult // +
    {
        $tab->evaluate('//a[@id="sign-in-modal-link"]')->click();

        $login = $tab->evaluate('//input[@id="label-email"]');
        $login->setValue($credentials->getLogin());

        $password = $tab->evaluate('//input[@id="label-password"]');
        $password->setValue($credentials->getPassword());

        $tab->evaluate('//input[@id="btnSignIn"]')->click();

        $submitResult = $tab->evaluate('//div[@id="errorMsgPwd"]//li | //div[@id="errorMsgEmail"]//li | //div[@id="error-message"] | //span[contains(@class, "user-greeting")]');

        if ($submitResult->getNodeName() == "SPAN") {
            return new LoginResult(true);
        } else if($submitResult->getNodeName() == "LI") {
            return new LoginResult(false, $submitResult->getInnerText(), null, ACCOUNT_INVALID_PASSWORD);
        } else {
            $error = $tab->evaluate('//div[@id="error-message"]')->getInnerText();

            if (
                strstr($error, "The email address or password entered doesn't match our records. Please make sure your email is correct or create a new account ")
            ) {
                return new LoginResult(false, $error, null, ACCOUNT_INVALID_PASSWORD);
            }

            return new LoginResult(false, $error);
        }
    }
    */

    public function logout(Tab $tab): void
    {
        $this->logger->notice(__METHOD__);
        $tab->evaluate('//span[contains(@class, "user-greeting")]/..')->click();
        $tab->evaluate('//div[contains(@class, "signout-link")]/a')->click();
        $tab->evaluate('//span[contains(text(), "Sign In / Up") or contains(text(), "Sign in")]');
    }

    public function parse(Tab $tab, Master $master, AccountOptions $accountOptions): void
    {
        $statement = $master->createStatement();
        $tab->gotoUrl("{$this->baseUrl}customer-account/account-settings.html");

        $name = $tab->findText('//div[button[@id="update-fullName"]]/p', FindTextOptions::new()->allowNull(true)->timeout(60));
        $tab->logPageState();

        if (isset($name)) {
            // Name
            $statement->addProperty('Name', beautifulName($name));
        }

        $number = $tab->findText('//p[contains(text(), "Club Card number")]/following-sibling::div/p', FindTextOptions::new()->allowNull(true));

        if (isset($number)) {
            // Club Card Number
            $statement->addProperty('CardNumber', $number);
        }

        $tab->gotoUrl("{$this->baseUrl}customer-account/rewards");
        $lifetimeSavings = $tab->findText('//p[contains(text(), "Lifetime Savings:")]/following-sibling::p', FindTextOptions::new()->allowNull(true)->timeout(60)->preg('/[\d\,\.]+/'));

        if (isset($lifetimeSavings)) {
            // Lifetime Savings
            $statement->addProperty('LifetimeSavings', $lifetimeSavings);
        }

        $savingsToDate = $tab->findText('//p[contains(text(), "2025 Savings to Date:")]/following-sibling::p', FindTextOptions::new()->allowNull(true)->preg('/[\d\,\.]+/'));

        if (isset($savingsToDate)) {
            // [THIS_YEAR] Savings to Date
            $statement->addProperty('YTDSavings', $savingsToDate);
        }

        $balance = $tab->findText('//img[contains(@alt, "Points")]/following-sibling::p', FindTextOptions::new()->allowNull(true)->preg('/[\d\,\.]+/'));

        if (isset($balance)) {
            // Balance - Rewards Points
            $statement->setBalance($balance);
        }

        $expiringBalance = $tab->findText('//p[contains(text(), "Points expire")]', FindTextOptions::new()->allowNull(true)->preg('/(\d+)\sPoints/'));

        if (isset($expiringBalance)) {
            // [EXPIRING BALANCE] Points expire April 30
            $statement->addProperty('ExpiringBalance', $expiringBalance);
        }

        $expirationDate = $tab->findText('//p[contains(text(), "Points expire")]', FindTextOptions::new()->allowNull(true)->preg('/expire\s(.*)/'));

        if (isset($expirationDate)) {
            // 18 Points expire [EXPIRATION DATE]
            $year = date('Y');
            $date = DateTime::createFromFormat('F j Y', "{$expirationDate} {$year}")->getTimestamp();
            $statement->setExpirationDate($date);
        }
    }
}
