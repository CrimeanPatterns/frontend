<?php

namespace AwardWallet\Engine\hongkongairlines;

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

class HongkongairlinesExtension extends AbstractParser implements LoginWithIdInterface, ParseInterface
{
    public function getStartingUrl(AccountOptions $options): string
    {
        return 'https://www.hainanairlines.com/US/US/Home';
    }

    public function isLoggedIn(Tab $tab): bool
    {
        $el = $tab->evaluate('//span[@id="loggedin_ffNumber"] | //a[contains(@href, "register") and span[contains(text(), "Register")]]', EvaluateOptions::new()->visible(false)->nonEmptyString());

        return $el->getNodeName() == 'SPAN';
    }

    public function getLoginId(Tab $tab): string
    {
        return $tab->findText('//span[@id="loggedin_ffNumber"]', FindTextOptions::new()->visible(false)->nonEmptyString());
    }

    public function login(Tab $tab, Credentials $credentials): LoginResult
    {
        $tab->evaluate('//li//a[contains(@class, "login-link")] | //a[img[@alt="Login user"]]');
        sleep(3); // prevent page crush
        $tab->evaluate('//li//a[contains(@class, "login-link")] | //a[img[@alt="Login user"]]')->click();
        $tab->evaluate('//input[@name="login"]');
        sleep(3); // prevent page crush
        $tab->evaluate('//input[@name="login"]')->setValue($credentials->getLogin());
        $tab->evaluate('//input[@name="password"]')->setValue($credentials->getPassword());
        $tab->evaluate('//footer/button[@type="submit"]')->click();
        $submitResult = $tab->evaluate('
            //input[@name="login"]/following-sibling::div//em[@class="form-error"]
            | //input[@name="login"]/following-sibling::div//em[@class="form-error"]
            | //a[@class="wdk-errorpanel-link"]
            | //span[contains(@class, "loggedin")]//span[contains(@class, "capital")]
            | //span[@id="loggedin_ffNumber"]
            | //div[@id="loginCaptchaLM"]
        ', EvaluateOptions::new()->visible(false));

        if ($submitResult->getNodeName() == 'DIV') {
            $tab->showMessage(Message::MESSAGE_RECAPTCHA);
            $submitResult = $tab->evaluate('
                //input[@name="login"]/following-sibling::div//em[@class="form-error"]
                | //input[@name="login"]/following-sibling::div//em[@class="form-error"]
                | //a[@class="wdk-errorpanel-link"]
                | //span[contains(@class, "loggedin")]//span[contains(@class, "capital")]
                | //span[@id="loggedin_ffNumber"]
            ', EvaluateOptions::new()->timeout(180));
        }

        if ($submitResult->getNodeName() == 'EM') {
            return new LoginResult(false, $submitResult->getInnerText());
        }

        if ($submitResult->getNodeName() == 'A') {
            $error = $submitResult->getInnerText();

            if (
                strstr($error, "Can't find a valid user")
                || strstr($error, "The password incorrect. your account will be locked after 5 incorrect passwords.")
            ) {
                return new LoginResult(false, $error, null, ACCOUNT_INVALID_PASSWORD);
            }
        }

        if ($submitResult->getNodeName() == 'SPAN') {
            return new LoginResult(true);
        }

        return new LoginResult(false);
    }

    public function parse(Tab $tab, Master $master, AccountOptions $accountOptions): void
    {
        $statement = $master->createStatement();
        // Name
        $name = $tab->findText('//span[contains(@class, "loggedin")]//span[contains(@class, "capital")]', FindTextOptions::new()->nonEmptyString()->timeout(60)->allowNull(true)->visible(false));
        $tab->logPageState();

        if (isset($name)) {
            $statement->addProperty('Name', $name);
        }

        // Member Status
        $status = $tab->findText("//span[@id = 'status_label']", FindTextOptions::new()->nonEmptyString()->allowNull(true)->visible(false));
        $tab->logPageState();

        if (isset($status)) {
            $statement->addProperty('Status', $status);
        }

        // Member No.
        $number = $tab->findText("//span[@id = 'loggedin_ffNumber']", FindTextOptions::new()->nonEmptyString()->allowNull(true)->visible(false));
        $tab->logPageState();

        if (isset($number)) {
            $statement->addProperty('MemberNo', $number);
        }

        // Points balance
        $balance = $tab->findText("//span[@id = 'points_balanced']", FindTextOptions::new()->nonEmptyString()->allowNull(true)->visible(false));
        $tab->logPageState();

        if (isset($balance)) {
            $statement->setBalance($balance);
        }
    }

    public function logout(Tab $tab): void
    {
        $this->logger->notice(__METHOD__);
        $tab->evaluate('//a[@name="topLogoutLink"]', EvaluateOptions::new()->visible(false))->click();
        $tab->evaluate('//a[contains(@href, "register")]/span[contains(text(), "Register")]', EvaluateOptions::new()->nonEmptyString()->allowNull(true)->timeout(10));
    }
}
