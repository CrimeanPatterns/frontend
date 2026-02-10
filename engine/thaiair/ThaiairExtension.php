<?php

namespace AwardWallet\Engine\thaiair;

use AwardWallet\ExtensionWorker\AbstractParser;
use AwardWallet\ExtensionWorker\AccountOptions;
//use AwardWallet\ExtensionWorker\ContinueLoginInterface;
use AwardWallet\ExtensionWorker\Credentials;
use AwardWallet\ExtensionWorker\EvaluateOptions;
use AwardWallet\ExtensionWorker\LoginResult;
use AwardWallet\ExtensionWorker\LoginWithIdInterface;
use AwardWallet\ExtensionWorker\Message;
use AwardWallet\ExtensionWorker\Tab;
use AwardWallet\Schema\Parser\ParserTraits\TextTrait;

class ThaiairExtension extends AbstractParser implements LoginWithIdInterface /*, ContinueLoginInterface*/
{
    use TextTrait;

    private $question = '';

    public function getStartingUrl(AccountOptions $options): string
    {
        return 'https://osci.thaiairways.com/en-th/rop/my-profile';
    }

    public function isLoggedIn(Tab $tab): bool
    {
        $this->logger->info($tab->getUrl());
        $el = $tab->evaluate('//span[normalize-space()="My Details"] | //button[@aria-label="button Sign in"]');

        return $el->getNodeName() == "SPAN";
    }

    public function getLoginId(Tab $tab): string
    {
        return $tab->evaluate('//text()[normalize-space()="My Profile"]/following::text()[string-length()=7][1]', EvaluateOptions::new()->nonEmptyString())->getInnerText();
    }

    public function login(Tab $tab, Credentials $credentials): LoginResult
    {
        $showLoginForm = $tab->evaluate('//button[@aria-label="button Sign in"]');

        if ($showLoginForm) {
            $showLoginForm->click();
            $tab->evaluate('//input[@name = "memberId"]');
        }

        $login = $tab->evaluate('//input[@name = "memberId"]');
        $login->setValue($credentials->getLogin());

        $password = $tab->evaluate('//input[@name = "password"]');
        $password->setValue($credentials->getPassword());

        $tab->evaluate('//button[@aria-label="Sign In "]')->click();

        $submitResult = $tab->evaluate('//div[@class="form-detail"]//b | //div[@class="rop-hero-slider"]//span[contains(@class, "member-id")] | //input[@placeholder="Enter your OTP code"] | //input[@name="otpKey"]');

        if ($submitResult->getNodeName() == 'SPAN') {
            return new LoginResult(true);
        }

        if ($submitResult->getNodeName() == 'INPUT') {
            /*$this->question = $tab->evaluate('//div[contains(text(), "OTP code")]')->getInnerText();

            if (!empty($this->question)) {
                $this->logger->debug('Question: ' . $this->question);

                $tab->logPageState();

                if ($this->context->isMailboxConnected()) {
                    $this->stateManager->keepBrowserSession(true);
                }

                return LoginResult::question($this->question);
            } else {*/

            $tab->showMessage(Message::identifyComputer("Confirm"));

            //}

            $errorOtpCode = $tab->evaluate('//text()[contains(normalize-space(), "Incorrect OTP")]/ancestor::div[contains(@class, "undefined")][1] | //button[normalize-space()="Book Flight"] | //span[normalize-space()="My Profile"]',
                EvaluateOptions::new()
                    ->timeout(90)
                    ->allowNull(true));

            if ($errorOtpCode === null) {
                $noEnterCode = $tab->evaluate("//text()[contains(normalize-space(), 'OTP code')]/ancestor::div[1]",
                    EvaluateOptions::new()
                        ->allowNull(true));

                if ($noEnterCode->getNodeName() == 'DIV') {
                    return LoginResult::identifyComputer();
                }
            }

            if ($errorOtpCode->getNodeName() == 'BUTTON' || $errorOtpCode->getNodeName() == 'SPAN') {
                $tab->gotoUrl("https://osci.thaiairways.com/en-th/rop/my-profile");

                $result = $this->waiter->waitFor(function () use ($tab) {
                    return $tab->evaluate('//span[normalize-space()="My Details"]',
                        EvaluateOptions::new()
                            ->allowNull(true)
                            ->timeout(0));
                });

                if ($result) {
                    return LoginResult::success();
                }
            } else {
                return new LoginResult(false);
            }
        }

        return new LoginResult(false);
    }

    public function logout(Tab $tab): void
    {
        $this->logger->notice(__METHOD__);

        $myProfile = $tab->evaluate('//span[normalize-space()="My Profile"]',
            EvaluateOptions::new()
                ->allowNull(true));

        if ($myProfile === null) {
            $menuOut = $tab->evaluate('//div[@role="menu"]/descendant::div[last()]/descendant::span[2]',
                EvaluateOptions::new()
                    ->allowNull(true));

            if ($menuOut) {
                $menuOut->click();
            }
        } else {
            $myProfile->click();
        }

        $tab->evaluate('//div[@aria-labelledby="Sign Out"]')->click();

        $tab->evaluate('//span[@aria-label="Sign In "] | //div[@id="sign_in_panel"]');
    }

    public function continueLogin(Tab $tab, Credentials $credentials): LoginResult
    {
        $this->logger->notice(__METHOD__);

        $inputs = $tab->evaluateAll('//input[@name="otpKey"]');

        $this->logger->debug('Count Inputs: ' . count($inputs));

        if (count($inputs) !== 4) {
            throw new \CheckException("expected 4 inputs, got " . count($inputs), ACCOUNT_ENGINE_ERROR);
        }

        $answer = $credentials->getAnswers()[$this->question] ?? null;

        if ($answer === null) {
            throw new \CheckException("expected answer for the question");
        }

        $this->logger->debug('OTP Code: ' . $answer);

        if (strlen($answer) !== 4 || !preg_match('/^\d{4}$/i', $answer)) {
            return LoginResult::question($this->question, 'Expected -digits code');
        }

        for ($i = 0; $i < 4; $i++) {
            $inputs[$i]->click();
            $inputs[$i]->setValue(substr($answer, $i, 1));
        }

        $tab->querySelector("//button[@aria-label='Confirm']")->click();

        $errorOtpCode = $tab->evaluate('//text()[contains(normalize-space(), "Incorrect OTP")]/ancestor::div[contains(@class, "undefined")][1]', EvaluateOptions::new()->allowNull(true));

        if (!$errorOtpCode) {
            $tab->gotoUrl("https://osci.thaiairways.com/en-th/rop/my-profile");
        } else {
            return new LoginResult(false, $errorOtpCode->getInnerText());
        }

        $submitResult = $tab->evaluate('//text()[contains(normalize-space(), "Incorrect OTP")]/ancestor::div[contains(@class, "undefined")][1] | //span[normalize-space()="My Details"]');

        if (stripos($submitResult->getAttribute('class'), 'undefined') !== false) {
            $this->stateManager->keepBrowserSession(true);

            return LoginResult::question($this->question, $submitResult->getInnerText());
        }

        if ($submitResult->getNodeName() === 'SPAN') {
            return LoginResult::success();
        }

        $tab->logPageState();

        return new LoginResult(false);
    }
}
