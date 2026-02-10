<?php

namespace AwardWallet\Engine\speedway;

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

/*
use AwardWallet\ExtensionWorker\Message;
*/

class SpeedwayExtension extends AbstractParser implements LoginWithIdInterface, ParseInterface
{
    public function getStartingUrl(AccountOptions $options): string
    {
        return 'https://www.speedway.com/account/profile';
    }

    public function isLoggedIn(Tab $tab): bool
    {
        $this->logger->info($tab->getUrl());
        $tab->logPageState();
        $el = $tab->evaluate('//p[contains(text(), "CARD #")] | //button[contains(text(), "Log In")]');

        return $el->getNodeName() == "P";
    }

    public function getLoginId(Tab $tab): string
    {
        $tab->gotoUrl('https://www.speedway.com/account/profile');
        $tab->logPageState();

        return $tab->findText('//p[contains(text(), "CARD #")]', FindTextOptions::new()->nonEmptyString()->preg('/\d+/'));
    }

    public function login(Tab $tab, Credentials $credentials): LoginResult
    {
        $tab->showMessage('A new tab has just opened in your browser. Please switch to it and log in using your account credentials. Once you\'ve successfully logged in, return to this tab to continue.');
        $tab->logPageState();
        $tab->evaluate('//button[contains(text(), "Log In")]')->click();
        $loggedIn = $tab->evaluate('//button[@aria-controls="account-menu"]', EvaluateOptions::new()->timeout(360)->allowNull(true));
        $tab->logPageState();

        if ($loggedIn) {
            return new LoginResult(true);
        } else {
            return new LoginResult(false);
        }

        /*
        $login = $tab->evaluate('//input[@name="phone"]');
        $login->setValue($credentials->getLogin());

        $submitResult = $tab->evaluate('//div[contains(@id, "error")] | //div[@id="security-code-fields"]');

        if (strstr($submitResult->getAttribute('id'), "error")) {
            $error = $submitResult->getInnerText();

            if (strstr($error, "Please provide a valid phone number")) {
                return new LoginResult(false, $error, null, ACCOUNT_INVALID_PASSWORD);
            }

            return new LoginResult(false, $error);
        }

        if (strstr($submitResult->getAttribute('id'), "security-code-fields")) {
            $tab->showMessage(message::identifyComputer('Continue'));

            $el = $tab->evaluate('
                //p[@id="toast-text" and @aria-label="Login Successful!"]
                | //p[contains(text(), "CARD #")]
                | //p[contains(text(), "For additional verification")]
            ', EvaluateOptions::new()->nonEmptyString()->timeout(180)->allowNull(true));

            if (!isset($el)) {
                return LoginResult::identifyComputer();
            }

            if (
                $el->getNodeName() == 'P'
                && !strstr($submitResult->getInnerText(), "For additional verification")
            ) {
                return new LoginResult(true);
            }

            if (strstr($submitResult->getInnerText(), "For additional verification")) {
                $tab->showMessage(message::identifyComputer('Continue'));
                $el = $tab->evaluate('
                    //p[@id="toast-text" and @aria-label="Login Successful!"]
                    | //p[contains(text(), "CARD #")]
                ', EvaluateOptions::new()->nonEmptyString()->timeout(180)->allowNull(true));

                if (!isset($el)) {
                    return LoginResult::identifyComputer();
                } else {
                    return new LoginResult(true);
                }
            }
        }
        */
    }

    public function logout(Tab $tab): void
    {
        $this->logger->notice(__METHOD__);
        $tab->logPageState();
        $tab->evaluate('//button[@aria-controls="account-menu"]')->click();
        $tab->evaluate('//button[contains(text(), "Log Out")]', EvaluateOptions::new()->visible(false))->click();
        $tab->evaluate('//span[contains(text(), "Sign Up")]');
        $tab->logPageState();
    }

    public function parse(Tab $tab, Master $master, AccountOptions $accountOptions): void
    {
        $statement = $master->createStatement();
        $tab->logPageState();
        $tab->logPageState();

        // Card #
        $cardNumber = $tab->findText('//p[contains(text(), "CARD #")]', FindTextOptions::new()->allowNull(true)->timeout(10)->nonEmptyString()->preg("/Card\s*#\s*([^<]+)/ims"));

        if (isset($cardNumber)) {
            $statement->addProperty("Number", $cardNumber);
        }

        // Name
        $name = $tab->findText('//h2[contains(text(), "Hi,")]', FindTextOptions::new()->allowNull(true)->timeout(10)->nonEmptyString()->preg('/hi, (.*)/i'));

        if (isset($name)) {
            $statement->addProperty("Name", $name);
        }

        // Balance - Points
        $balance = $tab->findText('//p[contains(text(), "points")]/span', FindTextOptions::new()->allowNull(true)->timeout(10)->nonEmptyString());

        if (isset($balance)) {
            $statement->setBalance($balance);
        }

        // Expiration Date   // refs #4416
        $tab->gotoUrl('https://www.speedway.com/account/transactions');
        $tab->logPageState();
        $noTransactions = $tab->findText('//p[contains(text(), "You have no transactions.")]', FindTextOptions::new()->allowNull(true)->timeout(10)->nonEmptyString());

        /*
        if (!isset($noTransactions)) {
            $this->notificationSender->sendNotification('refs #24875 speedway - need to check transactions // IZ');
        }
        */

        $allNodes = $tab->evaluateAll('//li[div[div[p]]]');
        $allNodesCount = count($allNodes);
        $nodesPositive = $tab->evaluateAll('//li[div[div[p]] and div[div[span[contains(text(), "+")]]]]');
        $nodesPositiveCount = count($nodesPositive);
        $this->logger->debug("Total {$allNodesCount} nodes found");
        $this->logger->debug("Positive {$nodesPositiveCount} nodes found");

        foreach ($nodesPositive as $i => $node) {
            $date = $tab->findText('./div/div[1]/p[1]', FindTextOptions::new()->allowNull(true)->timeout(10)->nonEmptyString()->contextNode($node));
            $points = $tab->findText('.//span', FindTextOptions::new()->allowNull(true)->timeout(10)->nonEmptyString()->contextNode($node)->preg('/[\d\,\.]+/'));

            if (($exp = strtotime($date)) && $points > 0) {
                $this->logger->debug("Node # " . $i);
                $this->logger->debug('[EXP DATE SEEMS TO BE]: ' . strtotime("+9 month", $exp));
                $this->logger->debug('[LAST ACTIVITY SEEMS TO BE]: ' . $date);
                /*
                // Expiration Date
                $statement->SetExpirationDate(strtotime("+9 month", $exp));
                // Last Activity
                $statement->addProperty("LastActivity", $date);
                */

                $this->notificationSender->sendNotification('refs #24875 speedway - need to check exp date // IZ');

                break;
            }
        }
    }
}
