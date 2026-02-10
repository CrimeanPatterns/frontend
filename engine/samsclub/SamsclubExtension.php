<?php

namespace AwardWallet\Engine\samsclub;

use AwardWallet\ExtensionWorker\AbstractParser;
use AwardWallet\ExtensionWorker\AccountOptions;
use AwardWallet\ExtensionWorker\ContinueLoginInterface;
use AwardWallet\ExtensionWorker\Credentials;
use AwardWallet\ExtensionWorker\EvaluateOptions;
use AwardWallet\ExtensionWorker\LoginResult;
use AwardWallet\ExtensionWorker\LoginWithIdInterface;
use AwardWallet\ExtensionWorker\Message;
use AwardWallet\ExtensionWorker\ParseInterface;
use AwardWallet\ExtensionWorker\Tab;
use AwardWallet\Schema\Parser\Component\Master;
use AwardWallet\Schema\Parser\ParserTraits\TextTrait;
use stdClass;

class SamsclubExtension extends AbstractParser implements LoginWithIdInterface, ContinueLoginInterface, ParseInterface
{
    use TextTrait;
    private const CAPTCHA_MESSAGE = 'Please solve the CAPTCHA by pressing and holding “PRESS & HOLD”';

    private array $headers = [
        'Accept' => 'application/json, text/plain, */*',
        'response_groups' => 'PRIMARY',
        'seamlesslogin' => 'true',
    ];

    public function getStartingUrl(AccountOptions $options): string
    {
        return 'https://www.samsclub.com/account/summary';
    }

    public function isLoggedIn(Tab $tab): bool
    {
        $this->logger->info($tab->getUrl());
        $el = $tab->evaluate('//input[@id="email"] | //h3[contains(., "membership")] 
        | //div[@class="bst-alert-body" and contains(text(), "Let us know you’re human (no robots allowed)")]');

        return $el->getNodeName() == "H3";
    }

    public function getLoginId(Tab $tab): string
    {
        return $tab->evaluate('//h3[contains(., "membership")]/span', EvaluateOptions::new()->nonEmptyString())->getInnerText();
    }

    public function login(Tab $tab, Credentials $credentials): LoginResult
    {
        $loadResult = $tab->evaluate('//input[@id="email"] | //div[@class="bst-alert-body" and contains(text(), "Let us know you’re human (no robots allowed)")]');

        if ($loadResult->getNodeName() == "DIV") {
            $tab->showMessage(self::CAPTCHA_MESSAGE);
            $loadResult = $tab->evaluate('//input[@id="email"]', EvaluateOptions::new()->timeout(120));
        }
        $login = $loadResult;
        $login->setValue($credentials->getLogin());

        $password = $tab->evaluate('//input[@id="password"]');
        $password->setValue($credentials->getPassword());

        $tab->evaluate('//button[@type="submit"]')->click();

        $submitResult = $tab->evaluate($xpath = '//h3[contains(., "membership")]
        | //div[@class="sc-modal-close-button-container"]/button
        | //div[@id="email-error"] 
        | //div[@id="password-error"] 
        | //div[@class="bst-alert-body"]/span 
        | //div[@class="bst-alert-body" and contains(text(), "Let us know you’re human (no robots allowed)")] 
        | //p[@class="sc-2fa-enroll-inform-text"]');

        if ($submitResult->getNodeName() == "DIV" && strstr($submitResult->getInnerText(), "Let us know you’re human (no robots allowed)")) {
            $tab->showMessage(self::CAPTCHA_MESSAGE);
            $submitResult = $tab->evaluate('//h3[contains(., "membership")]
            | //div[@class="sc-modal-close-button-container"]/button
            | //div[@id="email-error"] 
            | //div[@id="password-error"] | //div[@class="bst-alert-body"]/span 
            | //p[@class="sc-2fa-enroll-inform-text"]', EvaluateOptions::new()->timeout(120));
        }
        if (stristr($submitResult->getAttribute('class'), 'modal-close-button')) {
            $submitResult->click();
            $submitResult = $tab->evaluate('//h3[contains(., "membership")]');
        }

        if ($submitResult->getNodeName() == "H3") {
            $modalClose = $tab->evaluate('//div[@class="sc-modal-close-button-container"]/button', EvaluateOptions::new()->allowNull(true));
            if ($modalClose) {
                $modalClose->click();
            };
            return new LoginResult(true);
        } elseif ($submitResult->getNodeName() == "DIV") {
            return new LoginResult(false, $submitResult->getInnerText(), null, ACCOUNT_INVALID_PASSWORD);
        } elseif ($submitResult->getNodeName() == "SPAN") {
            $error = $submitResult->getInnerText();

            if (
                strstr($error, "Your email address and password don’t match. Please try again or reset your password.")
            ) {
                return new LoginResult(false, $error, null, ACCOUNT_INVALID_PASSWORD);
            }

            if (strstr($error, "Sorry, there's a problem. Please try again later.")) {
                return new LoginResult(false, $error, null, ACCOUNT_PROVIDER_ERROR);
            }

            return new LoginResult(false, $error);
        }
        elseif($submitResult->getAttribute('class') == "sc-2fa-enroll-inform-text") {
            $radioField = $tab->evaluate('//label[contains(@class,"sc-2fa-verification-options-radio-field") and .//input[@value="email"]]
            | //form/p[contains(@class,"sc-2fa-verification-options-value")]',
                EvaluateOptions::new()->timeout(0)->allowNull(true));
            if (!$radioField) {
                $radioField = $tab->evaluate('//label[contains(@class,"sc-2fa-verification-options-radio-field") and .//input[@value="phone"]]',
                    EvaluateOptions::new()->timeout(0));
            }
            $radioField->click();
            sleep(1);
            $inputBtn = $tab->evaluate('//button[span[contains(text(), "Send Code")] or span[contains(text(), "Continue")]]');
            if (!$inputBtn) {
                $this->logger->error("something went wrong");
                return new LoginResult(false);
            }
            $inputBtn->click();
            $question = $tab->evaluate('//p[contains(text(), "Enter the 6-digit code we sent t")]');
            if (empty($question)) {
                $this->logger->error("something went wrong");
                return new LoginResult(false);
            }
            $tab->showMessage(Message::identifyComputer('Done'));
            $this->stateManager->keepBrowserSession(true);
            return LoginResult::question($question->getInnerText());
        }
        return new LoginResult(false);
    }

    public function continueLogin(Tab $tab, Credentials $credentials): LoginResult
    {
        $question = $tab->findTextNullable('//p[contains(text(), "Enter the 6-digit code we sent t")]');
        $answer = $credentials->getAnswers()[$question] ?? null;
        if ($answer === null) {
            throw new \CheckException("expected answer for the question");
        }
        $this->logger->info("sending answer: $answer");
        $inputs = $tab->evaluateAll('//div[@class="sc-passcode-box"]/input');

        foreach ($inputs as $index => $input) {
            $input->setValue($answer[$index]);
        }
        $tab->evaluate('//button[@type="submit"]')->click();
        $result = $tab->evaluate('//div[@class="bst-alert-body"] | //i[contains(text(), "Plus")]');

        if (stristr($result->getAttribute('class'),'bst-alert-body')) {
            return LoginResult::question($question, $result->getInnerText());
        } else {
            return new LoginResult(true);
        }
    }

    public function logout(Tab $tab): void
    {
        $this->logger->notice(__METHOD__);
        $tab->evaluate('//div[@class="sc-main-header-account-flyout-trigger"]')->click();
        $tab->evaluate('//a[contains(@href, "logout")]')->click();
        $tab->evaluate('//span[@class="sc-header-account-button-sign-in"]');
    }

    public function parse(Tab $tab, Master $master, AccountOptions $accountOptions): void
    {
        $isCaptcha = $tab->evaluate('//div[@class="bst-alert-body" and contains(text(), "Let us know you’re human (no robots allowed)")]',
            EvaluateOptions::new()->allowNull(true)->timeout(3));
        if ($isCaptcha) {
            $tab->showMessage(self::CAPTCHA_MESSAGE);
        }
        $tab->logPageState();
        $st = $master->createStatement();
        $st->addProperty("YourClub", urldecode($tab->getCookies()['myPreferredClubName']));

        $this->headers['Authorization'] = $tab->getFromLocalStorage('authToken');

        $options = [
            'method' => 'get',
            'headers' => $this->headers,
        ];

        $data = $tab->fetch('https://www.samsclub.com/api/node/vivaldi/account/v3/membership?ts='.time().date('B'), $options)->body;
        //$this->logger->info($data);
        $response = json_decode($data);

        // Name
        $member = $response->payload->member[0];
        $st->addProperty("Name", beautifulName($member->memberName->firstName . ' ' . $member->memberName->lastName));
        // Account
        $st->addProperty("Account", $response->payload->membership->membershipId);
        // Status
        $st->addProperty("Status", $response->payload->membership->membershipType);
        // Club member since
        if (
            isset($response->payload->membership->startDate)
            && ($memberSince = strtotime($response->payload->membership->startDate, false))
        ) {
            $st->addProperty("MemberSince", date('Y', $memberSince));
        }

        // Membership Expiration
        if (
            isset($response->payload->renewalInfo->expiryDate)
            && ($exp = $this->findPreg('/(\d{4}-\d+-\d+)T/', $response->payload->renewalInfo->expiryDate))
            && ($exp = strtotime($exp, false)) // May 19, 2019
        ) {
            $st->addProperty("MembershipExpiration", date('M d, Y', $exp));
        }
        $options = [
            'method' => 'get',
            //'headers' => $this->headers,
        ];

        $data = $tab->fetch('https://www.samsclub.com/api/node/vivaldi/account/v4/membership/member-perks', $options)->body;
        //$this->logger->info($data);
        $response = json_decode($data);
        // in savings
        if (isset($response->savingsTotal)) {
            $st->addProperty("YTDSavings", "$" . $response->savingsTotal);
        }
        $savings = $response->savings ?? [];

        foreach ($savings as $saving) {
            switch ($saving->name) {
                // Cash Rewards
                case 'cashRewards':
                case 'samsCash':
                $st->addProperty('TotalEarnedRewards', "$" . $saving->value);

                    break;
                // Everyday club savings
                case 'compSavings':
                    $st->addProperty('ClubSavings', "Est. $" . $saving->value);

                    break;
                // Free shipping for Plus
                case 'freeShipping':
                    $st->addProperty('FreeShipping', "$" . $saving->value);

                    break;
                // Instant Savings
                case 'instantSavings':
                    $st->addProperty('InstantSavings', "$" . $saving->value);

                    break;

                default:
                    $this->logger->notice("Unknown saving type: {$saving->name}");
            }// switch ($saving->name)
        }// foreach ($savings as $saving)

        // Balance - Cash Rewards (Available now)
        $data = $tab->fetch('https://www.samsclub.com/api/node/vivaldi/account/v3/sams-wallet?response_group=full', $options)->body;
        //$this->logger->info($data);
        $response = json_decode($data);
        $balance = $response->payload->storedValueCards->samsRewards->balance->amount ?? null;
        if (isset($balance)) {
            $st->setBalance($balance);
        }

        // for Account with zero balance
        if (
            !$balance
            && !isset($response->payload->storedValueCards)
            && (isset($response->payload->paymentCards) || (isset($response->payload) && $response->payload == new stdClass()))
            && (!empty($st->getProperties()['ClubSavings']) || !empty($st->getProperties()['YTDSavings']) || !empty($st->getProperties()['InstantSavings']))
            && !empty($st->getProperties()['MemberSince'])
            && !empty($st->getProperties()['Account'])
        ) {
            $st->setNoBalance(true);
        }
    }
}
