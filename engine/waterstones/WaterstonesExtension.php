<?php

namespace AwardWallet\Engine\waterstones;

use AwardWallet\Common\Parsing\Exception\NotAMemberException;
use AwardWallet\Common\Parsing\Exception\ProfileUpdateException;
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

class WaterstonesExtension extends AbstractParser implements LoginWithIdInterface, ParseInterface
{
    use TextTrait;

    public function getStartingUrl(AccountOptions $options): string
    {
        return 'https://www.waterstones.com/account/waterstonescard';
    }

    public function isLoggedIn(Tab $tab): bool
    {
        $el = $tab->evaluate('//input[@id="login_form_email"] | //div[@class="plus-cardno"]');

        return $el->getNodeName() == "DIV";
    }

    public function getLoginId(Tab $tab): string
    {
        return $tab->findText("//div[@class = 'plus-cardno']");
    }

    public function login(Tab $tab, Credentials $credentials): LoginResult
    {
        $tab->evaluate('//input[@id="login_form_email"]')->setValue($credentials->getLogin());
        $tab->evaluate('//input[@id="login_form_password"]')->setValue($credentials->getPassword());
        $tab->evaluate('//button[@name="Login"]')->click();
        $submitResult = $tab->evaluate('
            //label[@id="login_form_email-error"]
            | //label[@id="login_form_password-error"]
            | //p[@class="error"]
            | //div[@class="plus-cardno"]
        ');

        if ($submitResult->getNodeName() == 'LABEL') {
            return new LoginResult(false, $submitResult->getInnerText(), null, ACCOUNT_INVALID_PASSWORD);
        }

        if ($submitResult->getNodeName() == 'P') {
            $error = $submitResult->getInnerText();

            if (
                strstr($error, "Your login details are invalid. Please try again.")
            ) {
                return new LoginResult(false, $error, null, ACCOUNT_INVALID_PASSWORD);
            }

            if (
                strstr($error, "We've been unable to sign you in to your account. Please re-enter your email address and password to try again.")
            ) {
                return new LoginResult(false, $error, null, ACCOUNT_PROVIDER_ERROR);
            }

            return new LoginResult(false, $error);
        }

        if ($submitResult->getNodeName() == 'DIV') {
            return new LoginResult(true);
        }

        return new LoginResult(false);
    }

    public function parse(Tab $tab, Master $master, AccountOptions $accountOptions): void
    {
        $statement = $master->createStatement();
        // Available Waterstones Card balance
        $balance = $tab->findText("//div[contains(text(), 'Available ') and contains(., 'balance:')]/following-sibling::div[1]/strong", FindTextOptions::new()->nonEmptyString()->timeout(10)->allowNull(true)->preg('/([\d\.\,\-]+)/ims'));

        if (isset($balance)) {
            $statement->SetBalance($balance);
        }

        // Waterstones Card no.
        $cardNumber = $tab->findText("//div[@class = 'plus-cardno']", FindTextOptions::new()->nonEmptyString()->timeout(10)->allowNull(true));

        if (isset($cardNumber)) {
            $statement->addProperty("CardNumber", $cardNumber);
        }

        // Current stamps balance
        $currentStamps = $tab->findText('//div[contains(text(), "Current stamps balance:")]/following-sibling::div/strong', FindTextOptions::new()->nonEmptyString()->timeout(10)->allowNull(true)->preg('/^(\d+)/'));

        if (isset($currentStamps)) {
            $statement->addProperty("CurrentStamps", $currentStamps);
        }

        // Pending stamp cards
        $pendingStamp = $tab->findText('//div[contains(text(), "Pending stamp cards:")]/following-sibling::div/strong', FindTextOptions::new()->nonEmptyString()->timeout(10)->allowNull(true)->preg('/^(\d+)/'));

        if (isset($pendingStamp)) {
            $statement->addProperty("PendingStamp", $pendingStamp);
        }

        $tab->gotoUrl('https://www.waterstones.com/account/contactdetails');

        // Name
        $firstName = $tab->findText("//input[contains(@name,'user[first_name]')]/@value", FindTextOptions::new()->nonEmptyString()->timeout(10)->allowNull(true));
        $lastName = $tab->findText("//input[contains(@name,'user[last_name]')]/@value", FindTextOptions::new()->nonEmptyString()->timeout(10)->allowNull(true));

        if (isset($firstName, $lastName)) {
            $statement->addProperty("Name", beautifulName($firstName . ' ' . $lastName));
        }

        if ($tab->evaluate('//*[contains(text(), "You don\'t currently have a Waterstones Card associated with your account")]', EvaluateOptions::new()->allowNull(true))) {
            $statement->setNoBalance(true);
        }

        if (
            $this->findPreg('/.com\/plus/', $tab->getUrl())
            && $tab->findText("//div[contains(text(),'Register for your') and contains(.,'Plus card online')]", FindTextOptions::new()->nonEmptyString()->allowNull(true))
        ) {
            throw new NotAMemberException();
        }

        // An error has occurred in the Waterstones Card system, please try again later.
        if ($message = $tab->findText("//p[contains(text(), 'An error has occurred in the Waterstones Card system, please try again later.')] | //h2[contains(., 'rewards are currently down')]", FindTextOptions::new()->allowNull(true))) {
            throw new \CheckException($message, ACCOUNT_PROVIDER_ERROR);
        }

        // Thank you for choosing to upgrade to Waterstones Plus, please take a moment to confirm your details below. By joining our hugely popular email programme, you'll be all set to enjoy our complete suite of Waterstones Plus rewards.
        if ($message = $tab->findText("//p[contains(text(), 'please take a moment to confirm your details below.')]", FindTextOptions::new()->allowNull(true))) {
            throw new ProfileUpdateException();
        }

        // provider error
        if (
            !empty($statement->getProperties()['Name'])
            && $tab->findText("//div[contains(text(), 'Current') and contains(., 'Balance:')]/strong", FindTextOptions::new()->allowNull(true)) == '£'
        ) {
            throw new \CheckException('The website is experiencing technical difficulties, please try to check your balance at a later time.', ACCOUNT_PROVIDER_ERROR);
        }

        // Available gift card balance
        $tab->gotoUrl("https://www.waterstones.com/account/giftcards");
        $giftCardBalance = $tab->findText("//div[h2[contains(text(),'card balance:')]]/child::h2", FindTextOptions::new()->allowNull(true)->timeout(10)->preg('/£[\d\.]+/ims'));

        if (isset($giftCardBalance)) {
            $statement->addProperty("GiftCardBalance", $giftCardBalance);
        }
    }

    public function logout(Tab $tab): void
    {
        $this->logger->notice(__METHOD__);
        $tab->gotoUrl('https://www.waterstones.com/logout');
        $tab->evaluate('//span[contains(text(), "Join")]');
    }
}
