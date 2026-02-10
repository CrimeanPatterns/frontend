<?php

namespace AwardWallet\Engine\aeromexico;

use AwardWallet\Common\Parsing\Html;
use AwardWallet\ExtensionWorker\AbstractParser;
use AwardWallet\ExtensionWorker\AccountOptions;
use AwardWallet\ExtensionWorker\Credentials;
use AwardWallet\ExtensionWorker\EvaluateOptions;
use AwardWallet\ExtensionWorker\FindTextOptions;
use AwardWallet\ExtensionWorker\LoginResult;
use AwardWallet\ExtensionWorker\LoginWithIdInterface;
use AwardWallet\ExtensionWorker\ParseInterface;
use AwardWallet\ExtensionWorker\Message;
use AwardWallet\ExtensionWorker\Tab;
use AwardWallet\Schema\Parser\Component\Master;

class AeromexicoExtension extends AbstractParser implements LoginWithIdInterface, ParseInterface
{
    public function getStartingUrl(AccountOptions $options): string
    {
        return 'https://member.aeromexicorewards.com/';
    }

    public function isLoggedIn(Tab $tab): bool
    {
        $this->logger->info($tab->getUrl());

        $el = $tab->evaluate('//input[@id="account"] | //h3[@class="as-cp-account-number"]');

        return $el->getNodeName() == "H3";
    }

    public function getLoginId(Tab $tab): string
    {
        return $tab->evaluate('//h3[@class="as-cp-account-number"]')->getInnerText();
    }

    public function login(Tab $tab, Credentials $credentials): LoginResult
    {
        $login = $tab->evaluate('//input[@id="account"]');
        $login->setValue($credentials->getLogin());
        $password = $tab->evaluate('//input[@id="password"]');
        $password->setValue($credentials->getPassword());
        $tab->evaluate('//button[@id="btnSubmit" and not(@disabled)]')->click();
        /*
        $submitResult = $tab->evaluate('
            //div[contains(@class, "alert-code-security") and not(contains(@class, "d-none"))]
            | //div[contains(@class, "invalid-feedback")]/span
            | //h3[@class="as-cp-account-number"]
            | //div[@class="alert alert-danger alert-general"]/p
        ', EvaluateOptions::new()->visible(false));
        */

        $submitResult = $tab->evaluate('
            //div[contains(@class, "alert-code-security") and not(contains(@class, "d-none"))]
            | //h3[@class="as-cp-account-number"]
            | //div[@class="alert alert-danger alert-general"]/p
        ', EvaluateOptions::new()->visible(false));

        if ($submitResult->getNodeName() == 'H3') {
            return new LoginResult(true);
        } elseif ($submitResult->getNodeName() == 'SPAN') {
            return new LoginResult(false, $submitResult->getInnerText(), null, ACCOUNT_INVALID_PASSWORD);
        } elseif ($submitResult->getNodeName() == 'DIV') {
            $tab->showMessage(Message::identifyComputer("Confirmar"));
            $loginIDElement = $tab->evaluate('//span[@data-testid="member-number-formatted"]', EvaluateOptions::new()->nonEmptyString()->timeout(90)->allowNull(true));

            if ($loginIDElement) {
                return new LoginResult(true);
            } else {
                return LoginResult::identifyComputer();
            }
        } else {
            $error = $submitResult->getInnerText();

            if (
                strstr($error, "El número de cuenta y/o contraseña son incorrectos.")
            ) {
                return new LoginResult(false, $error, null, ACCOUNT_INVALID_PASSWORD);
            }

            return new LoginResult(false, $error);
        }
    }

    public function logout(Tab $tab): void
    {
        $this->logger->notice(__METHOD__);
        $tab->evaluate('//div[@class="userinfo"]/following-sibling::div//a[contains(@href, "salir")]')->click();
        $tab->evaluate('//input[@id="account"]');
    }

    public function parse(Tab $tab, Master $master, AccountOptions $accountOptions): void
    {
        $tab->logPageState();
        $statement = $master->createStatement();
        $language = $tab->findText("
            //div[contains(@class, 'select-lang')]/a/span
            | //div[contains(@class, 'container')]//div[contains(@class, 'language')]//option[@selected]
        ", FindTextOptions::new()->allowNull(true)->timeout(10));
        $language = Html::cleanXMLValue($language);
        $this->logger->notice(">>> Language: " . $language);

        if (
            !in_array($language, ['Inglés', 'English']) 
            && !$tab->findText("//p[contains(text(), 'Balance:')]", FindTextOptions::new()->allowNull(true))
        ) {
            $this->logger->notice(">>> Switch to English version");
            $tab->gotoUrl("https://member.aeromexicorewards.com/?lang=en");
        }

        // Balance - My balance is ... Premier Points
        $balance1 = $tab->findText("//div[@class='userinfo']//p//text()[contains(., 'Balance:')]", FindTextOptions::new()->allowNull(true)->preg("/:\s*([\-\d\.\,]+)\s*Premier/ims"));
        $balance2 = $tab->findText("//div[@class='userinfo']//span[contains(text(), 'Balance')]/following-sibling::span[1]", FindTextOptions::new()->allowNull(true));
        if (isset($balance1)) {
            $statement->setBalance($balance1);
        }
        if (isset($balance2)) {
            $statement->SetBalance( $balance2);
        }

        // Name
        $name1 = $tab->findText('//div[contains(@class, "row home-tutorial")]//div[@class = "name"]', FindTextOptions::new()->allowNull(true)->preg("/>?(.+)/ims"));
        $name2 = $tab->findText("//div[@class='userinfo']//span[contains(text(), 'Hello')]/following-sibling::span[1]", FindTextOptions::new()->allowNull(true));
        if (isset($name1)) {
            $statement->addProperty("Name", beautifulName($name1));
        }
        if (isset($name2)) {
            $statement->addProperty("Name", beautifulName($name2));
        }

        // Membership Number
        $membershipNumber1 = $tab->findText("//div[@class = 'account']/text()/following-sibling::span", FindTextOptions::new()->allowNull(true));
        $membershipNumber2 = $tab->findText("//div[@class='userinfo']//span[contains(text(), 'Account number:')]/following-sibling::strong[1]", FindTextOptions::new()->allowNull(true));
        if (isset($name1)) {
            $statement->addProperty("Membership Number", $membershipNumber1);
        }
        if (isset($name2)) {
            $statement->addProperty("Membership Number", $membershipNumber2);
        }

        // Level - in english version, level not showing
        $levelEnglish = $tab->findText("//div[contains(@class, \"row home-tutorial\")]//div[@class = 'card-user']//img[@class = 'img-fluid']/@src", FindTextOptions::new()->allowNull(true));
        $tab->gotoUrl("https://member.aeromexicorewards.com/individual/movimientos-por-fecha");
        // Balance - Saldo Actual
        $balance = $tab->findText("//div[contains(text(), 'Saldo Actual')]/following-sibling::div", FindTextOptions::new()->allowNull(true)->preg("/\s*([\-\d\.\,]+)\s*Puntos/ims"));
        if (isset($balance)) {
            $statement->setBalance($balance);
        }

        // Level - in english version, level not showing
        $level = $levelEnglish;

        if ($levelEnglish) {
            $level = basename($level);
            $this->logger->debug(">>> Level " . $level);

            switch ($level) {
                case 'bannerVisa.jpg':
                    // may be Clasico
                case 'AeromexicoVisaSignature.png':
                case 'AeromexicoVisaCard.png':
                case 'cp-card-test.png':
                case 'cp-one.png':
                    $statement->addProperty("Level", "Clasico");

                    break;

                case 'AeromexicoGold.png':
                case 'cp-card-test-oro.png':
                    $statement->addProperty("Level", "Gold");

                    break;

                case 'AeromexicoPlatino.png':
                case 'cp-card-test-platino.png':
                    $statement->addProperty("Level", "Platinum");

                    break;

                case 'AeromexicoTitanio.png':
                case 'cp-card-test-titanio.png':
                    $statement->addProperty("Level", "Titanium");

                    break;

                default:
                    $this->notificationSender->sendNotification("refs #25443 aeromexico - new elite level was found // IZ");
            }
        }
    }
}
