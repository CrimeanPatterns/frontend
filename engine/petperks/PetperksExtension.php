<?php

namespace AwardWallet\Engine\petperks;

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
use AwardWallet\ExtensionWorker\Message;

class PetperksExtension extends AbstractParser implements LoginWithIdInterface, ParseInterface
{
    public function getStartingUrl(AccountOptions $options): string
    {
        return 'https://www.petsmart.com/account/';
    }

    public function isLoggedIn(Tab $tab): bool
    {
        $this->logger->info($tab->getUrl());
        $el = $tab->evaluate('
            //input[@name="email"]
            | //div[@class="member-tier"]
        ');

        return $el->getNodeName() == "DIV";
    }

    public function getLoginId(Tab $tab): string
    {
        return $tab->evaluate('//a[@class="user-greeting"]//span[@data-ux-analytics-mask="true"]', EvaluateOptions::new()->nonEmptyString()->visible(false))->getInnerText();
    }

    public function login(Tab $tab, Credentials $credentials): LoginResult
    {
        $login = $tab->evaluate('//input[@name="email"]');
        $login->setValue($credentials->getLogin());
        $password = $tab->evaluate('//input[@name="password"]');
        $password->setValue($credentials->getPassword());
        $tab->evaluate('//button[@data-testid="signInCta"]')->click();
        $submitResult = $tab->evaluate('
            //div[@class="member-tier"]
            | //div[@data-testid="alert-banner"]/div
            | //div[@data-testid="email-field"]//div[contains(@class, "sparky-is-error") and text()]
            | //div[@class="sparky-c-password"]//div[contains(@class, "sparky-is-error") and text()]
            | //div[@data-testid="recaptcha-checkbox"]
        ', EvaluateOptions::new()->timeout(30)->allowNull(true));
        $tab->logPageState();
        $this->notificationSender->sendNotification('refs #25733 petsmart - need to check authorization // IZ');
        if (!$submitResult) {
            return new LoginResult(false);
        }
        if (strstr($submitResult->getAttribute('data-testid'), "recaptcha-checkbox")) {
            $tab->showMessage(Message::MESSAGE_RECAPTCHA);
            $submitResult = $tab->evaluate('
                //div[@class="member-tier"]
                | //div[contains(@class, "member-title-box")]/h6
                | //div[@data-testid="alert-banner"]/div
                | //div[@data-testid="email-field"]//div[contains(@class, "sparky-is-error") and text()]
                | //div[@class="sparky-c-password"]//div[contains(@class, "sparky-is-error") and text()]
            ', EvaluateOptions::new()->timeout(120)->allowNull(true));

            if (!$submitResult) {
                return LoginResult::captchaNotSolved();
            }
        }

        $tab->logPageState();
        if (
            strstr($submitResult->getAttribute('class'), "member-tier")
            || $submitResult->getNodeName() == 'H6'
        ) {
            return new LoginResult(true);
        } else {
            $error = $submitResult->getInnerText();

            if (
                strstr($error, "Your email and/or password are incorrect")
            ) {
                return new LoginResult(false, $error, null, ACCOUNT_INVALID_PASSWORD);
            }

            if (
                strstr($error, "An unknown error has occurred")
            ) {
                return new LoginResult(false, $error, null, ACCOUNT_PROVIDER_ERROR);
            }

            return new LoginResult(false, $error);
        }
    }

    public function logout(Tab $tab): void
    {
        $this->logger->notice(__METHOD__);
        $tab->evaluate('//a[contains(@class, "logout-link") and not(@data-gtm="logout")]', EvaluateOptions::new()->visible(false))->click();
        $tab->evaluate('//span[contains(text(), "sign in")]');
    }

    public function parse(Tab $tab, Master $master, AccountOptions $accountOptions): void
    {
        $statement = $master->createStatement();

        $json = $tab->fetch("https://www.petsmart.com/on/demandware.store/Sites-PetSmart-Site/default/LoyaltyController-GetLoyaltyMemberPointsBFF")->body;
        $this->logger->info($json);
        $response = json_decode($json);

        // Balance - points
        $statement->SetBalance($response->api->availablePoints ?? null);

        if (isset($response->api->availableDollars)) {
            $statement->AddSubAccount([
                "Code"        => "petperksRewards",
                "DisplayName" => 'Rewards',
                "Balance"     => $response->api->availableDollars,
            ]);
        }
        // Status
        $statement->addProperty("Status", beautifulName($response->api->currentTierLevel ?? null));
        // NextLevel
        $statement->addProperty("NextLevel", beautifulName($response->api->nextTierLevel ?? null));
        // Spend until the next level - Spend $364 to become a Bestie!
        if (isset($response->api->dollarsToNextTier)) {
            $statement->addProperty("SpendUntilTheNextLevel", floor($response->api->dollarsToNextTier));
        }
        // Spent this year - You've spent $136 this year.**
        if (isset($response->api->currentTierDollarsSpent)) {
            $statement->addProperty("SpentThisYear", floor($response->api->currentTierDollarsSpent));
        }
        // pts. until next treat
        if (isset($response->api->pointsToNextTier)) {
            $statement->addProperty("UntilNextTreat", floor($response->api->pointsToNextTier));
        }

        // Total Pets
        $json = $tab->fetch("https://www.petsmart.com/on/demandware.store/Sites-PetSmart-Site/default/Pet-CustomerPet?includeCheckoutPets=false")->body;
        $this->logger->info($json);
        $response = json_decode($json);

        $totalPetsCount = count($response->petModelArray ?? []);
        $statement->addProperty("TotalPets", $totalPetsCount);

        $tab->gotoUrl('https://www.petsmart.com/account/treats-offers/');
        $expDates = array_map('strtotime', $tab->findTextAll('//ul[@id = "expire-points-container"]/li[not(contains(@class, "heading"))]', FindTextOptions::new()->preg('#(\d{1,2}/\d{1,2}/\d{4})#')));
        $expPoints = $tab->findTextAll('//ul[@id = "expire-points-container"]//span');

        if (count($expDates) != count($expPoints)
            || count($expDates) == 0
        ) {
            return;
        }

        foreach (array_combine($expDates, $expPoints) as $time => $points) {
            if ($points > 0) {
                $statement->SetExpirationDate($time);
                $statement->addProperty('ExpiringBalance', $points);

                break;
            }
        }
    }
}
