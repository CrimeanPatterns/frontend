<?php

namespace AwardWallet\Engine\groupon;

use AwardWallet\ExtensionWorker\AbstractParser;
use AwardWallet\ExtensionWorker\AccountOptions;
use AwardWallet\ExtensionWorker\Credentials;
use AwardWallet\ExtensionWorker\EvaluateOptions;
use AwardWallet\ExtensionWorker\LoginResult;
use AwardWallet\ExtensionWorker\LoginWithIdInterface;
use AwardWallet\ExtensionWorker\Tab;
use AwardWallet\Schema\Parser\ParserTraits\TextTrait;
use AwardWallet\ExtensionWorker\ParseInterface;
use AwardWallet\Schema\Parser\Component\Master;
use AwardWallet\ExtensionWorker\FindTextOptions;
use CheckException;

class GrouponExtensionUsa extends AbstractParser implements LoginWithIdInterface, ParseInterface
{
    use TextTrait;
    private $mainBalance = 0;
    private $url;

    public function getStartingUrl(AccountOptions $options): string
    {
        $regionOptions = [
            ""          => "http://www.groupon.com/",
            "Australia" => "http://www.groupon.com.au/",
            "Canada"    => "http://www.groupon.ca/",
            "UK"        => "http://www.groupon.co.uk/",
            "USA"       => "http://www.groupon.com/",
        ];
        
        $this->url = $regionOptions[$options->login2];
        return $regionOptions[$options->login2] . 'subscription_center';
    }

    public function isLoggedIn(Tab $tab): bool
    {
        $this->logger->info($tab->getUrl());
        $el = $tab->evaluate('
            //input[@name="email"]
            | //div[button[@data-bhw="delete-account-button"]]/div[input]
            | //p[contains(text(), "HTTP") and contains(text(), "rror")]
        ', EvaluateOptions::new()->visible(false));

        if ($el->getNodeName() == 'P') {
            throw new CheckException('The website is experiencing technical difficulties, please try to check your balance at a later time.', ACCOUNT_PROVIDER_ERROR);
        }

        return $el->getNodeName() == "DIV";
    }

    public function getLoginId(Tab $tab): string 
    {
        return $tab->evaluate('
            //div[button[@data-bhw="delete-account-button"]]/div/input
        ', EvaluateOptions::new()->visible(false))->getAttribute('value');
    }

    public function login(Tab $tab, Credentials $credentials): LoginResult 
    {
        $login = $tab->evaluate('//input[@name="email"]');
        $login->setValue($credentials->getLogin());
        $tab->evaluate('//form/button[@type="submit" and div[div]]')->click();
        
        $submitResult = $tab->evaluate('
            //div[div[input[@name="email"]]]/span
            | //input[@name="name"]
            | //input[@name="password"]
        ');

        if ($submitResult->getNodeName() == 'SPAN') {
            $error = $submitResult->getInnerText();
            return $this->checkLoginErrors($error);
        }

        if (
            strstr($submitResult->getAttribute('name'), 'name')
        ) {
            return LoginResult::invalidPassword('You are not a member of this loyalty program.');            
        }

        $password = $tab->evaluate('//input[@name="password"]');
        $password->setValue($credentials->getPassword());
        $tab->evaluate('//form/button[@type="submit" and div[div]]')->click();

        $submitResult = $tab->evaluate('
            //div[div[input[@name="password"]]]/span
            | //div[button[@data-bhw="delete-account-button"]]/div/input
        ');

        if ($submitResult->getNodeName() == 'INPUT') {
            return new LoginResult(true);
        } else {
            $error = $submitResult->getInnerText();
            return $this->checkLoginErrors($error);
        }
    }

    private function checkLoginErrors(string $error): LoginResult
    {
        if (
            strstr($error, "Oops! The email or password did not match our records. Please try again.")
        ) {
            return LoginResult::invalidPassword($error);
        }

        if (
            strstr($error, "Access Denied")
        ) {
            return LoginResult::providerError($error);
        }

        return new LoginResult(false, $error);
    }

    public function logout(Tab $tab): void
    {
        $this->logger->notice(__METHOD__);
        $tab->evaluate('//button[@data-bhw-path="Header|signin-btn"]')->click();
        $tab->evaluate('//button[@data-bhw="UserSignOut"]')->click();
        $tab->evaluate('//input[@name="email"]');
    }

    public function parse(Tab $tab, Master $master, AccountOptions $accountOptions): void
    {
        $this->logger->notice(__METHOD__);
        $statement = $master->createStatement();
        $menuButton = $tab->evaluate('//button[@data-bhw-path="Header|signin-btn"]', EvaluateOptions::new()->allowNull(true)->timeout(5));
        $tab->logPageState();
        if (isset($menuButton)) {
            $menuButton->click();
        }
        $name = $tab->findText('//a[@href="/myaccount"]/div/p[contains(@class, "text-body")]', FindTextOptions::new()->allowNull(true)->timeout(5));
        $tab->logPageState();
        if (isset($name)) {
            $statement->addProperty('Name', beautifulName($name));
        }
        $grouponBucks = $tab->findText('//a[@href="/mybucks"]/div[contains(text(), "$")]', FindTextOptions::new()->allowNull(true)->preg('/\$(.*)/'));
        // Groupon Bucks
        if (isset($grouponBucks)) {
            $this->logger->info('Groupon Bucks', ['Header' => 3]);
            $statement->AddSubAccount([
                "Code"        => "groupon{$accountOptions->login2}GrouponBucks",
                "DisplayName" => "Groupon Bucks",
                "Balance"     => $grouponBucks,
            ]);
        }

        if (!isset($name, $grouponBucks, $menuButton)) {
            $this->notificationSender->sendNotification('refs #25642 groupon - need to check properties // IZ');
        }
        $this->ParseCoupons($tab, $statement);
        $statement->setBalance($this->mainBalance);
    }

    private function parseCoupons(Tab $tab, $statement)
    {
        $this->notificationSender->sendNotification('refs #25642 groupon - need to check groupons parsing // IZ');
        $tab->gotoUrl($this->url . 'mygroupons?tab=all');
        for($i = 0; $i < 10; $i++) {
            $loadMoreGroupons = $tab->evaluate('//button[div/div[contains(text(), "Show more vouchers")]]',
                EvaluateOptions::new()->allowNull(true)->timeout(5));
            $tab->logPageState();
            if (isset($loadMoreGroupons)) {
                $loadMoreGroupons->click();
                sleep(3);
                $this->watchdogControl->increaseTimeLimit(60);
            }

            if (
                !isset($loadMoreGroupons)
            ) {
                break;
            }
        }

        $allGrouponElements = $tab->evaluateAll('//a[contains(@data-testid, "voucher-action-")]');
        $allGrouponLinks = [];
        foreach($allGrouponElements as $grouponElement) {
            $allGrouponLinks[] = $grouponElement->getAttribute('href');
        }
        
        foreach($allGrouponLinks as $grouponLink) {
            $this->watchdogControl->increaseTimeLimit(120);
            $tab->gotoUrl($grouponLink);
            $redemtionCode = $tab->findText('//div[button[@aria-label="Copy"]]/span', FindTextOptions::new()->allowNull(true)->timeout(20));
            $shortName = $tab->findText('//div/span[@data-testid="voucher-title"]', FindTextOptions::new()->allowNull(true));
            $fullName = $tab->findText('//div[span[@data-testid="voucher-title"]]/span[not(@data-testid="voucher-title")]', FindTextOptions::new()->allowNull(true));
            $grouponCode = $tab->findText('//div[div[contains(text(), "Groupon Code")]]', FindTextOptions::new()->allowNull(true)->preg('/:\s*(.*)Order/'));
            $orderNumber = $tab->findText('//div[div[contains(text(), "Groupon Code")]]/div[contains(text(), "#")]', FindTextOptions::new()->allowNull(true));
            $tab->logPageState();

            $grouponData = [
                'ShortName' => $shortName,
                'Name' => $fullName,
                'DisplayName' => $fullName,
                'Code' => $grouponCode,
            ];

            if (
                !isset($redemtionCode, $shortName, $fullName, $grouponCode, $orderNumber)
            ) {
                $this->logger->debug('wrong groupon, skipping: ' . var_export($grouponData, true));
                continue;
            }

            $grouponReceiptLinkElement = $tab->evaluate( '//a[contains(@data-testid, "voucher-action-")]', EvaluateOptions::new()->allowNull(True)->timeout(20));
            if (!isset($grouponReceiptLinkElement)) {
                $this->logger->debug('this groupon does not have a receipt link, adding without receipt: ' . var_export($grouponData, true));
                $statement->addSubAccount($grouponData);
                continue;
            }

            $grouponReceiptLink = $grouponReceiptLinkElement->getAttribute('href');
            $tab->gotoUrl($grouponReceiptLink);

            $subtotal = $tab->findText('//div[@data-testid="subtotal-price-cart"]', FindTextOptions::new()->allowNull(True)->timeout(5));
            $promocodeApplied = $tab->findText( '//div[@data-testid="legacy_discount-price-cart"]/div', FindTextOptions::new()->allowNull(True)->timeout(0));
            $total = $tab->findText('//div[@data-testid="total-price-cart"]', FindTextOptions::new()->allowNull(True)->timeout(0));

            $grouponData['Value'] = isset($subtotal) ? $this->extractCorrectDouble($subtotal) : null;
            $grouponData['Balance'] = isset($promocodeApplied) ? $this->extractCorrectDouble($promocodeApplied) : null;
            $grouponData['Price'] = isset($total) ? $this->extractCorrectDouble($total) : null;
            $this->logger->debug(var_export($grouponData, true));
            $statement->addSubAccount($grouponData);
            if (isset($grouponData['Balance'])) {
                $this->mainBalance += $grouponData['Balance'];
            }
        }
    }

    private function extractCorrectDouble(string $string): float
    {
        $this->logger->notice(__METHOD__);
        $this->logger->debug('init: ' . $string);

        $cleaned_string = preg_replace('/[^\d\.]/', '', $string);
        if (preg_match('/^\d+(\.\d+)?$/', $cleaned_string, $matches)) {
            $final_number_string = $matches[0];
        } else {
            if (preg_match('/\d+\.?\d*/', $cleaned_string, $num_matches)) {
                 $final_number_string = $num_matches[0];
            } else {
                 $final_number_string = '0.0';
            }
        }

        $this->logger->debug('end: ' . $final_number_string);
        return (float) $final_number_string;
    }
}