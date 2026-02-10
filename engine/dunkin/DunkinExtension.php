<?php

namespace AwardWallet\Engine\dunkin;

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

class DunkinExtension extends AbstractParser implements LoginWithIdInterface, ParseInterface, ContinueLoginInterface
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
        return "https://www.dunkindonuts.com/en/sign-in";
    }

    public function isLoggedIn(Tab $tab): bool
    {
        $result = $tab->evaluate('//div[contains(@class,"js-utility-bar-loggedIn") and contains(@style,"display")]',
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
        $tab->evaluate('//input[@id="email"]')->setValue($credentials->getLogin());
        $tab->evaluate('//input[@id="password"]')->setValue($credentials->getPassword());
        $tab->evaluate('//input[@value="Sign In"]')->click();

        $result = $tab->evaluate('//p[contains(@class,"u-page-error")] 
        | //div[contains(@class,"u-page-error")] 
        | //h1/span[contains(text(),"HELP US KEEP YOUR ACCOUNT SECURE")]
        | //button[contains(@class,"dltMyAccount__my-account-btn")]
        | //div[contains(@class,"my-rewards__heading")]',
            EvaluateOptions::new()->timeout(40)->allowNull(true));
        if ($result) {
            if (stripos($result->getAttribute('class'), 'dltMyAccount__my-account-btn') !== false
            || stripos($result->getAttribute('class'), 'my-rewards__heading') !== false)
            return LoginResult::success();
        }
        if ($result && stripos($result->getInnerText(), 'Sorry, we are unable to process your log in at this time.') !== false) {
            return LoginResult::providerError($result->getInnerText());
        }
        if ($result && stripos($result->getInnerText(), 'Profile information is invalid') !== false) {
            return LoginResult::invalidPassword($result->getInnerText());
        }
        if ($result && stripos($result->getInnerText(), 'HELP US KEEP YOUR ACCOUNT SECURE') !== false) {
            $tab->showMessage(Message::identifyComputerSelect("SEND CODE"));

            if ($this->context->isServerCheck()) {
                $tab->logPageState();
                $tab->evaluate('//input[@value="SEND CODE"]')->click();
                $phone = $tab->findText('//div[contains(text(), "security code we just sent to") and contains(text(), "•••")]',
                    FindTextOptions::new()->timeout(30)->allowNull(true)->preg('/sent to (.+?\d{4})\./'));
                if ($phone) {
                    $this->logger->debug(">>> phone number: {$phone}");
                    $question = sprintf(self::PHONE_OTC_QUESTION, $phone);

                    if (!$this->context->isBackground() || $this->context->isMailboxConnected()) {
                        $this->stateManager->keepBrowserSession(true);
                    }

                    return LoginResult::question($question);
                }
            } else {
                $result = $tab->evaluate('//input[@id="accessCodeInput"]',
                    EvaluateOptions::new()->timeout(30)->allowNull(true));
                if ($result) {
                    $tab->showMessage(Message::identifyComputer("CONTINUE"));
                    $result = $tab->evaluate('//button[contains(@class,"dltMyAccount__my-account-btn")]',
                        EvaluateOptions::new()->timeout(120)->allowNull(true));

                    if ($result) {
                        return new LoginResult(true);
                    } else {
                        return LoginResult::identifyComputer();
                    }
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
        $st = $master->createStatement();

        $options = [
            'method' => 'post',
            'headers' => $this->headers,
        ];

        $data = $tab->fetch('https://www.dunkindonuts.com/bin/servlet/cval', $options)->body;
        //$this->logger->info($data);
        $userInfo = json_decode(json_decode($data)->usinf ?? null);

        // Name
        $perksUser = $userInfo->perksUser;
        $st->addProperty("Name", beautifulName($userInfo->firstName . " " . $userInfo->lastName));
        $loyaltyPoints = $userInfo->loyaltyPoints;
        if ($loyaltyPoints !== '') {
            // Balance - Points You've Earned
            $st->setBalance($loyaltyPoints);
            // UNTIL NEXT REWARD
            $st->addProperty("PointsToNextReward", 200 - $loyaltyPoints);
        } // User is not member of this loyalty program
        elseif ($perksUser === false) {
            throw new NotAMemberException();
        }

        $this->logger->info('My Rewards', ['Header' => 3]);
        $options = [
            'method' => 'post',
            'headers' => $this->headers,
            'body' => '{"service":"getAllCertificates"}'
        ];

        $data = $tab->fetch('https://www.dunkindonuts.com/bin/servlet/loyalty', $options)->body;
        //$this->logger->info($data);
        $data = json_decode($data);
        $rewards = $data->data->certificateList ?? [];
        //$this->logger->debug("Total " . count($rewards) . " rewards were found");

        foreach ($rewards as $key => $reward) {
            $this->logger->info("certificate key => $key");
            $displayName = $reward->strCertificateName;
            $exp = $rewards->trExpiryDate;
            $couponNumber = $rewards->strCertificateNumber;
            $st->addSubAccount([
                'Code' => 'dunkinRewards' . $couponNumber,
                'DisplayName' => $displayName,
                'Balance' => null,
                'ExpirationDate' => strtotime($exp),
                'CouponNumber' => $couponNumber,
            ]);
        }


        // Expiration date  // refs #14211, 23510
        $this->logger->info('Expiration date', ['Header' => 3]);

        if ($st->getBalance() > 0) {
            $requestDate = time();
            $page = 1;

            do {
                $this->logger->debug("Page: {$page}");
                $options = [
                    'method' => 'post',
                    'headers' => $this->headers,
                    'body' => '{"month":"' . date('n', $requestDate) . '","year":"' . date('Y',
                            $requestDate) . '","service":"getAccountTransactions"}'
                ];
                $data = $tab->fetch('https://www.dunkindonuts.com/bin/servlet/loyalty', $options)->body;
                $data = json_decode($data);
                $transactions = $data->data->transactionList ?? [];
                $this->logger->debug("Total " . count($transactions) . " transaction were found");

                foreach ($transactions as $transaction) {
                    $purchase = $transaction->transactiontype == "Purchase";
                    $date = $transaction->postedDateTime;

                    if ($purchase) {
                        // Last activity
                        $st->addProperty("LastActivity", $date);
                        if ($exp = strtotime($date)) {
                            $st->setExpirationDate(strtotime("+6 month", $exp));
                        }

                        break;
                    }
                }
                $requestDate = strtotime("-1 month", $requestDate);
                $page++;
            } while (!isset($st->getProperties()['LastActivity']) && $page < 12);
        }


        // refs #20935
        // All Dunkin Cards
        $this->logger->info('Dunkin Cards', ['Header' => 3]);
        //$tab->gotoUrl("https://www.dunkindonuts.com/en/account/transaction-history#");
        $headers = $this->headers;
        $headers['Content-Type'] = 'application/x-www-form-urlencoded; charset=UTF-8';

        $options = [
            'method' => 'post',
            'headers' => $headers,
            'body' => 'service=getDdCardDetails'
        ];
        try {
            $dataTransactions = $tab->fetch('https://www.dunkindonuts.com/bin/servlet/ddcard', $options)->body;
            $this->logger->info($dataTransactions);
            $dataTransactions = json_decode($dataTransactions);
            $transactions = $dataTransactions->data ?? [];
            $this->logger->debug("Total " . count($transactions) . " cards were found");

            foreach ($transactions as $transaction) {
                $cardNumber = $transaction->cardNumber;
                $lastFourDigits = $transaction->lastFourDigits;
                $balance = $transaction->balance->balance;
                $st->addSubAccount([
                    'Code' => 'dunkinCard' . md5($cardNumber) . $lastFourDigits,
                    'DisplayName' => 'Card #••••••••••••' . $lastFourDigits,
                    'Balance' => $balance,
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }


}
