<?php

namespace AwardWallet\Engine\columbia;

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

class ColumbiaExtension extends AbstractParser implements LoginWithIdInterface, ParseInterface
{
    public function getStartingUrl(AccountOptions $options): string
    {
        return 'https://www.columbia.com/membership';
    }

    public function isLoggedIn(Tab $tab): bool
    {
        $tab->evaluate('//input[@id="gateway-form-email"] | //div[@data-action="Membership-Dashboard"]//h2[contains(text(), ",")]');
        $el = $tab->evaluate('//input[@id="gateway-form-email"] | //div[@data-action="Membership-Dashboard"]//h2[contains(text(), ",")]', EvaluateOptions::new()->timeout(60)->allowNull(true));

        if (!isset($el)) {
            $tab->logPageState();

            return false;
        }

        return $el->getNodeName() == 'H2';
    }

    public function getLoginId(Tab $tab): string
    {
        $tab->gotoUrl('https://www.columbia.com/membership');

        return $tab->findText('//div[@data-action="Membership-Dashboard"]//h2[contains(text(), ",")]', FindTextOptions::new()->nonEmptyString()->preg('/,\s(.*)/'));
    }

    public function login(Tab $tab, Credentials $credentials): LoginResult
    {
        $tab->evaluate('//input[@id="gateway-form-email"]', EvaluateOptions::new()->timeout(10)->allowNull(true));
        $tab->logPageState();
        $tab->evaluate('//input[@id="gateway-form-email"]')->setValue($credentials->getLogin());
        $tab->evaluate('//button[@type="submit" and not(contains(@class, "disabled")) and contains(text(), "Continue")]')->click();
        $submitResult = $tab->evaluate('//form[contains(@class, "registration")] | //input[@name="loginPassword"]');

        if ($submitResult->getNodeName() == 'FORM') {
            return new LoginResult(false, 'Invalid login or password', null, ACCOUNT_INVALID_PASSWORD);
        }

        $submitResult->setValue($credentials->getPassword());
        $tab->evaluate('//button[@type="submit" and not(contains(@class, "disabled")) and contains(text(), "Log in")]')->click();
        $submitResult = $tab->evaluate('//div[contains(@class, "alert")]/div[text()] | //div[@data-action="Membership-Dashboard"]//h2[contains(text(), ",")] | //div[contains(text(), "Welcome")]', EvaluateOptions::new()->nonEmptyString());

        if (
            $submitResult->getNodeName() == 'DIV'
            && strstr($submitResult->getInnerText(), "You are now logged in")
        ) {
            return new LoginResult(true);
        }

        if ($submitResult->getNodeName() == 'DIV') {
            $error = $submitResult->getInnerText();

            if (strstr($error, "Invalid login or password. Remember that password is case-sensitive. Please try again or")) {
                return new LoginResult(false, $error, null, ACCOUNT_INVALID_PASSWORD);
            }

            return new LoginResult(false, $error);
        }

        if (
            $submitResult->getNodeName() == 'H2'
        ) {
            return new LoginResult(true);
        }

        return new LoginResult(false);
    }

    public function parse(Tab $tab, Master $master, AccountOptions $accountOptions): void
    {
        // TODO: need to find lost property SpendToNextTier
        $statement = $master->createStatement();
        $balance = $tab->findText('//div[contains(@class, "rewards__tracker-balance")] | //p[contains(text(), "Rewards Balance:")]/following-sibling::p', FindTextOptions::new()->nonEmptyString()->timeout(10)->allowNull(true)->preg('/[\d\,\.]+/'));
        $tab->logPageState();

        if (isset($balance)) {
            // Balance - Rewards Balance
            $statement->SetBalance($balance);
        }

        $name = $tab->findText('//div[@data-action="Membership-Dashboard"]//h2[contains(text(), ",")]', FindTextOptions::new()->nonEmptyString()->allowNull(true)->preg('/,\s(.*)/'));

        if (isset($name)) {
            // Name
            $statement->addProperty('Name', $name);
        }
    }

    public function logout(Tab $tab): void
    {
        $this->logger->notice(__METHOD__);

        try {
            $tab->fetch('https://www.columbia.com/on/demandware.store/Sites-Columbia_US-Site/en_US/Login-Logout');
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
        $tab->gotoUrl('https://www.columbia.com/membership');
        $tab->evaluate('//input[@id="gateway-form-email"]');
    }
}
