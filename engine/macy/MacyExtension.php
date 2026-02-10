<?php

namespace AwardWallet\Engine\Macy;

use AwardWallet\Common\Parsing\Exception\NotAMemberException;
use AwardWallet\ExtensionWorker\AbstractParser;
use AwardWallet\ExtensionWorker\AccountOptions;
use AwardWallet\ExtensionWorker\Credentials;
use AwardWallet\ExtensionWorker\FindTextOptions;
use AwardWallet\ExtensionWorker\LoginResult;
use AwardWallet\ExtensionWorker\LoginWithIdInterface;
use AwardWallet\ExtensionWorker\ParseInterface;
use AwardWallet\ExtensionWorker\Tab;
use AwardWallet\Schema\Parser\Component\Master;
use AwardWallet\Schema\Parser\ParserTraits\TextTrait;

class MacyExtension extends AbstractParser implements LoginWithIdInterface, ParseInterface
{
    use TextTrait;

    public function getStartingUrl(AccountOptions $options): string
    {
        return 'https://www.macys.com/account/profile?cm_sp=macys_account-_-my_account-_-my_profile&linklocation=leftrail';
    }

    public function isLoggedIn(Tab $tab): bool
    {
        $this->logger->info($tab->getUrl());
        $el = $tab->evaluate('//div[@id="header-container"]//a[contains(@href,"/myaccount/home")] | //input[@name="user.email_address"]');
        return $el->getAttribute('href') == '/myaccount/home';
    }

    public function getLoginId(Tab $tab): string
    {
        return $tab->findText('//div[contains(@class,"pm-details-data")]', FindTextOptions::new()->nonEmptyString());
    }

    public function login(Tab $tab, Credentials $credentials): LoginResult
    {

        sleep(3);
        $login = $tab->evaluate('//input[@id="email"]');
        $login->setValue($credentials->getLogin());

        $password = $tab->evaluate('//input[@id="pw-input"]');
        $password->setValue($credentials->getPassword());

        $tab->evaluate('//button[@id="sign-in"]')->click();

        $submitResult = $tab->evaluate('//small[@id="pw-input-error"] 
        | //small[@id="email-error"] 
        | //p[@class="notification-body"] 
        | //div[@id="header-container"]//a[contains(@href,"/myaccount/home")]');

        if ($submitResult->getAttribute('href') == '/myaccount/home') {
            return LoginResult::success();
        } elseif ($submitResult->getNodeName() == 'P') {
            return new LoginResult(false, $submitResult->getInnerText(), null, ACCOUNT_INVALID_PASSWORD);
        } else {
            $error = $submitResult->getInnerText();

            if (
                strstr($error, 'Your email address or password is incorrect')
            ) {
                return new LoginResult(false, $error, null, ACCOUNT_INVALID_PASSWORD);
            }

            return new LoginResult(false, $error);
        }
    }

    public function logout(Tab $tab): void
    {
        $this->logger->notice(__METHOD__);
        $tab->gotoUrl('https://www.macys.com/account/logout');
        $tab->evaluate('//a[@data-testid="signInLink"] | //input[@id="email"]');
    }


    public function parse(Tab $tab, Master $master, AccountOptions $accountOptions): void
    {
        $uuid = $tab->getCookies()['macys_online_uid'];
        $guid = $tab->getCookies()['macys_online_guid'];
        $options = [
            'credentials' => 'same-origin',
            'method' => 'get',
            'headers' => [
                'Accept' => 'application/json, text/plain, */*',
            ],
        ];
        $user = $tab->fetch("https://www.macys.com/account-xapi/api/userprofile/user/$uuid?_=" . date("UB"),
            $options)->body;
        $this->logger->info($user);
        $user = json_decode($user);
        $st = $master->createStatement();

        // Name
        $st->addProperty("Name", beautifulName(($user->userProfileDetails->firstName) . " " . ($user->userProfileDetails->lastName)));


        $this->logger->info("Star Rewards", ['Header' => 3]);
        $options = [
            'credentials' => 'same-origin',
            'method' => 'get',
            'headers' => [
                'Accept' => 'application/json, text/plain, */*',
                'x-macys-devicefingerprint' => 853289251,
                'x-macys-signedin' => 1,
                'x-macys-uid' => $uuid,
                'x-macys-userguid' => $guid,
                'x-requested-with' => 'XMLHttpRequest',
            ],
        ];
        $data = $tab->fetch("https://www.macys.com/xapi/loyalty/v1/starrewardssummary?_origin=HnF",
            $options)->body;
        $this->logger->info($data);
        $response = json_decode($data);

        if (isset($response->rewardsInfo, $response->rewardsInfo->currentPoints, $response->tierInfo->tierName)) {
            // 0 current points
            $st->setBalance($response->rewardsInfo->currentPoints);
            // Status
            $st->addProperty('Status', $response->tierInfo->tierName);

            // YOU'VE SPENT:
            $st->addProperty('MoneySpent', '$' . $response->tierInfo->yearToDateSpend);

            if (isset($response->tierInfo->spendToKeepCurrent) && strtolower($response->tierInfo->tierName) == 'gold') {
                // Money Retain Status
                $st->addProperty('MoneyRetainStatus', '$' . $response->tierInfo->spendToKeepCurrent);
            }

            if (isset($response->tierInfo->spendToNextUpgrade) && strtolower($response->tierInfo->tierName) == 'silver') {
                // Spend to the next tier
                $st->addProperty('SpendToTheNextTier', '$' . $response->tierInfo->spendToNextUpgrade);
            }

            if (isset($response->tierInfo->tierExpiryDate)) {
                // Status expiration date
                $st->addProperty('StatusExpiration', strtotime($response->tierInfo->tierExpiryDate));
            }


            $this->logger->info('Star Money Rewards', ['Header' => 3]);
//            $this->http->GetURL("https://www.macys.com/loyalty/starrewards?cm_sp=macys_account-_-starrewards-_-star_rewards&lid=star_rewards-star_rewards");
            $tab->gotoUrl("https://www.macys.com/account/wallet?ocwallet=true");
            $ocwPageJson = json_decode($this->findPreg('/ocwPageJson\',(.+]})\s*\);/', $tab->getHtml()));
            $starRewardCardsList = $ocwPageJson->starRewardsInfo->starRewardCardsList ?? [];
            $starMoneyRewards = $ocwPageJson->starRewardsInfo->totalStarRewardCardsValue ?? null;

            if (isset($starMoneyRewards)) {
                $st->addProperty("CombineSubAccounts", false);
                $this->logger->debug("Star Money Rewards Available: {$starMoneyRewards}");

                foreach ($starRewardCardsList as $starRewardCard) {
                    $expirationDate = $starRewardCard->expirationDate;

                    if (!isset($exp) || $exp > strtotime($expirationDate)) {
                        $exp = strtotime($expirationDate);
                        $expBalance = $starRewardCard->formattedCurrentValue;
                        $this->logger->debug("Set exp date: {$expirationDate} / {$expBalance}");
                    }
                }

                if (isset($exp, $expBalance)) {
                    $st->addSubAccount([
                        'Code'            => 'macyStarMoneyRewards',
                        'DisplayName'     => 'Star Money Rewards',
                        'Balance'         => $starMoneyRewards,
                        'ExpirationDate'  => $exp,
                        'ExpiringBalance' => $expBalance,
                    ]);
                }
            }
        } else {
            if (isset($response->cardDefaultToWallet) && !isset($response->maskedCardNumber) && $response->cardDefaultToWallet === false) {
                $st->setNoBalance(true);
            } elseif (
                // AccountID: 2590821, 5191120
                isset($response->ccpaNoticeAndConsentEnabled, $response->emailLookupMembershipExists, $response->walletCardsIndicator)
                && $response->ccpaNoticeAndConsentEnabled == true
                && $response->emailLookupMembershipExists == false
                && in_array($response->walletCardsIndicator, [
                    "MultipleCards",
                    "OneCard", // AccountID: 4957711
                ])
                && !isset($response->cardDefaultToWallet)
                && !isset($response->maskedCardNumber)
            ) {
                $st->setNoBalance(true);
            } elseif (
                // AccountID: 5265619
                (
                    isset($response->walletCardsIndicator)
                    && !isset($response->maskedCardNumber)
                    && !isset($response->errors->error[0]->message)
                    && in_array($response->walletCardsIndicator, [
                        "NoCards",
                        "MultipleCards", // AccountID: 2590821
                        "OneCard", // AccountID: 3431391
                    ])
                )
                // AccountID: 2590821
                || (
                    isset($response->errors->error[0]->message)
                    && strstr($response->errors->error[0]->message, "We're experiencing a technical glitch. Please try again later.")
                )
            ) {
                throw new NotAMemberException();
            }
            // We're sorry! It looks like there's an issue with Star Rewards. Please try again later.
            elseif (isset($response->errors->error[0]->message) && strstr($response->errors->error[0]->message, 'We\'re sorry! It looks like there\'s an issue with Star Rewards. Please try again later.')) {
                $master->setWarning($response->errors->error[0]->message);
            }
            // AccountID: 4634571
            elseif (isset($response->errors->error[0]->message) && strstr($response->errors->error[0]->message, "We're sorry! We can’t find a Star Rewards membership associated with your default Macy’s Credit Card. Please go to My Wallet and make your current Macy’s Credit Card your default card or call Macy's Credit Customer Service at 1-888-860-7111.")) {
                $master->setWarning($response->errors->error[0]->message);
            } elseif (isset($response->errors->error[0]->message) && strstr($response->errors->error[0]->message, "We're sorry, your session has timed out. Please sign in to your account again")) {
                $this->notificationSender->sendNotification('session has timed out // MI');
            }
        }
    }
}
