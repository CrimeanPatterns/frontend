<?php

namespace AwardWallet\Engine\airchina;

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

class AirchinaExtension extends AbstractParser implements LoginWithIdInterface, ParseInterface
{
    use TextTrait;

    public function getStartingUrl(AccountOptions $options): string
    {
        return 'https://www.airchina.us/US/GB/booking/account/';
    }

    public function isLoggedIn(Tab $tab): bool
    {
        $this->logger->info($tab->getUrl());
        $tab->logPageState();
        $el = $tab->evaluate('//button[@id="loginPanelBtn"] | //span[@class="membership-information"]//span[@name="ffMasked"] | //span[@aria-label="login"]/..');

        return $el->getNodeName() == "SPAN";
    }

    public function getLoginId(Tab $tab): string
    {
        $loginID = $tab->findText('//span[@class="membership-information"]//span[@name="ffMasked"]', FindTextOptions::new()->nonEmptyString()->allowNull(true)->timeout(10));
        $tab->logPageState();

        return $loginID;
    }

    public function login(Tab $tab, Credentials $credentials): LoginResult
    {
        $tab->logPageState();
        $tab->evaluate('//span[@aria-label="login"]')->click();

        if (filter_var($credentials->getLogin(), FILTER_VALIDATE_EMAIL) === true) {
            $tab->evaluate('//div[contains(@class, "ca-login-dropdown")]')->click();
            $tab->evaluate('//div[contains(@class, "ca-login-dropdown")]//li[@data-value="Email"]')->click();
        }
        $login = $tab->evaluate('//input[@name="loginId"]');
        $login->setValue($credentials->getLogin());
        $password = $tab->evaluate('//input[@name="loginPassword"]');
        $password->setValue($credentials->getPassword());
        $tab->evaluate('//button[@data-tracking-id="loginBtn"]')->click();

        //div[@id="loginCaptchId"]/div[contains(@class, "dx_captcha") and contains(@class, "wrapper")] - old captcha xpath
        $submitResult = $tab->evaluate('
            //form[@id="caRLoginPanel"]//div[contains(@class, "error")]/div[contains(@class, "labels") and not(text()="")]
            | //form[@id="caRLoginPanel"]//span[contains(@class, "error")]/span[contains(@class, "labels") and not(text()="")]
            | //div[contains(@class, "open_popin") and span[@class="ca-v2-login-text-span-name"]]
            | //div[@id="loginCaptchId"]/div[contains(@class, "dx_captcha")]
            | //li[@id="caRLoginDropDown"]
        ', EvaluateOptions::new()->timeout(60)->allowNull(true));
        $tab->logPageState();

        if (
            strstr($submitResult->getAttribute('class'), 'dx_captcha')
        ) {
            $tab->showMessage(Message::MESSAGE_RECAPTCHA);
            $submitResult = $tab->evaluate('
                //form[@id="caRLoginPanel"]//div[contains(@class, "error")]/div[contains(@class, "labels") and not(text()="")]
                | //form[@id="caRLoginPanel"]//span[contains(@class, "error")]/span[contains(@class, "labels") and not(text()="")]
                | //div[contains(@class, "open_popin") and span[@class="ca-v2-login-text-span-name"]]
                | //li[@id="caRLoginDropDown"]
            ', EvaluateOptions::new()->nonEmptyString()->timeout(180)->allowNull(true));
        }
        $tab->logPageState();

        if (!isset($submitResult)) {
            return LoginResult::captchaNotSolved();
        }

        if (
            $submitResult->getNodeName() == 'DIV'
            && strstr($submitResult->getAttribute('class'), "labels")
        ) {
            return new LoginResult(false, $submitResult->getInnerText());
        }

        if (
            $submitResult->getNodeName() == 'SPAN'
            && strstr($submitResult->getAttribute('class'), "labels")
        ) {
            $error = $submitResult->getInnerText();

            if (strstr($error, "Invalid user or password")) {
                return new LoginResult(false, $error, null, ACCOUNT_INVALID_PASSWORD);
            }

            return new LoginResult(false, $error);
        }

        if (
            $submitResult->getNodeName() == 'DIV'
            && strstr($submitResult->getAttribute('class'), "open_popin")
        ) {
            $tab->logPageState();
            $tab->gotoUrl('https://www.airchina.us/US/GB/booking/account/');

            return new LoginResult(true);
        }

        if ($submitResult->getNodeName() == 'LI') {
            $tab->logPageState();
            $tab->gotoUrl('https://www.airchina.us/US/GB/booking/account/');

            return new LoginResult(true);
        }

        return new LoginResult(false);
    }

    public function logout(Tab $tab): void
    {
        $this->logger->notice(__METHOD__);
        $tab->logPageState();
        $tab->evaluate('//li[@id="caRLoginDropDown"]/div')->click();
        $tab->evaluate('//button[@id="logoutBtn"]')->click();
        $tab->evaluate('//span[@aria-label="login"]');
    }

    public function parse(Tab $tab, Master $master, AccountOptions $accountOptions): void
    {
        $statement = $master->createStatement();
        $tab->logPageState();

        // Name
        $name = $tab->findText("//h2[@class = 'capitalize']", FindTextOptions::new()->allowNull(true)->nonEmptyString()->preg("/Hello\s*([^\,]+)/ims")->timeout(10));

        if (isset($name)) {
            $statement->addProperty("Name", beautifulName($name));
        }

        // Member Account Level
        $status = $tab->findText("//span[span[contains(text(), 'Member Account Level')]]/following-sibling::span/span[contains(@class, 'value')]", FindTextOptions::new()->allowNull(true)->nonEmptyString());

        if (isset($status)) {
            $statement->addProperty("Status", $status);
        }

        // Member Account Number
        $number = $tab->findText("//span[span[contains(text(), 'Member Account Number')]]/following-sibling::span/span[contains(@class, 'value')]", FindTextOptions::new()->allowNull(true)->nonEmptyString());

        if (isset($number)) {
            $statement->addProperty("CardNumber", $number);
        }

        // Balance - Useable mileage
        $balance = $tab->findText("//span[span[contains(text(), 'Useable mileage')]]/following-sibling::span/span", FindTextOptions::new()->allowNull(true)->nonEmptyString());

        if (isset($balance)) {
            $statement->SetBalance($balance);
        }

        // Kilometers to next level
        $clubMiles = $tab->findText('//b[contains(text(), "MILEAGES")]', FindTextOptions::new()->allowNull(true)->nonEmptyString()->preg('/[\d,.]+/'));

        if (isset($clubMiles)) {
            $statement->addProperty('ClubMiles', $clubMiles);
        }

        // Segments to next level
        $segments = $tab->findText('//b[contains(text(), "SEGMENTS")]', FindTextOptions::new()->allowNull(true)->nonEmptyString()->preg('/[\d,.]+/'));

        if (isset($segments)) {
            $statement->addProperty('Segments', $segments);
        }

        if (empty($statement->getProperties()['Status'])) {
            // fixed provider bug
            $tab->gotoUrl("https://www.airchina.us/CAPortal/dyn/portal/DisplayPage?COUNTRY_SITE=US&SITE=B000CA00&LANGUAGE=GB&PAGE=ACUI");
            // Member Level
            $status = $tab->findText("//span[contains(text(), 'Member Level:')]/following-sibling::span[1]", FindTextOptions::new()->allowNull(true)->nonEmptyString()->timeout(10));

            if (isset($status)) {
                $statement->addProperty("Status", $status);
            }
        }
    }
}
