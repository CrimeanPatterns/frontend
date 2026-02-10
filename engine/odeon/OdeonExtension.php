<?php

namespace AwardWallet\Engine\odeon;

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

class OdeonExtension extends AbstractParser implements LoginWithIdInterface, ParseInterface
{
    public function getStartingUrl(AccountOptions $options): string
    {
        return 'https://www.odeon.co.uk/my-account/membership/';
    }

    public function isLoggedIn(Tab $tab): bool
    {
        $el = $tab->evaluate('//input[@name="email"] | //div[contains(@class, "v-member-card-number")]/div[contains(@class, "content")]', EvaluateOptions::new()->allowNull(true)->timeout(60));
        $tab->logPageState();

        if (isset($el)) {
            return $el->getNodeName() == 'DIV';
        }
        $this->logger->debug('no elements found, returning false');

        return false;
    }

    public function getLoginId(Tab $tab): string
    {
        $loginID = $tab->findText('//div[contains(@class, "v-member-card-number")]/div[contains(@class, "content")]', FindTextOptions::new()->nonEmptyString()->preg('/\d+/')->allowNull(true)->timeout(60));
        $tab->logPageState();

        if (isset($loginID)) {
            return $loginID;
        }

        return '';
    }

    public function login(Tab $tab, Credentials $credentials): LoginResult
    {
        $login = $tab->evaluate('//input[@name="email"]');
        $login->setValue($credentials->getLogin());

        $password = $tab->evaluate('//input[@name="password"]');
        $password->setValue($credentials->getPassword());

        $tab->evaluate('//button[@type="submit"]')->click();

        $submitResult = $tab->evaluate('
            //div[contains(@id, "help-text") and contains(@id, "email")]
            | //div[contains(@id, "help-text") and contains(@id, "password")]
            | //div[contains(@id, "help-text") and contains(@id, "captcha")]
            | //div[@class="v-notification-list"]//div[contains(@class, "v-display-text-part") and text() and not(@data-sleek-node-id)]
            | //div[contains(@class, "v-member-card-number")]/div[contains(@class, "content")]
        ', EvaluateOptions::new()->nonEmptyString());

        if (
            strstr($submitResult->getAttribute('id'), 'captcha')
        ) {
            $tab->showMessage(Message::MESSAGE_RECAPTCHA);
            $submitResult = $tab->evaluate('
                //div[contains(@id, "help-text") and contains(@id, "email")]
                | //div[contains(@id, "help-text") and contains(@id, "password")]
                | //span[contains(@class, "v-display-text-part") and text() and not(@data-sleek-node-id)]
                | //div[contains(@class, "v-member-card-number")]/div[contains(@class, "content")]
            ', EvaluateOptions::new()->nonEmptyString());
        }

        if (
            strstr($submitResult->getAttribute('id'), 'email')
            || strstr($submitResult->getAttribute('id'), 'password')
        ) {
            return new LoginResult(false, $submitResult->getInnerText());
        }

        if (
            strstr($submitResult->getAttribute('class'), 'v-display-text-part')
        ) {
            $error = $submitResult->getInnerText();

            if (strstr($error, "something went wrong")) {
                return new LoginResult(false, $error, null, ACCOUNT_PROVIDER_ERROR);
            }

            if (strstr($error, "Details not recognised. Our password security policy has been updated. You may need to set a new password via the Forgot Password link below")) {
                return new LoginResult(false, $error, null, ACCOUNT_INVALID_PASSWORD);
            }

            return new LoginResult(false, $error);
        }

        if (
            strstr($submitResult->getAttribute('class'), 'content')
        ) {
            return new LoginResult(true);
        }

        $tab->logPageState();

        return new LoginResult(false);
    }

    public function parse(Tab $tab, Master $master, AccountOptions $accountOptions): void
    {
        $statement = $master->createStatement();
        $tab->gotoUrl("https://www.odeon.co.uk/my-account/membership/");

        // Name
        $name = $tab->findText('//div[contains(text(), "Name")]/following-sibling::div[not(contains(text(), "cookie"))]', FindTextOptions::new()->nonEmptyString()->timeout(10)->allowNull(true));
        $tab->logPageState();

        if (isset($name)) {
            $statement->addProperty('Name', $name);
        }

        // Membership Number
        $number = $tab->findText('//div[contains(text(), "MEMBERSHIP NUMBER")]/following-sibling::div', FindTextOptions::new()->nonEmptyString()->timeout(10)->allowNull(true)->nonEmptyString()->preg('/\d+/'));

        if (isset($number)) {
            $statement->addProperty('Number', $number);
        }

        // Member since
        /*
        $memberSince = $tab->findText('//li[contains(text(), "Member Since")]/following-sibling::li | //div[contains(text(), "THE CLUB SINCE:")]/following-sibling::div', $defaultOptions);
        */
        $memberSince = $tab->findText('//li[contains(text(), "Member Since")]/following-sibling::li', FindTextOptions::new()->nonEmptyString()->timeout(10)->allowNull(true));

        if (isset($memberSince)) {
            $statement->addProperty('MemberSince', $memberSince);
        }

        // Type of membership
        $TypeOfMembership = $tab->findText('//li[normalize-space() = "Type"]/following-sibling::li', FindTextOptions::new()->nonEmptyString()->timeout(10)->allowNull(true));

        if (isset($memberSince)) {
            $statement->addProperty('TypeOfMembership', $TypeOfMembership);
        }

        if (
            !empty($statement->getProperties()['Name'])
            && !empty($statement->getProperties()['Number'])
            && !empty($statement->getProperties()['MemberSince'])
        ) {
            $statement->setNoBalance(true);
        }
    }

    public function logout(Tab $tab): void
    {
        $this->logger->notice(__METHOD__);
        $tab->evaluate('//a[@class="header-sign-out"]')->click();
        $tab->evaluate('//input[@name="email"]', EvaluateOptions::new()->timeout(10)->allowNull(true));
        $tab->logPageState();
    }
}
