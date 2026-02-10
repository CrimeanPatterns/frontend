<?php

namespace AwardWallet\Engine\landrys;

use AwardWallet\ExtensionWorker\AbstractParser;
use AwardWallet\ExtensionWorker\AccountOptions;
use AwardWallet\ExtensionWorker\Credentials;
use AwardWallet\ExtensionWorker\EvaluateOptions;
use AwardWallet\ExtensionWorker\FindTextOptions;
use AwardWallet\ExtensionWorker\LoginResult;
use AwardWallet\ExtensionWorker\LoginWithIdInterface;
use AwardWallet\ExtensionWorker\Message;
use AwardWallet\ExtensionWorker\Tab;

class LandrysExtension extends AbstractParser implements LoginWithIdInterface
{
    public function getStartingUrl(AccountOptions $options): string
    {
        return 'https://www.landrysselect.com/summary/';
    }

    public function isLoggedIn(Tab $tab): bool
    {
        $this->logger->info($tab->getUrl());
        $el = $tab->evaluate('//a[contains(@href, "login")] | //div[@class="user-info-container"]//h1/span');

        return strstr($el->getNodeName(), "SPAN");
    }

    public function getLoginId(Tab $tab): string
    {
        return $tab->findText('//div[@class="user-info-container"]//h1/span', FindTextOptions::new()->nonEmptyString());
    }

    public function login(Tab $tab, Credentials $credentials): LoginResult
    {
        $tab->gotoUrl('https://web.landrysloyalty.com/authentication/login');

        $login = $tab->evaluate('//app-general-email-input//input');
        $login->setValue($credentials->getLogin());

        $password = $tab->evaluate('//app-general-password-input//input');
        $password->setValue($credentials->getPassword());

        $tab->evaluate('//button[span[contains(text(), "Login")] and not(@disabled)]')->click();

        $submitResult = $tab->evaluate('
            //app-general-password-input//mat-error
            | //app-general-email-input//mat-error
            | //div[@id="toast-container"]//div[contains(@class, "toast-message")]
            | //div[@class="user-info-container"]//h1/span
        ');

        if ($submitResult->getNodeName() == 'SPAN') {
            return new LoginResult(true);
        }

        if ($submitResult->getNodeName() == 'MAT-ERROR') {
            return new LoginResult(false, $submitResult->getInnerText(), null, ACCOUNT_INVALID_PASSWORD);
        }

        if ($submitResult->getNodeName() == 'DIV') {
            $error = $submitResult->getInnerText();

            if (
                strstr($error, "Invalid password or email, please try again or reset")
                || strstr($error, "Invalid password or email, please try again")
            ) {
                return new LoginResult(false, $error, null, ACCOUNT_INVALID_PASSWORD);
            }

            return new LoginResult(false, $error);
        }

        return new LoginResult(false);
    }

    public function logout(Tab $tab): void
    {
        $this->logger->notice(__METHOD__);
        $tab->evaluate('//a[img[contains(@class, "sign-out-img")]]', EvaluateOptions::new()->visible(false))->click();
        sleep(5);
        $tab->logPageState();
        $this->notificationSender->sendNotification('refs #25196 landrys - need to check logout // IZ');
    }
}
