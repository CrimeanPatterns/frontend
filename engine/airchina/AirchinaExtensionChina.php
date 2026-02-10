<?php

namespace AwardWallet\Engine\airchina;

use AwardWallet\Common\Parsing\Html;
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

class AirchinaExtensionChina extends AbstractParser implements LoginWithIdInterface, ParseInterface
{
    use TextTrait;

    public function getStartingUrl(AccountOptions $options): string
    {
        return 'https://ffp.airchina.com.cn/appen/index/member/';
    }

    public function isLoggedIn(Tab $tab): bool
    {
        $this->logger->info($tab->getUrl());
        $tab->logPageState();
        $el = $tab->evaluate('//p[@class="person-info-name"] | //input[@id="loginUid"]');

        return $el->getNodeName() == "P";
    }

    public function getLoginId(Tab $tab): string
    {
        $tab->logPageState();
        $tab->gotoUrl('https://ffp.airchina.com.cn/appen/index/member/');
        $tab->logPageState();

        return $tab->findText('//p[@class="person-info-name"]', FindTextOptions::new()->nonEmptyString());
    }

    public function login(Tab $tab, Credentials $credentials): LoginResult
    {
        $tab->logPageState();
        $login = $tab->evaluate('//input[@name="loginUid"]');
        $login->setValue($credentials->getLogin());
        $password = $tab->evaluate('//input[@name="loginPwd"]');
        $password->setValue($credentials->getPassword());
        $tab->evaluate('//input[@name="nc_read"]')->click();
        $tab->evaluate('//div[@class="login-btn"]/a[@id="submitBtn"]')->click();
        $submitResult = $tab->evaluate('
            //div[@id="errors"]
            | //p[@class="person-info-name"]
            | //a[@href="/appen/index/member/"]
            | //div[@id="CAPTCHA_VERY" and contains(@class, "dx_captcha")]
        ');

        $tab->logPageState();

        if (
            $submitResult->getNodeName() == 'DIV'
            && strstr($submitResult->getInnerText(), "Captcha is incorrect")
        ) {
            $tab->showMessage(Message::MESSAGE_RECAPTCHA);
            $submitResult = $tab->evaluate('
                //div[@id="errors" and not(contains(text(), "Captcha is incorrect"))]
                | //p[@class="person-info-name"]
                | //a[@href="/appen/index/member/"]
            ', EvaluateOptions::new()->nonEmptyString()->timeout(180)->allowNull(true));
        }
        $tab->logPageState();

        if (!isset($submitResult)) {
            return LoginResult::captchaNotSolved();
        }

        if (
            $submitResult->getNodeName() == 'DIV'
        ) {
            $error = $submitResult->getInnerText();
            $this->logger->error("[Error]: {$error}");

            if (strstr($error, 'Other Error')) {
                return new LoginResult(false, $error, null, ACCOUNT_PROVIDER_ERROR);
            }

            if (strstr($error, "Account or password is incorrect.")) {
                return new LoginResult(false, $error, null, ACCOUNT_INVALID_PASSWORD);
            }

            return new LoginResult(false, $error);
        }

        if (in_array($submitResult->getNodeName(), ['A', 'P'])) {
            return new LoginResult(true);
        }

        return new LoginResult(false);
    }

    public function logout(Tab $tab): void
    {
        $this->logger->notice(__METHOD__);
        $tab->logPageState();
        $tab->gotoUrl('https://ffp.airchina.com.cn/appen/logout/member');
        $tab->evaluate('//a[@href="/appen/register/member"]');
    }

    public function parse(Tab $tab, Master $master, AccountOptions $accountOptions): void
    {
        $statement = $master->createStatement();
        $tab->logPageState();

        // Name
        $name = $tab->findText("//li[contains(text(), 'Welcome，')]", FindTextOptions::new()->preg("/，\s*([^<]+)/")->timeout(10)->allowNull(true)->nonEmptyString());

        if (isset($name)) {
            $statement->addProperty("Name", beautifulName($name));
        }

        // Status
        $status = $tab->findText("//span[contains(@class, 'person-card-name')]", FindTextOptions::new()->allowNull(true)->nonEmptyString());

        if (isset($status)) {
            $statement->addProperty('Status', $status);
        }

        // Membership Level Expiration Date
        $statusExpiration = $tab->findText("//p[contains(text(), 'Membership Level Expiration Date')]", FindTextOptions::new()->preg("/Date:?\s*([^<]+)/")->allowNull(true)->nonEmptyString());

        if (isset($statusExpiration)) {
            $statement->addProperty('StatusExpiration', str_replace(['?', '：'], '', Html::cleanXMLValue($statusExpiration)));
        }

        // Kilometers to next level
        $clubMiles = $tab->findText("(//p[@class = 'person-mileage-news'])[1]", FindTextOptions::new()->preg("/fly\s*([\d\.\,]+)\s*(?: Air China Lifetime Platinum\s*|)kilometer/")->allowNull(true)->nonEmptyString());

        if (isset($clubMiles)) {
            $statement->addProperty("ClubMiles", $clubMiles);
        }

        // Segments to next level
        $segments = $tab->findText("(//p[@class = 'person-mileage-news'])[1]", FindTextOptions::new()->preg("/or\s*([\d\.\,]+)\s*segment/")->allowNull(true)->nonEmptyString());

        if (isset($segments)) {
            $statement->addProperty("Segments", $segments);
        }

        // Will Expire In 3 Months
        $expiringBalance = $tab->findText("//p[contains(text(), 'Kilometers Will Expire In 3 Months')]/b", FindTextOptions::new()->allowNull(true)->nonEmptyString());

        if (isset($expiringBalance)) {
            $statement->addProperty('ExpiringBalance', $expiringBalance);
        }

        // Air China Lifetime Platinum mileage
        $lifetimeMileage = $tab->findText("//p[contains(text(), 'Lifetime')]/b", FindTextOptions::new()->allowNull(true)->nonEmptyString());

        if (isset($lifetimeMileage)) {
            $statement->addProperty("LifetimeMileage", $lifetimeMileage);
        }

        // Balance - Kilometers Balance
        $balance = $tab->findText("//p[contains(text(), 'Kilometers Balance')]/b", FindTextOptions::new()->allowNull(true)->nonEmptyString());

        if (isset($balance)) {
            $statement->SetBalance($balance);
        }

        // provider bug fix
        if ($tab->findText('//div[@class = \'my_perogative\' and contains(., \'FreeMarker template error: The following has evaluated to null or missing: ==> memberGrade.memberGradeTypes [in template "member/index.ftl"\')]', FindTextOptions::new()->allowNull(true))) {
            $tab->gotoUrl("http://ffp.airchina.com.cn/appen/mileage/member?id=0");
            // Balance - Kilometers Balance
            $balance = $tab->findText("//span[contains(text(), 'Balance of your account')]/following-sibling::p/text()[1]", FindTextOptions::new()->allowNull(true)->timeout(10));

            if (isset($balance)) {
                $statement->SetBalance($balance);
            }
        }

        $tab->gotoUrl("http://ffp.airchina.com.cn/appen/member/manage/index");

        // Card No.
        $cardNumber = $tab->findText("//dt[contains(text(), 'Card No：')]/following-sibling::dd/text()[1]", FindTextOptions::new()->allowNull(true)->timeout(10)->nonEmptyString());

        if (isset($cardNumber)) {
            $statement->addProperty("CardNumber", $cardNumber);
        }
    }
}
