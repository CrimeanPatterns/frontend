<?php

namespace AwardWallet\Engine\paybackgerman;

use AwardWallet\ExtensionWorker\AbstractParser;
use AwardWallet\ExtensionWorker\AccountOptions;
use AwardWallet\ExtensionWorker\Credentials;
use AwardWallet\ExtensionWorker\EvaluateOptions;
use AwardWallet\ExtensionWorker\FindTextOptions;
use AwardWallet\ExtensionWorker\LoginResult;
use AwardWallet\ExtensionWorker\LoginWithIdInterface;
use AwardWallet\ExtensionWorker\ParseInterface;
use AwardWallet\ExtensionWorker\Tab;
use AwardWallet\Schema\Parser\Component\Master;
use AwardWallet\Schema\Parser\ParserTraits\TextTrait;

class PaybackgermanExtensionAustria extends AbstractParser implements LoginWithIdInterface, ParseInterface
{
    use TextTrait;

    public function getStartingUrl(AccountOptions $options): string
    {
        return "https://www.payback.at/meine-daten";
    }

    public function isLoggedIn(Tab $tab): bool
    {
        $result = $tab->evaluate('
        //a[contains(@class,"pb-navigation__link_login")] 
        | //span[@class="pb-profile-overview__card-info-label"]
        | //input[@name="secret"]
        ');

        return $result && str_contains($result->getAttribute('class'), 'pb-profile-overview__card-info-label');
    }

    public function getLoginId(Tab $tab): string
    {
        return $tab->findText('//span[@data-locator = "cardNumberBig"] | //span[@class="pb-profile-overview__card-info-number"]');
    }

    public function logout(Tab $tab): void
    {
        $tab->evaluate('//a[contains(@href,"?:action=Logout")]', EvaluateOptions::new()->visible(false))->click();
        $tab->evaluate('//a[contains(@class,"pb-navigation__link_login")]');
    }

    public function login(Tab $tab, Credentials $credentials): LoginResult
    {
        $tab->evaluate('//form[@name="Login"]//input[@name="alias"]')->setValue($credentials->getLogin());
        $tab->evaluate('//form[@name="Login"]//input[@name="secret"]')->setValue($credentials->getPassword());
        $tab->evaluate('//form[@name="Login"]//input[contains(@name,"login-button-")]')->click();

        $errorOrSuccess = $tab->evaluate('//p[contains(@class,"pb-alert-content__message")] 
        | //span[contains(@class,"pb-account-details__card-holder-name")]
        | //h2[contains(@class,"pb-headline pb-headline_h2")]');

        if (str_contains($errorOrSuccess->getInnerText(),
            'Ihre Eingabe war nicht erfolgreich, bitte versuchen Sie es erneut.')) {
            return LoginResult::invalidPassword($errorOrSuccess->getInnerText());
        }

        if (str_contains($errorOrSuccess->getAttribute('class'), 'pb-headline_h2')) {
            return new LoginResult(true);
        }

        return new LoginResult(false);
    }

    public function parse(Tab $tab, Master $master, AccountOptions $accountOptions): void
    {
        $st = $master->createStatement();
        // Name
        $st->addProperty('Name', $tab->findText('//h2[contains(@class,"pb-headline pb-headline_h2")]',
            FindTextOptions::new()
                ->preg("/^\w+\s+([[:alpha:]][-.\'[:alpha:] ]*[[:alpha:]])\,/")));

        $tab->gotoUrl("https://www.payback.at/punktestand");
        // Balance - Ihr aktueller Punktestand
        $st->setBalance($tab->findText('//div[contains(@class,"pb-account-details__points-area-value")]/text()',
            FindTextOptions::new()->preg('/[\d.,]+/')->pregReplace('/\./', '')));
    }
}
