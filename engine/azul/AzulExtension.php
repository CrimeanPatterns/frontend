<?php

namespace AwardWallet\Engine\azul;

use AwardWallet\ExtensionWorker\AbstractParser;
use AwardWallet\ExtensionWorker\AccountOptions;
use AwardWallet\ExtensionWorker\Credentials;
use AwardWallet\ExtensionWorker\LoginResult;
use AwardWallet\ExtensionWorker\LoginWithIdInterface;
use AwardWallet\ExtensionWorker\Tab;

class AzulExtension extends AbstractParser implements LoginWithIdInterface
{
    public function getStartingUrl(AccountOptions $options): string // +
    {
        return 'https://www.voeazul.com.br/us/en/fidelity-program';
    }

    public function isLoggedIn(Tab $tab): bool // +
    {
        $this->logger->info($tab->getUrl());
        $el = $tab->evaluate('
            //button[@aria-label="Log in"]
            | //button[contains(@aria-label, "Click to access the customer area")]
        ');
        sleep(3);
        $el = $tab->evaluate('
            //button[@aria-label="Log in"]
            | //button[contains(@aria-label, "Click to access the customer area")]
        ');

        return $el->getAttribute('aria-label') != 'Log in';
    }

    public function getLoginId(Tab $tab): string // +
    {
        return $tab->findText('//div[@class="css-surmsm"]');
    }

    public function login(Tab $tab, Credentials $credentials): LoginResult // +
    {
        $tab->evaluate('//button[@aria-label="Log in"]')->click();
        $tab->evaluate('//input[contains(@data-test-id, "passenger-identification-cpf-or-tudoazul")]')->setValue($credentials->getLogin());
        $tab->evaluate('//input[contains(@data-test-id, "modal-login-password")]')->setValue($credentials->getPassword());
        $tab->evaluate('//button[@data-test-id="login-submit-button" and not(@disabled)]')->click();

        $submitResult = $tab->evaluate('
            //input[contains(@data-test-id, "passenger-identification-cpf-or-tudoazul")]/../../following-sibling::span
            | //input[contains(@data-test-id, "modal-login-password")]/../../following-sibling::span
            | //p[contains(@class, "error")]
            | //div[@class="css-surmsm"]
        ');

        if ($submitResult->getNodeName() == 'DIV') {
            return new LoginResult(true);
        }

        if ($submitResult->getNodeName() == 'SPAN') {
            return new LoginResult(false, $submitResult->getInnerText(), null, ACCOUNT_INVALID_PASSWORD);
        }

        if ($submitResult->getNodeName() == 'P') {
            $error = $submitResult->getInnerText();
            
            if (
                strstr($error, "We were unable to validate your information. Try logging in again.")
            ) {
                return new LoginResult(false, $error, null, ACCOUNT_INVALID_PASSWORD);
            }

            return new LoginResult(false, $error);
        }

        return new LoginResult(false);
    }

    public function logout(Tab $tab): void // +
    {
        $this->logger->notice(__METHOD__);
        $tab->evaluate('//button[div/div[@class="css-surmsm"]]')->click();
        $tab->evaluate('//button[p[contains(text(), "Exit")]]')->click();
        $tab->evaluate('//a[contains(@href, "passagens")]');
    }
}
