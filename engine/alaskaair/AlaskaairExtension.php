<?php

namespace AwardWallet\Engine\alaskaair;

use AwardWallet\ExtensionWorker\AbstractParser;
use AwardWallet\ExtensionWorker\AccountOptions;
use AwardWallet\ExtensionWorker\Credentials;
use AwardWallet\ExtensionWorker\ElementNotFoundException;
use AwardWallet\ExtensionWorker\EvaluateOptions;
use AwardWallet\ExtensionWorker\LoginResult;
use AwardWallet\ExtensionWorker\LoginWithIdInterface;
use AwardWallet\ExtensionWorker\Message;
use AwardWallet\ExtensionWorker\QuerySelectorOptions;
use AwardWallet\ExtensionWorker\SelectFrameOptions;
use AwardWallet\ExtensionWorker\Tab;
use AwardWallet\Schema\Parser\ParserTraits\TextTrait;

class AlaskaairExtension extends AbstractParser implements LoginWithIdInterface
{
    use TextTrait;

    public function getStartingUrl(AccountOptions $options): string
    {
        return 'https://www.alaskaair.com/account/overview?lid=nav%3aaccount%3aprofile&INT-_AS_NAV_-prodID%3aMileagePlan';
    }

    public function isLoggedIn(Tab $tab): bool
    {
        $this->logger->info($tab->getUrl());

        $checkLogin = $tab->evaluate("
            //span[@id='FormUserControl__heading']
            | //p[contains(normalize-space(),'Mileage Plan number:') or contains(normalize-space(),'Mileage Plan #:')]
            | //a[contains(@href, 'ResetPassword')]
            ");

        if (strpos($checkLogin->getInnerText(), "Mileage Plan") !== false) {
            return true;
        }

        $checkLogin = $tab->evaluate('//form//button[@data-action-button-primary="true"]');
        $text = $checkLogin->getInnerText();

        if (stripos($text, 'Sign in') !== false) {
            return false;
        }

        $checkLogin = $tab->querySelector('a#navSignOut', QuerySelectorOptions::new()->visible(false))->getInnerText();

        return strlen($checkLogin) > 0;
    }

    public function getLoginId(Tab $tab): string
    {
        $numberString = $tab->evaluate('(//span[contains(normalize-space(),"Mileage Plan number:")]/ancestor::p[1] | //p[contains(normalize-space(),"Mileage Plan #:")])[1]',
            EvaluateOptions::new()->visible(false)->nonEmptyString())->getInnerText();

        return $this->findPreg('/Mileage Plan (?:Number|#):\s*(\d+)/i', $numberString);
    }

    public function login(Tab $tab, Credentials $credentials): LoginResult
    {
        $login = $tab->evaluate("//form//input[@name='username']");
        $login->setValue($credentials->getLogin());

        $password = $tab->evaluate("//form//input[@name='password']");
        $password->setValue($credentials->getPassword());

        try {
            $signIn = $tab->evaluate("//form//button[@data-action-button-primary='true'] | //form//button[normalize-space()='Sign in']");
        } catch (ElementNotFoundException $e) {
            $signIn = $tab->evaluate("//form//input[@value = 'signInWidget']");
        }

        $recaptcha = $tab->selectFrameContainingSelector("//span[@aria-labelledby='recaptcha-accessible-status']", SelectFrameOptions::new()
            ->method("evaluate")
            ->timeout(3)
            ->allowNull(true));

        if ($recaptcha) {
            $tab->showMessage(Message::MESSAGE_RECAPTCHA);
            $result = $this->waiter->waitFor(function () use ($tab) {
                return $tab->evaluate('//span[contains(normalize-space(),"Mileage Plan number:")]/ancestor::p[1]', EvaluateOptions::new()->allowNull(true)->timeout(90));
            });

            if (!$result) {
                return LoginResult::captchaNotSolved();
            }
        } else {
            $signIn->click();
        }

        $checkAuth = $tab->evaluate('//div[contains(@class,"errorText")]| //div[@data-error-code="authentication-failure"] | //span[contains(normalize-space(),"Mileage Plan number:")]/ancestor::p[1]');

        if ($this->findPreg('/Mileage Plan (?:Number|#):\s*(\d+)/i', $checkAuth->getInnerText())) {
            return new LoginResult(true);
        }

        if ($checkAuth->getNodeName() === 'DIV') {
            return LoginResult::invalidPassword($checkAuth->getInnerText());
        } else {
            $errors = $tab->querySelector('div.errorText');

            return new LoginResult(false, $errors->getInnerText());
        }
    }

    public function logout(Tab $tab): void
    {
        $tab->gotoUrl('https://www.alaskaair.com/www2/ssl/myalaskaair/myalaskaair.aspx?CurrentForm=UCSignOut&lid=signOut');
        $tab->evaluate("//div[@id='FormUserControl']");
    }
}
