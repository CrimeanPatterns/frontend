<?php

namespace AwardWallet\Engine\groupon;

use AwardWallet\Common\Parsing\Solver\Extra\Context;
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
use AwardWallet\Common\Parsing\Html;
use AwardWallet\ExtensionWorker\FindTextOptions;
use Symfony\Component\DomCrawler\Crawler;
use Exception;
use CheckException;

class GrouponExtensionGeneral extends AbstractParser implements LoginWithIdInterface, ParseInterface
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
            //input[@id="login-email-input"]
            | //button[@id="user-profile-submit"]
            | //p[contains(text(), "HTTP") and contains(text(), "rror")]
        ', EvaluateOptions::new()->visible(false));

        if ($el->getNodeName() == 'P') {
            throw new CheckException('The website is experiencing technical difficulties, please try to check your balance at a later time.', ACCOUNT_PROVIDER_ERROR);
        }

        return $el->getNodeName() == "BUTTON";
    }

    public function getLoginId(Tab $tab): string
    {
        return $tab->findText('//div[@id="subcenter-email"]/span[not(a)]');
    }

    public function login(Tab $tab, Credentials $credentials): LoginResult
    {
        $login = $tab->evaluate('//input[@id="login-email-input"]');
        $login->setValue($credentials->getLogin());
        
        $password = $tab->evaluate('//input[@id="login-password-input"]');
        $password->setValue($credentials->getPassword());

        $tab->evaluate('//button[@id="signin-button"]')->click();
        $tab->logPageState();

        $submitResult = $tab->evaluate('
            //p[@id="error-login-email-input"]
            | //p[@id="error-login-password-input"]
            | //div[contains(@class, "error") and contains(@class, "notification")]
            | //button[@id="user-profile-submit"]
        ');

        if ($submitResult->getNodeName() == 'BUTTON') {
            return new LoginResult(true);
        }

        if ($submitResult->getNodeName() == 'P') {
            $error = $submitResult->getInnerText();
            return $this->checkLoginErrors($error);
        }

        if ($submitResult->getNodeName() == 'DIV') {
            $error = $submitResult->getInnerText();
            return $this->checkLoginErrors($error);
        }

        return new LoginResult(false);
    }

    private function checkLoginErrors(string $error): LoginResult
    {
        if (
            strstr($error, "Your username or password is incorrect.")
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
        $tab->evaluate('//button[@id="signin-container"]')->click();
        $tab->evaluate('//a[@id="sign-out"]')->click();
        $tab->evaluate('//input[@id="login-email-input"]');
    }

    public function parse(Tab $tab, Master $master, AccountOptions $accountOptions): void
    {
        $statement = $master->createStatement();
        $this->logger->notice(__METHOD__);
        $tab->gotoUrl($this->url . 'myaccount');
        $dataLayer = $tab->findText('//script[@id="domConfig"]', FindTextOptions::new()->visible(false));
        $dataLayer = json_decode($dataLayer);
        $firstName = $dataLayer->account->firstName ?? '';
        $lastName = $dataLayer->account->lastName ?? '';
        $statement->addProperty('Name', beautifulName("$firstName $lastName"));
        $this->ParseCoupons($tab, $statement, $accountOptions);
        $statement->setBalance($this->mainBalance);
    }

    private function parseCoupons(Tab $tab, $statement, $accountOptions)
    {
        $grouponBucks = $tab->findText('//span[contains(@class, "bucks-balance")]', FindTextOptions::new()->allowNull(true)->preg('/\$(.*)/'));
        // Groupon Bucks
        if (isset($this->grouponBucks)) {
            $this->logger->info('Groupon Bucks', ['Header' => 3]);
            $statement->AddSubAccount([
                "Code"        => "groupon{$accountOptions->login2}GrouponBucks",
                "DisplayName" => "Groupon Bucks",
                "Balance"     => $grouponBucks,
            ]);
        }

        // TODO: scrolling
        $tab->gotoUrl($this->url . 'mystuff');
        for($i = 0; $i < 10; $i++) {
            $this->watchdogControl->increaseTimeLimit(60);
            $lastElement = $tab->evaluate('//div[contains(@id,"grpn-item-") and contains(@class, "deal")][last()]', EvaluateOptions::new()->allowNull(true)->timeout(3));
            $footer = $tab->evaluate('//button[@id="country-link-container"]', EvaluateOptions::new()->allowNull(true)->timeout(3));
            if (isset($lastElement)) {
                $footer->focus();
                sleep(3);
                $lastElement->focus();
            }
        }

        $allGrouponElements = $tab->evaluateAll('//div[contains(@id,"grpn-item-") and contains(@class, "deal")]');
        $allGrouponsData = [];
        foreach($allGrouponElements as $grouponElement) {
            $linkElement = $tab->evaluate('//a[contains(@id, "groupon-item-")]', EvaluateOptions::new()->contextNode($grouponElement)->allowNull(True));
            if (isset($linkElement)) {
                $link = $linkElement->getAttribute('href');
            }
            $number = $tab->findText('//div[@class="order-info"]', FindTextOptions::new()->contextNode($grouponElement)->allowNull(True)->preg('/\d+/'));   
            $expDate = $tab->findText('//div[contains(@id,"grpn-item-") and contains(@class, "deal")]//span[contains(@class, "deal-expire-date")]', FindTextOptions::new()->contextNode($grouponElement)->allowNull(True)->preg('/(\d+.+?\d+)/'));
            $expDate = strtotime($expDate);
            $result = [
                'link' => $link,
                'number' => $number,
                'expDate' => $expDate
            ];
            $this->logger->debug(var_export($result, true));
            if (!isset($number, $expDate, $result)) {
                $this->logger->debug('Wrong groupon, skipping');
                continue;
            }
            if (!isset($expDate) || $expDate < time()) {
                $this->logger->debug('Groupon expired, skipping');
                continue;
            }
            $allGrouponsData[] = $result;
        }

        foreach($allGrouponsData as $grouponsData) {
            $this->logger->debug('processing groupon ' . var_export($grouponsData, true));
            $this->watchdogControl->increaseTimeLimit(120);
            $options = ['method' => 'get'];

            $grouponInfoGeneralPage = $this->fetch($tab, $grouponsData['link'], $options);
            if (!isset($grouponInfoGeneralPage)) {
                $this->logger->debug('No page fetched');
                continue;
            }

            $crawler = new Crawler($grouponInfoGeneralPage);
            $data = $this->filterByCrawler($crawler, '//script[@id="domConfig"]')->text();
            // $this->logger->info(var_export($data, true));

            $data = json_decode($data, true);

            $voucher = $data['voucher'] ?? null;
            $deal = $data['deal'] ?? null;
            $dealOption = $data['dealOption'] ?? null;
            $order = $data['order'] ?? null;
            $orderDetails = $data['orderDetails'] ?? null;

            $balance = null;
            if (isset($orderDetails['discount'])) {
                $balance = $this->findPreg('/[\d\.]+/', $orderDetails['discount']);
            }

            $currency = $order['unitPrice']['currencyCode'] ?? null;
            $name = $deal['title'] ?? null;
            $unitPrice = null;
            if (isset($orderDetails['unitPrice'])) {
                $unitPrice = $this->findPreg('/[\d\.]+/', $orderDetails['unitPrice']);
            }

            $price = $unitPrice;
            if ($balance !== null && $unitPrice !== null) {
                $price = $unitPrice - abs($balance);
                $this->mainBalance += abs($balance);
            }
            $quantity = $order['quantity'] ?? null;
            $save = 0;
            if ($dealOption && isset($dealOption['discount']['amount'])) {
                $save = $dealOption['discount']['amount'] / 100;
            }
            $shortName = $dealOption['subTitle'] ?? null;
            $voucherCode = $voucher['id'] ?? null;
            $expirationDate = strtotime($voucher['expiresAt']) ?? $grouponsData['expDate'] ?? null;

            $result = [];
            $result['Balance'] = $balance;
            $result['Currency'] = $currency;
            $result['Name'] = $name;
            $result['Price'] = $price;
            $result['Quantity'] = $quantity;
            $result['Save'] = $save;
            $result['ShortName'] = $shortName;
            $result['Value'] = $unitPrice;
            $result['Code'] = $voucherCode;
            $result['DisplayName'] = $shortName;
            $result['ExpirationDate'] = $expirationDate;
            $this->logger->debug(var_export($result, true));
            $statement->AddSubAccount($result);
        }
    }

    private function filterByCrawler(Crawler $crawler, string $xpath)
    {
        $filterResult = $crawler->filterXPath($xpath);
        if ($filterResult->count() > 0) {
            return $filterResult;
        }
        return null;
    }

    private function fetch(Tab $tab, string $url, array $options = []) 
    {
        try {
            $fetchResult = $tab->fetch($url, $options);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return null;
        }

        if ($fetchResult->status !== 200) {
            return null;
        }

        return $fetchResult->body;
    }
}