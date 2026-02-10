<?php

namespace AwardWallet\Engine\dbs;

use AwardWallet\Common\Parsing\Exception\NotAMemberException;
use AwardWallet\ExtensionWorker\AbstractParser;
use AwardWallet\ExtensionWorker\AccountOptions;
use AwardWallet\ExtensionWorker\ContinueLoginInterface;
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
use Aws\Result;
use function AwardWallet\ExtensionWorker\beautifulName;

class DbsExtension extends AbstractParser implements LoginWithIdInterface, ParseInterface, ContinueLoginInterface
{
    use TextTrait;
    private const PHONE_OTC_QUESTION = 'Please enter Verification Code which was sent to your phone %s. Please note that you must provide the latest code that was just sent to you. Previous codes will not work.';
    private int $stepItinerary = 0;

    private array $headers = [
        'Accept' => '*/*',
        'csrf-token' => 'undefined',
        'Content-Type' => 'application/json',
        'x-requested-with' => 'XMLHttpRequest',
    ];
    /**
     * @var array|bool|float|int|mixed|\stdClass|string|null
     */
    private $profile;


    public function getStartingUrl(AccountOptions $options): string
    {
        return "https://iam.dbs.com.sg/iam/v1/authorize?response_type=code&client_id=ccrwd01&redirect_uri=https://rewards.dbs.com/redirectPage.aspx";
    }

    public function isLoggedIn(Tab $tab): bool
    {
        // TODO
        $result = $tab->evaluate('//input[@name="username"]',
            EvaluateOptions::new()->visible(false));
        return stristr($result->getAttribute('style'), 'flex;');
    }

    public function getLoginId(Tab $tab): string
    {
        try {
            $headers = $this->headers;
            $headers['Content-Type'] = 'application/x-www-form-urlencoded';
            $accountOptions = [
                'method' => 'post',
                'headers' => $headers,
                'body' => 'service=getProfileInfo',
            ];
            $profile = $tab->fetch("https://www.dunkindonuts.com/bin/servlet/profile", $accountOptions)->body;
            $this->profile = json_decode($profile);
            return $this->profile->data->userName ?? '';
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
        return '';
    }

    public function logout(Tab $tab): void
    {
        $tab->evaluate('//button[contains(text(),"SIGN OUT")]', EvaluateOptions::new()->visible(false))->click();
        $tab->evaluate('//a[contains(text(),"SIGN IN")]');
    }

    public function login(Tab $tab, Credentials $credentials): LoginResult
    {
        sleep(3); // Loader page
        $acceptAll = $tab->evaluate('//button[@aria-label="Accept All"]', EvaluateOptions::new()->allowNull(true)->timeout(3));
        if ($acceptAll) {
            $acceptAll->click();
        }
        $tab->evaluate('//input[@name="username"]')->setValue($credentials->getLogin());
        $tab->evaluate('//input[@name="password"]')->setValue($credentials->getPassword());
        $tab->evaluate('//button[contains(text(), "Login")]')->click();

        // TODO
        $result = $tab->evaluate('//div[contains(@id,"errormsg")] 
        | //h1[strong[contains(text(), "Two Step Verification")]]
        ', EvaluateOptions::new()->timeout(40)->allowNull(true)->nonEmptyString());
        $this->notificationSender->sendNotification('check login // MI');
        $innerText = $result ? $result->getInnerText() : null;
        if ($result) {
            if (stripos($result->getAttribute('class'), 'dltMyAccount__my-account-btn') !== false
            || stripos($result->getAttribute('class'), 'my-rewards__heading') !== false)
                return LoginResult::success();
        }
        if ($innerText && stripos($innerText, 'The PIN length should range from 6 to 9 digits.') !== false) {
            return LoginResult::invalidPassword($innerText);
        }
        if ($innerText && stripos($innerText,
                'This service is temporarily unavailable. Please try again at a later time.') !== false) {
            return LoginResult::providerError($innerText);
        }

        if ($innerText && stripos($innerText, 'Two Step Verification') !== false) {
            $tab->showMessage(Message::identifyComputerSelect("Authenticate"));

            if ($this->context->isServerCheck()) {
                $tab->logPageState();
            } else {

                // TODO
                try {
                    $result = $tab->evaluate('//button[contains(@class,"dltMyAccount__my-account-btn")]',
                        EvaluateOptions::new()->timeout(120));
                    $tab->logPageState();
                } catch (\Exception $e) {
                    $this->logger->error($e->getMessage());
                }
                if ($result) {
                    return new LoginResult(true);
                } else {
                    return LoginResult::identifyComputer();
                }
            }
        }
        return new LoginResult(false);
    }

    public function continueLogin(Tab $tab, Credentials $credentials): LoginResult
    {
        $phone = $tab->findText('//div[contains(text(), "security code we just sent to") and contains(text(), "•••")]',
            FindTextOptions::new()->timeout(30)->allowNull(true)->preg('/sent to (.+?\d{4})\./'));
        if ($phone) {
            $this->logger->debug(">>> phone number: {$phone}");
            $question = sprintf(self::PHONE_OTC_QUESTION, $phone);
            $answer = $credentials->getAnswers()[$question] ?? null;
            if ($answer === null) {
                throw new \CheckException("expected answer for the question");
            }

            $input = $tab->evaluate('//input[@id="accessCodeInput"]');
            $input->setValue($answer);
            $tab->evaluate('//button[@value="CONTINUE"]')->click();
            $submitResult = $tab->evaluate('//li[contains(@class, "parsley-accessCodeError")] | //button[contains(@class,"dltMyAccount__my-account-btn")]');
            if ($submitResult->getAttribute('class') === 'parsley-accessCodeError') {
                return LoginResult::question($question, $submitResult->getInnerText());
            }
            if ($submitResult->getAttribute('class') === 'dltMyAccount__my-account-btn') {
                return LoginResult::success();
            }
        }
        $tab->logPageState();
        return new LoginResult(false);
    }

    public function parse(Tab $tab, Master $master, AccountOptions $accountOptions): void
    {
        $tab->gotoUrl("https://rewards.dbs.com/ShoppingCart.aspx");
        sleep(10);
        $tab->logPageState();
        $tab->evaluate('//span[@id = "spnTotalPts"]');
        $st = $master->createStatement();

        // Current Total
        $st->addProperty('Total', $tab->findTextNullable('//span[@id = "spnTotalPts"]'));
        // Current Redeemed Total
        $st->addProperty('Redeemed', $tab->findTextNullable('//span[@id = "cartTotal"]'));
        // Balance - DBS Points
        /**
         * ar balance = parseInt($('#spnTotalPts').html()) - parseInt($('#cartTotal').html());
         * $('#balancePts').html(balance + '');.
         */
        if (isset($st->getProperties()['Total'], $st->getProperties()['Redeemed'])) {
            $st->setBalance($st->getProperties()['Total'] - $st->getProperties()['Redeemed']);
        }
        // Name
        $st->addProperty('Name',
            beautifulName($tab->findTextNullable('//span[@id = "lblUserName" and not(contains(text(), "Rewards"))]')));

        /* $cards = $tab->evaluateAll("//table[@class = 'pointstable']//tr");
       foreach ($cards as $card) {
           $expiringBalance = 0;
           $exp = null;
           $displayName = trim($this->http->FindSingleNode('td[1]', $card, true, "/XXXX(\d+)/"));
           $balance = trim($this->http->FindSingleNode('td[7]', $card));
           // fin nearest exp date
           for ($i = 2; $i < 6; $i++) {
               $expDate = $this->http->FindSingleNode('//tr[td[table[@class = "pointstable"]]]/preceding-sibling::tr[1]/td[' . $i . ']', null, true, "/Expiring\s*([^<]+)/");
               $expPoints = trim($this->http->FindSingleNode('td[' . $i . ']', $card));
               $this->logger->debug("[Card #{$displayName}]: $expDate - $expPoints");

               if ($expPoints > 0 && (!isset($exp) || $exp >= strtotime($expDate))) {
                   $expiringBalance = $expPoints;
                   $exp = strtotime("+1 month -1 day", strtotime($expDate));
               }// if ($expPoints > 0 && (!isset($exp) || $exp >= strtotime($expDate)))
           }// for ($i = 3; $i < 6; $i++)

           if ($displayName) {
               $this->AddSubAccount([
                   "Code"              => 'dbsCard' . $displayName,
                   "DisplayName"       => "Credit Card ending in {$displayName}",
                   "Balance"           => $balance,
                   "ExpiringBalance"   => $expiringBalance,
                   "ExpirationDate"    => $exp ?? false,
                   "BalanceInTotalSum" => true,
               ], true);
           }
       }*/


    }


}
