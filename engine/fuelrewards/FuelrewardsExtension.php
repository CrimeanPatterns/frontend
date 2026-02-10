<?php

namespace AwardWallet\Engine\fuelrewards;

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
use AwardWallet\Schema\Parser\ParserTraits\TextTrait;

class FuelrewardsExtension extends AbstractParser implements LoginWithIdInterface, ParseInterface
{
    use TextTrait;

    public function getStartingUrl(AccountOptions $options): string
    {
        return 'https://www.fuelrewards.com/fuelrewards/loggedIn.html';
    }

    public function isLoggedIn(Tab $tab): bool
    {
        $this->logger->info($tab->getUrl());
        $el = $tab->evaluate('//p[contains(@class, "member-info") and contains(@class, "altid") and not(contains(text(), "ALT ID")) and text()] | //a[@id="loginButton"]');

        return $el->getNodeName() == "P";
    }

    public function getLoginId(Tab $tab): string
    {
        return $tab->evaluate('//p[contains(@class, "member-info") and contains(@class, "altid") and not(contains(text(), "ALT ID")) and text()]',
            EvaluateOptions::new()->nonEmptyString())->getInnerText();
    }

    public function login(Tab $tab, Credentials $credentials): LoginResult
    {
        $login = $tab->evaluate('//input[@id="userId"]');
        $login->setValue($credentials->getLogin());

        $password = $tab->evaluate('//input[@id="password"]');
        $password->setValue($credentials->getPassword());

        $inputResult = $tab->evaluate('//div[@id="reCapthcaDiv"] | //a[@id="loginButton"]');
        $submitResultXpath = '//p[contains(@id, "Error")]/label[text()] 
        | //p[@id="serverErrors" and text() and not(text() = "error")] 
        | //p[contains(@class, "member-info") and contains(@class, "altid") and not(contains(text(), "ALT ID")) and text()]';
        $captchaXpath = '//div[@class="captcha-image"]';

        if ($inputResult->getNodeName() == 'DIV') {
            $tab->showMessage(Message::captcha('submit'));
            $submitResult = $tab->evaluate($submitResultXpath, EvaluateOptions::new()->timeout(120));
        } else {
            $inputResult->click();
            $submitResult = $tab->evaluate("$submitResultXpath | $captchaXpath");
            if (stristr($submitResult->getAttribute('class'), 'captcha-image')) {
                $tab->showMessage(Message::captcha('submit'));
                $submitResult = $tab->evaluate($submitResultXpath, EvaluateOptions::new()->timeout(120));
            }
        }

        if (strstr($submitResult->getAttribute('class'), "altid")) {
            return new LoginResult(true);
        } elseif ($submitResult->getNodeName() == 'LABEL') {
            return new LoginResult(false, $submitResult->getInnerText(), null, ACCOUNT_INVALID_PASSWORD);
        } else {
            $error = $submitResult->getInnerText();

            if (
                strstr($error, "User name or password not recognized")
            ) {
                return new LoginResult(false, $error, null, ACCOUNT_INVALID_PASSWORD);
            }

            if (
                strstr($error, "Please verify that you are not a robot")
            ) {
                return new LoginResult(false, $error, null, ACCOUNT_PROVIDER_ERROR);
            }

            return new LoginResult(false, $error);
        }
    }

    public function logout(Tab $tab): void
    {
        $this->logger->notice(__METHOD__);
        $tab->gotoUrl('https://www.fuelrewards.com/fuelrewards/logout.html');
        $tab->evaluate('//a[@id="headerLogin"]');
    }

    public function parse(Tab $tab, Master $master, AccountOptions $accountOptions): void
    {
        $st = $master->createStatement();
        // set Name
        $st->addProperty('Name', beautifulName($tab->findText('(//div[@class="user-name"])[1]',
            FindTextOptions::new()->visible(false)->preg("/([^|]+)/"))));
        // set Account Number
        $st->addProperty('AccountNumber',
            $tab->findText('(//div[@class="user-account"])[1]',
                FindTextOptions::new()->visible(false)->preg('/Account#\s+(.*)/ims')));
        // set Member Since
        $st->addProperty('MemberSince',
            $tab->findText('(//div[@class="user-date"])[1]',
                FindTextOptions::new()->visible(false)->preg('/Member since\s+(.*)/ims')));
        // set Total Amount Saved on Fuel
        $totalAmountSaved = $tab->findTextNullable('//div[contains(@class, "balance_ts")]/h2',
            FindTextOptions::new()->visible(false)->nonEmptyString());
        if (!empty($totalAmountSaved)) {
            $st->addProperty('TotalAmountSaved', $totalAmountSaved);
        }
        // Status
        $trier = $this->findPreg("/tier\s*=\s*(?:\'|\")([^\'\"]+)/", $tab->getHtml());
        $this->logger->debug("[Status]: {$trier}");
        // Next Status
        $nextStatus = $tab->findTextNullable('//p[contains(@class, "togo") and not(contains(@class, "no-eval"))]//a[contains(@href, "fuelrewards/status")]',
            FindTextOptions::new()->preg('/(.*)\sstatus/ims'));
        if (isset($nextStatus)) {
            $st->addProperty('NextStatus', $nextStatus);
        }

        if (!in_array($trier, [
            'SEGREACTIVATIONDEC19',
            'NCALDEBRAND919',
        ])) {
            $st->addProperty("Status", beautifulName($trier));
        }

        // Expiration Date
        $exp = $tab->findTextNullable("//span[contains(text(), 'Rewards Expiring')]/following-sibling::span[1]",
            FindTextOptions::new()->visible(false)->preg("/on\s*([\d\/]+)/ims"));

        if ($exp = strtotime($exp)) {
            $st->SetExpirationDate($exp);
        }
        // Rewards to expire
        $rewardsToExpire = $tab->findTextNullable("//span[contains(text(), 'Rewards Expiring')]/following-sibling::span[2]");
        if ($rewardsToExpire) {
            $st->addProperty('RewardsToExpire', $rewardsToExpire);
        }

        // set Balance
        $balance = $tab->findText("//input[@id = 'totalRewardBal']/@value");
        if (!empty($balance)) {
            $st->SetBalance($balance);
        } // Account ID: 4363450, SetBalance(0);
        elseif (
            !empty($st->getProperties()['Name'])
            && !empty($st->getProperties()['AccountNumber'])
            && !empty($st->getProperties()['MemberSince'])
            && !isset($st->getProperties()['TotalAmountSaved'])
            && empty($st->getProperties()['AccountExpirationDate'])
            && empty($st->getProperties()['RewardsToExpire'])
        ) {
            $st->SetBalance(0);
        }

    }
}
