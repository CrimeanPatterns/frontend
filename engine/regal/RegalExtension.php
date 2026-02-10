<?php

namespace AwardWallet\Engine\regal;

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

class RegalExtension extends AbstractParser implements LoginWithIdInterface, ParseInterface
{
    public function getStartingUrl(AccountOptions $options): string
    {
        return 'https://www.regmovies.com/account';
    }

    public function isLoggedIn(Tab $tab): bool
    {
        $this->logger->info($tab->getUrl());

        $el = $tab->evaluate('//img[@alt="Account Card"]/following-sibling::div/h3 | //input[@id="login-username-input"]');

        return $el->getNodeName() == "H3";
    }

    public function getLoginId(Tab $tab): string
    {
        return $tab->evaluate('//img[@alt="Account Card"]/following-sibling::div/h3', EvaluateOptions::new()->nonEmptyString())->getInnerText();
    }

    public function login(Tab $tab, Credentials $credentials): LoginResult
    {
        $login = $tab->evaluate('//input[@id="login-username-input"]');
        $login->setValue($credentials->getLogin());

        $password = $tab->evaluate('//input[@id="password-input-login-component"]');
        $password->setValue($credentials->getPassword());

        $tab->evaluate('//button[@id="login-component-submit"]')->click();

        $submitResult = $tab->evaluate('//label[@for="password"]//p[contains(@class, "error")] | //label[@for="username"]//p[contains(@class, "error")] | //form/div/p[text()] | //img[@alt="Account Card"]/following-sibling::div/h3', EvaluateOptions::new()->timeout(30));

        if ($submitResult->getNodeName() == 'H3') {
            return new LoginResult(true);
        } elseif ($submitResult->getNodeName() == 'P') {
            $error = $submitResult->getInnerText();

            if (
                strstr($error, "Username or Password is incorrect")
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
        $tab->evaluate('//button[@id="account-button"]')->click();
        $tab->evaluate('//button[@id="logout"]')->click();
    }

    public function parse(Tab $tab, Master $master, AccountOptions $accountOptions): void
    {
        $tab->logPageState();
        $statement = $master->createStatement();

        $name = $tab->findText('//img[@alt="Account Card"]/following-sibling::div/h3', FindTextOptions::new()->nonEmptyString()->allowNull(True)->timeout(30));
        if (isset($name)) {
            // Name
            $statement->addProperty('Name', $name);
        }

        $balance = $tab->findText('//h4[contains(text(), "Regal Crown Club")]/../h3', FindTextOptions::new()->nonEmptyString()->allowNull(True));
        if (isset($balance)) {
            // Balance - Credit Balance
            $statement->SetBalance($balance);
        }
 
        $expiringBalance = $tab->findText('//span[contains(., "credits expiring on")]', FindTextOptions::new()->nonEmptyString()->preg('/(.*).credits\sexpiring\son/')->allowNull(True));
        $expirationDate = $tab->findText('//span[contains(., "credits expiring on")]', FindTextOptions::new()->nonEmptyString()->preg('/credits\sexpiring\son\s(.*)/')->allowNull(True));
        if (isset($expiringBalance, $expirationDate)) {
            // Expiring balance
            $statement->addProperty('ExpiringBalance', $expiringBalance);
            // Expiration date
            $statement->setExpirationDate(strtotime($expirationDate));
        }

        $nextStatus = $tab->findText('//img[@alt="Account Card"]/following-sibling::div/h3/following-sibling::p', FindTextOptions::new()->nonEmptyString()->preg('/reach\s*(.+?)\s*status/i')->allowNull(True));
        /*
        $this->notificationSender->sendNotification('refs #25217 - need to check properties // IZ');
        */
        if (isset($nextStatus)) {
            // 10 visits to go to reach ... Status 
            switch($nextStatus) {
                case 'Emerald':
                    $statement->addProperty('Status', 'Member');
                    break;
                case 'Ruby':
                    $statement->addProperty('Status', 'Emerald');
                    break;
                case 'Diamond':
                    $statement->addProperty('Status', 'Diamond');
                    break;
                default:
                    break;
            }
        }

        $visitsToNextStatus = $tab->findText('//img[@alt="Account Card"]/following-sibling::div/h3/following-sibling::p', FindTextOptions::new()->nonEmptyString()->preg('/^\d+/i')->allowNull(True));
        if (isset($visitsToNextStatus)) {
            // ... visits to go to reach Diamond Status 
            $statement->addProperty('PointsNeeded', $visitsToNextStatus);
        }

        $tab->evaluate('//img[@alt="Account Card"]/../button[@id="show-member-card"]')->click();
        $number = $tab->findText('//img[@alt="ticket-example"]/../div/div/span');
        if (isset($number)) {
            // Number
            $statement->addProperty('Number', $number);
        }
    }
}
