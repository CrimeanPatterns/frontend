<?php

namespace AwardWallet\Engine\atlanticairways;

use AwardWallet\ExtensionWorker\AbstractParser;
use AwardWallet\ExtensionWorker\AccountOptions;
use AwardWallet\ExtensionWorker\Credentials;
use AwardWallet\ExtensionWorker\FindTextOptions;
use AwardWallet\ExtensionWorker\LoginResult;
use AwardWallet\ExtensionWorker\LoginWithIdInterface;
use AwardWallet\ExtensionWorker\ParseInterface;
use AwardWallet\ExtensionWorker\Tab;
use AwardWallet\Schema\Parser\Component\Master;

class AtlanticairwaysExtension extends AbstractParser implements LoginWithIdInterface, ParseInterface
{
    public function getStartingUrl(AccountOptions $options): string
    {
        return 'https://www.atlanticairways.com/en/s%C3%BAlubonus';
    }

    public function isLoggedIn(Tab $tab): bool
    {
        $el = $tab->evaluate('//input[@name="login_email_address"] | //span[@ng-bind="userService.user.bonusNumber"]');

        return $el->getNodeName() == 'SPAN';
    }

    public function getLoginId(Tab $tab): string
    {
        return $tab->findText('//span[@ng-bind="userService.user.bonusNumber"]');
    }

    public function login(Tab $tab, Credentials $credentials): LoginResult
    {
        $tab->evaluate('//input[@name="login_email_address"]')->setValue($credentials->getLogin());
        $tab->evaluate('//input[@name="login_password"]')->setValue($credentials->getPassword());
        $tab->evaluate('//button[@x-translate="loyalty.login_submit_button_label"]')->click();
        $submitResult = $tab->evaluate('
            //div[@ng-show="loginError"]
            | //span[@ng-bind="userService.user.bonusNumber"]
        ');

        if ($submitResult->getNodeName() == 'DIV') {
            $error = $submitResult->getInnerText();

            if (strstr($error, "Please make sure that your username and password is correct and try again.")) {
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
        $balance = $tab->findText('//div[@class="member-points-text"]//span[contains(@ng-bind, "userService.user.pointBalance")]', FindTextOptions::new()->nonEmptyString()->timeout(20)->allowNull(true));

        if (isset($balance)) {
            // You have 1000 points
            $statement->SetBalance($balance);
        }

        $firstName = $tab->findText('//span[@ng-bind="userService.user.firstName"]', FindTextOptions::new()->nonEmptyString()->allowNull(true));
        $lastName = $tab->findText('//span[@ng-bind="userService.user.lastName"]', FindTextOptions::new()->nonEmptyString()->allowNull(true));

        if (isset($firstName, $lastName)) {
            // Welcome home
            $statement->addProperty('Name', beautifulName("{$firstName} {$lastName}"));
        }

        $number = $tab->findText('//span[@ng-bind="userService.user.bonusNumber"]', FindTextOptions::new()->nonEmptyString()->allowNull(true));

        if (isset($number)) {
            // Bonus number
            $statement->addProperty('BonusNumber', $number);
        }
    }

    public function logout(Tab $tab): void
    {
        $tab->evaluate('//div[@x-translate="loyalty.logout"]')->click();
        $tab->evaluate('//input[@name="login_email_address"]');
    }
}
