<?php

namespace AwardWallet\Engine\pcpoints;

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

class PcpointsExtension extends AbstractParser implements LoginWithIdInterface, ParseInterface
{
    public function getStartingUrl(AccountOptions $options): string
    {
        return 'https://www.pcoptimum.ca/dashboard';
    }

    public function isLoggedIn(Tab $tab): bool
    {
        $el = $tab->evaluate('//section[contains(@class, "your-pco-header-greeting")]/p/span | //input[@name="email"] | //a[@href="/login?hidePageView=true"]');

        if ($el->getNodeName() == 'A') {
            $el->click();

            return false;
        }

        return $el->getNodeName() == "SPAN";
    }

    public function getLoginId(Tab $tab): string
    {
        return $tab->findText('//section[contains(@class, "your-pco-header-greeting")]/p/span', FindTextOptions::new()->nonEmptyString()->preg('/,\s(.*)/i'));
    }

    public function login(Tab $tab, Credentials $credentials): LoginResult
    {
        $tab->evaluate('//input[@id="email"]')->setValue($credentials->getLogin());
        $tab->evaluate('//input[@id="password"]')->setValue($credentials->getPassword());
        $tab->evaluate('//button[contains(@class, "submit-button")]')->click();
        /*
        // old xpath with bad 2fa selection
        $submitResult = $tab->evaluate('
            //label[contains(@id, "email") and contains(@id, "error")]
            | //label[contains(@id, "password") and contains(@id, "error")]
            | //div[contains(@class, "form-message") and contains(@class, "content")]
            | //section[contains(@class, "your-pco-header-greeting")]/p/span
            | //button[contains(text(), "erify")]
        ', EvaluateOptions::new()->allowNull(true)->timeout(20)); // TODO: need to fix 2fa
        */
        $submitResult = $tab->evaluate('
            //label[contains(@id, "email") and contains(@id, "error")]
            | //label[contains(@id, "password") and contains(@id, "error")]
            | //div[contains(@class, "form-message") and contains(@class, "content")]
            | //section[contains(@class, "your-pco-header-greeting")]/p/span
            | //form[@id="mfa-challenge"]
        ', EvaluateOptions::new()->allowNull(true)->timeout(20));
        $tab->logPageState();

        if (!isset($submitResult)) {
            return new LoginResult(false);
        }

        if ($submitResult->getNodeName() == 'LABEL') {
            return new LoginResult(false, $submitResult->getInnerText(), null, ACCOUNT_INVALID_PASSWORD);
        }

        if ($submitResult->getNodeName() == 'DIV') {
            $error = $submitResult->getInnerText();

            if (
                strstr($error, "Our apologies, we're having trouble connecting with the server. Please try refreshing the page, or")
                || strstr($error, "There was an error on our end. Please sign in again to continue.")
            ) {
                return new LoginResult(false, $error, null, ACCOUNT_PROVIDER_ERROR);
            }

            if (
                strstr($error, "Your email or password was incorrect.")
            ) {
                return new LoginResult(false, $error, null, ACCOUNT_INVALID_PASSWORD);
            }

            return new LoginResult(false, $error);
        }

        if (
            $submitResult->getNodeName() == 'FORM'
        ) {
            $loginIDElement = $tab->evaluate('//section[contains(@class, "your-pco-header-greeting")]/p/span',
                EvaluateOptions::new()->nonEmptyString()->timeout(180)->allowNull(true));

            if ($loginIDElement) {
                return new LoginResult(true);
            } else {
                return LoginResult::identifyComputer();
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
        $tab->gotoUrl("https://www.pcoptimum.ca/points");

        $balanceXpath = "
            //div[contains(@class, 'point-summary__inner--balance')]/h2/span
            | //p[span[contains(text(), 'You don’t have any points, but that can change right away by using your personalized offers.')]]
            | //p[span[contains(text(), '’s nice to see you too. Your points balance is at 0, browse your offers feed to start earning points.')]]
        ";
        $balance = $tab->findText($balanceXpath, FindTextOptions::new()->nonEmptyString()->timeout(10)->allowNull(true));
        $tab->logPageState();

        if ($balance && $balance == 'You don’t have any points, but that can change right away by using your personalized offers.') {
            $this->logger->notice("You don’t have any points");
        } else {
            sleep(3);
            $tab->findText($balanceXpath, FindTextOptions::new()->nonEmptyString()->allowNull(true));
            $tab->logPageState();
            $balance1 = $tab->findText("//div[contains(@class, 'point-summary__inner--balance')]/h2/span", FindTextOptions::new()->nonEmptyString()->allowNull(true));

            sleep(2);
            $tab->logPageState();
            $balance2 = $tab->findText("//div[contains(@class, 'point-summary__inner--balance')]/h2/span", FindTextOptions::new()->nonEmptyString()->allowNull(true));
            // refs #18289
            if ($balance1 != $balance2) {
                $this->logger->error("balance may be incorrect");

                return;
            }
        }

        // Current Balance
        $balance = $tab->findText("//div[contains(@class, 'point-summary__inner--balance')]/h2/span", FindTextOptions::new()->nonEmptyString()->allowNull(true));

        if (isset($balance)) {
            $statement->SetBalance($balance);
        } else {
            $noPointsText = $tab->findText("//p[span[contains(text(), 'You don’t have any points, but that can change right away by using your personalized offers.')]]", FindTextOptions::new()->allowNull(true));
            $zeroBalanceText = $tab->findText("//p[span[contains(text(), 's nice to see you too. Your points balance is at 0, browse your offers feed to start earning points.')]]", FindTextOptions::new()->allowNull(true));

            if ($noPointsText) {
                $statement->setNoBalance(true);
            } elseif ($zeroBalanceText) {
                $statement->SetBalance(0);
            }
        }

        $providerError = $tab->findText('//div[contains(@class, "")]/span[contains(text(), "Our apologies, an unknown error has occurred")]', FindTextOptions::new()->allowNull(true));

        if ($providerError) {
            throw new \CheckException($providerError, ACCOUNT_PROVIDER_ERROR);
        }

        // Redeemable value
        $redeemableValue = $tab->findText("//div[contains(@class, 'point-summary__inner--redeemable')]/h2/span", FindTextOptions::new()->nonEmptyString()->allowNull(true));

        if (isset($redeemableValue)) {
            $statement->addProperty("RedeemableValue", $redeemableValue);
        }

        // Expiration Date  // refs #8909
        $lastActivity = $tab->findText("//ul[contains(@class, 'point-events__list')]/li[1]//div[@class = 'point-event__subtitle' and position()> 1]", FindTextOptions::new()->allowNull(true)->preg("/^\w+\s*\•\s*(\w+\s*\d+)/"));
        // Last Activity
        if (isset($lastActivity)) {
            $statement->addProperty("LastActivity", $lastActivity);

            if (strtotime($lastActivity)) {
                $statement->SetExpirationDate(strtotime("+2 year", strtotime($lastActivity)));
            }
        }// if ($lastActivity)

        $menu = $tab->evaluate('//span[@id = "desktop-menu-account"]', EvaluateOptions::new()->allowNull(true));

        if (isset($menu)) {
            $menu->click();
        }
        $tab->gotoUrl("https://www.pcoptimum.ca/account/settings");

        // Name
        $name = $tab->findText("//span[span[contains(text(), 'Full Name')]]/following-sibling::span[@class = 'account-setting__value']", FindTextOptions::new()->allowNull(true)->timeout(10));

        if (isset($name)) {
            $statement->addProperty("Name", beautifulName($name));
        }
    }

    public function logout(Tab $tab): void
    {
        $this->logger->notice(__METHOD__);
        $tab->evaluate('//button[@data-testid="expand-menu-button"]')->click();
        $tab->evaluate('//a[@data-testid="logout-link"]')->click();
        $tab->evaluate('//a[@data-testid="register-link"]');
    }
}
