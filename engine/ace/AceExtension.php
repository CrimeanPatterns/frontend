<?php

namespace AwardWallet\Engine\ace;

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
use AwardWallet\Schema\Parser\ParserTraits\TextTrait;

class AceExtension extends AbstractParser implements LoginWithIdInterface, ParseInterface
{
    use TextTrait;

    public function getStartingUrl(AccountOptions $options): string
    {
        return 'https://www.acehardware.com/myaccount';
    }

    public function isLoggedIn(Tab $tab): bool
    {
        $this->logger->info($tab->getUrl());

        $el = $tab->evaluate('//input[@id="sign-in-customer-login-email"] | //div[@class="rewards-id"]/b');

        return $el->getNodeName() == "B";
    }

    public function getLoginId(Tab $tab): string
    {
        $el = $tab->evaluate('//div[@class="rewards-id"]/b', EvaluateOptions::new()->nonEmptyString());

        return $this->findPreg('/\d+/', $el->getInnerText());
    }

    public function login(Tab $tab, Credentials $credentials): LoginResult
    {
        sleep(3);

        $login = $tab->evaluate('//input[@id="sign-in-customer-login-email"]');
        $login->setValue($credentials->getLogin());

        $password = $tab->evaluate('//input[@id="sign-in-customer-login-password"]');
        $password->setValue($credentials->getPassword());

        $tab->evaluate('//button[@data-mz-action="login"]')->click();

        $submitResult = $tab->evaluate('
            //div[@class="rewards-id"]/b
            | //div[@id="login-error-summary"]//p
            | //span[@class="mz-validationmessage" and text()]
            | //div[@class="categories"]
        ');

        if (
            $submitResult->getNodeName() == 'DIV'
            && $tab->getUrl == 'https://www.acehardware.com/'
            && $tab->evaluate('//div[contains(text(), "Points")]', EvaluateOptions::new()->allowNull(true))
        ) {
            return new LoginResult(true);
        }

        if ($submitResult->getNodeName() == 'B') {
            return new LoginResult(true);
        } elseif ($submitResult->getNodeName() == 'SPAN') {
            return new LoginResult(false, $submitResult->getInnerText(), null, ACCOUNT_INVALID_PASSWORD);
        } else {
            $error = $submitResult->getInnerText();

            if (
                strstr($error, "Your email or password is incorrect")
            ) {
                return new LoginResult(false, $error, null, ACCOUNT_INVALID_PASSWORD);
            }

            return new LoginResult(false, $error);
        }
    }

    public function logout(Tab $tab): void
    {
        $this->logger->notice(__METHOD__);
        $tab->evaluate('//div[@id="account-info-container"]/button')->click();
        $tab->evaluate('//a[contains(@href, "logout")]')->click();
        $tab->evaluate('//div[contains(@class, "signup-greeting") and contains(text(), "there")]');
    }

    public function parse(Tab $tab, Master $master, AccountOptions $accountOptions): void
    {
        $statement = $master->createStatement();
        $tab->gotoUrl("https://www.acehardware.com/myaccount");

        // Ace Rewards Number
        $number = $tab->findText('//div[@class="rewards-id"]/b', FindTextOptions::new()->nonEmptyString()->timeout(10)->allowNull(true));

        if (isset($number)) {
            $statement->addProperty('Number', $number);
        }

        // You are 1,997 points away from your next reward
        $pointsToNextReward = $tab->findText('//div[@class="rewards-next-reward-txt"]/span', FindTextOptions::new()->nonEmptyString()->allowNull(true));

        if (isset($pointsToNextReward)) {
            $statement->addProperty('PointsToNextReward', $pointsToNextReward);
        }

        // Balance - Current Points
        $balance = $tab->findText('//div[div[contains(text(), "Points")]]/div[@class="signup-stat"]', FindTextOptions::new()->nonEmptyString()->allowNull(true));

        if (isset($balance)) {
            $statement->setBalance($balance);
        }

        $tab->gotoUrl("https://www.acehardware.com/myaccount#/rewards-dashboard");

        // Name
        $name = $tab->findText('//p[@class="name"]/span[@class="data-mask"]', FindTextOptions::new()->nonEmptyString()->timeout(10)->allowNull(true));
        $tab->logPageState();

        if (isset($name)) {
            $statement->addProperty('Name', beautifulName($name));
        }

        $this->parseCoupons($tab, $master, $accountOptions);
    }

    private function parseCoupons(Tab $tab, Master $master, AccountOptions $accountOptions)
    {
        $coupons = $tab->evaluateAll('//li[@class="coupon"]');
        $this->logger->debug('Found ' . count($coupons) . ' coupons');

        foreach ($coupons as $coupon) {
            $text = $tab->findText('.//p[@class="text"]', FindTextOptions::new()->nonEmptyString()->allowNull(true)->contextNode($coupon));
            $exp = $tab->findText('.//p[@class="date"]', FindTextOptions::new()->nonEmptyString()->allowNull(true)->contextNode($coupon)->preg('/[\d\/]+/'));

            if (
                !isset($text, $exp)
                || !strtotime($exp)
                || strtotime($exp) < time()
            ) {
                $this->logger->debug('skipping wrong coupon');

                continue;
            }
            $code = 'AceCoupon' . md5("{$text}|{$exp}");
            $subaccount = [
                'Code'           => $code,
                'DisplayName'    => $text,
                'ExpirationDate' => strtotime($exp),
                'Balance'        => null,
            ];
            $master->getStatement()->addSubAccount($subaccount);
        }
    }
}
