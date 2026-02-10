<?php

namespace AwardWallet\Engine\omnihotels;

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

class OmnihotelsExtension extends AbstractParser implements LoginWithIdInterface, ParseInterface
{
    public function getStartingUrl(AccountOptions $options): string
    {
        return 'https://bookings.omnihotels.com/membersarea/overview';
    }

    public function isLoggedIn(Tab $tab): bool
    {
        $el = $tab->evaluate('//form[@id="login-form"] | //p[contains(text(),"Select Guest Member #")]', EvaluateOptions::new()->visible(false));

        return strstr($el->getInnerText(), "Select Guest Member #");
    }

    public function getLoginId(Tab $tab): string
    {
        return $tab->evaluate('//p[contains(text(),"Select Guest Member #")]', EvaluateOptions::new()->visible(false))->getInnerText();
    }

    public function login(Tab $tab, Credentials $credentials): LoginResult
    {
        $login = $tab->evaluate('//input[@id="email"]');
        $login->setValue($credentials->getLogin());

        $password = $tab->evaluate('//input[@id="new-password"]');
        $password->setValue($credentials->getPassword());

        $tab->evaluate('//button[@id="submit_btn"]')->click();

        $submitResult = $tab->evaluate('
            //p[contains(text(),"Select Guest Member #")]
            | //h2[contains(text(), "Recaptcha verification failed. Please try again.")]
            | //p[@class="help-block"]
        ', EvaluateOptions::new()->visible(false));

        if ($submitResult->getNodeName() == 'H2') {
            return new LoginResult(false, $submitResult->getInnerText(), null, ACCOUNT_PROVIDER_ERROR);
        }

        if (strstr($submitResult->getInnerText(), "Select Guest Member #")) {
            return new LoginResult(true);
        } else {
            $error = $submitResult->getInnerText();

            if (
                strstr($error, "These credentials do not match our records")
                || strstr($error, "Profile Not Found. Please contact Member Services")
            ) {
                return new LoginResult(false, $error, null, ACCOUNT_INVALID_PASSWORD);
            }

            return new LoginResult(false, $error);
        }
    }

    public function logout(Tab $tab): void
    {
        $this->logger->notice(__METHOD__);
        $tab->evaluate('//a[contains(@href, "logout")]', EvaluateOptions::new()->visible(false))->click();
        $tab->evaluate('//div[@class="booker-wrapper"]');
    }

    public function parse(Tab $tab, Master $master, AccountOptions $accountOptions): void
    {
        $statement = $master->createStatement();
        // Name
        $name = $tab->findText("//h2[contains(@class, 'greetings')]", FindTextOptions::new()->allowNull(true)->timeout(10));

        if (isset($name)) {
            $statement->addProperty("Name", $name);
        }
        // Level
        $level = $tab->findText("//h2[contains(@class, 'tier-level-name')]", FindTextOptions::new()->allowNull(true));

        if (isset($level)) {
            $statement->addProperty("Level", $level);
        }
        // Member Since
        $memberSince = $tab->findText("//p[contains(text(), 'Member Since')]", FindTextOptions::new()->allowNull(true)->preg('/Member Since\s+(.+)/'));

        if (isset($memberSince)) {
            $statement->addProperty("MemberSince", $memberSince);
        }
        // Select Guest Member No
        $number =
            $tab->findText("//p[contains(text(), 'Select Guest Member #')]", FindTextOptions::new()->allowNull(true)->preg('/#(.+)/'))
            ?? $tab->findText("//div[contains(@class, 'header-desktop')]//span[contains(text(), 'Member Number')]/following-sibling::span", FindTextOptions::new()->allowNull(true));

        if (isset($number)) {
            $statement->addProperty("Number", $number);
        }
        // Spend $1000 more by Dec 31, 2024 to achieve Insider Status through 2025.
        $spendNextLevel = $tab->findText("//p[contains(., 'Spend') and contains(., 'to achieve') and contains(., ' Status through')]/span[1]", FindTextOptions::new()->allowNull(true));
        if (isset($spendNextLevel)) {
            $statement->addProperty("SpendNextLevel", $spendNextLevel);
        }
        // Spend $4000 more by Dec 31, 2025 to retain CHAMPION Status through 2026.
        $spendRetainLevel = $tab->findText("//p[contains(., 'Spend') and contains(., 'to retain') and contains(., ' Status through')]/span[1]", FindTextOptions::new()->allowNull(true));
        if (isset($spendRetainLevel)) {
            $statement->addProperty("SpendRetainLevel", $spendRetainLevel);
        }

        // Spend $1000 more by Dec 31, 2024 to achieve Insider Status through 2025.
        $creditsFreeNight = $tab->findText("//p[contains(., 'more Omni Credits to earn a free night stay in any Omni Hotel or Resort!')]/span[1]", FindTextOptions::new()->allowNull(true));

        if (isset($creditsFreeNight)) {
            $statement->addProperty("CreditsFreeNight", $creditsFreeNight);
        }
        // Status Valid Through
        $statusValidThrough = $tab->findText("//p[contains(text(), '* Valid through')]", FindTextOptions::new()->allowNull(true)->preg('/Valid through\s+(.+)/'));

        if (isset($statusValidThrough)) {
            $statement->addProperty("StatusValidThrough", $statusValidThrough);
        }

        // Tier Dollars
        $tierDollars = $tab->findText('//h3[contains(text(),"Tier Dollars")]/following-sibling::div/div/p',
            FindTextOptions::new()->allowNull(true));
        $statement->addProperty('TierDollars', $tierDollars);

        // Balance - Free Nights
        $balance = $tab->findText('//div[@id = "membersarea-free-night-credits"]/p',
            FindTextOptions::new()->allowNull(true)->preg('#(\d+)/\d+#'));
        $statement->setBalance($balance);


        /*
        if (
            !$statement->getBalance()
            && ($message = $tab->evaluate('//h2[contains(text(), "Something went wrong.")]', EvaluateOptions::new()->allowNull(true)))
        ) {
            throw new \CheckException($message, ACCOUNT_PROVIDER_ERROR);
        }
        */

        /*
         $credits = $tab->findText('//div[@class="credit-balance"]//div[@class="points-bg"]/span', FindTextOptions::new()->allowNull(true));

        if (isset($credits)) {
            $statement->AddSubAccount([
                'Code'           => 'OmniCreditBalance',
                'DisplayName'    => 'Omni Credit Balance',
                'Balance'        => $credits,
            ]);
        }
        */
    }
}
