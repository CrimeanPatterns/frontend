<?php

namespace AwardWallet\Engine\etihad;

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

class EtihadExtension extends AbstractParser implements LoginWithIdInterface, ParseInterface, ContinueLoginInterface
{
    private const EMAIL_OTC_QUESTION = 'Please enter the five-digit verification code sent to your email address. This code is valid for 15 minutes.';

    public function getStartingUrl(AccountOptions $options): string
    {
        return 'https://www.etihad.com/en/etihadguest/profile';
    }

    public function isLoggedIn(Tab $tab): bool
    {
        $this->logger->info($tab->getUrl());
        $loginFieldOrBalance = $tab->evaluate('//span[contains(@class, "logged-in-user-initial")] | //div[contains(@class, "login-dd-open")] | //button[@id="submitLogin"] | //span[@data-ng-bind = "cmnCtrl.checkComma(accSummeryModel.accdata.guestmiles)"]', EvaluateOptions::new()->nonEmptyString());

        return $loginFieldOrBalance->getNodeName() === 'SPAN';
    }

    public function getLoginId(Tab $tab): string
    {
        $mobile = $tab->evaluate("//a[normalize-space() = 'View card']", EvaluateOptions::new()->allowNull(true));

        if ($mobile) {
            $mobile->click();
            $loginId = $tab->evaluate("//text()[starts-with(normalize-space(), 'Etihad Guest account number')]/following::text()[normalize-space()][1]");

            return $loginId->getInnerText();
        }

        $loginId = $tab->evaluate("//img[contains(@src, 'profile')]/preceding::span[1][contains(translate(normalize-space(.),'0123456789','dddddddddd'),'dddd')]");

        return str_replace(' ', '', $loginId->getInnerText());
    }

    public function login(Tab $tab, Credentials $credentials): LoginResult
    {
        $login = $tab->evaluate('//input[@id="email"]');
        $login->setValue($credentials->getLogin());

        $password = $tab->evaluate('//input[@id="password"]');
        $password->setValue($credentials->getPassword());

        $button = $tab->evaluate('//button[normalize-space()="Log in"]');
        $button->click();

        $result = $this->waiter->waitFor(function () use ($tab) {
            return !$tab->evaluate('//button[normalize-space()="Log in"]', EvaluateOptions::new()->allowNull(true)->timeout(3));
        });

        if (!$result) {
            $button->click();
        }

        $errorOrTitle = $tab->evaluate('//input[@name="otp"]/preceding::div[1][contains(normalize-space(), "email")] | //span[contains(@class, "logged-in-user-initial")]', EvaluateOptions::new()->visible(false)->nonEmptyString());

        if ($errorOrTitle->getNodeName() === 'SPAN') {
            $this->logger->info('logged in');

            return new LoginResult(true);
        } else {
            $this->logger->info('error logging in');
            $error = $errorOrTitle->getInnerText();

            if (str_starts_with($error, "Please enter in the one-time") || str_starts_with($error, "Please enter the five")) {
                $tab->showMessage(Message::identifyComputer('Verify'));

                $result = $this->waiter->waitFor(function () use ($tab) {
                    return $tab->evaluate('//span[contains(@class, "logged-in-user-initial")] | //div[contains(@class, "notification--error")]', EvaluateOptions::new()->allowNull(true)->timeout(90));
                });

                if ($result) {
                    $singIn = $tab->evaluate('//span[contains(@class, "logged-in-user-initial")]', EvaluateOptions::new()->allowNull(true));

                    if ($singIn) {
                        return new LoginResult(true);
                    }

                    $signInError = $tab->evaluate('//div[contains(@class, "notification--error")]/descendant::div[normalize-space()][2]', EvaluateOptions::new()->allowNull(true));

                    if ($signInError) {
                        return new LoginResult(false, $signInError->getInnerText());
                    }
                }

                return new LoginResult(false, $error);
            }

            return new LoginResult(false, $error);
        }
    }

    public function continueLogin(Tab $tab, Credentials $credentials): LoginResult
    {
        $inputs = $tab->evaluateAll('//input[@name="otp"]');

        if (count($inputs) !== 5) {
            throw new \CheckException("expected 5 inputs, got " . count($inputs), ACCOUNT_ENGINE_ERROR);
        }

        $answer = $credentials->getAnswers()[self::EMAIL_OTC_QUESTION] ?? null;

        if ($answer === null) {
            throw new \CheckException("expected answer for the question");
        }

        if (strlen($answer) !== 5 || !preg_match('/^\d{5}$/i', $answer)) {
            return LoginResult::question(self::EMAIL_OTC_QUESTION, 'Expected 5-digits code');
        }

        for ($i = 0; $i < 5; $i++) {
            $inputs[$i]->click();
            $inputs[$i]->setValue(substr($answer, $i, 1));
        }

        $button = $tab->evaluate('//button[normalize-space()="Verify"]');
        $button->click();

        $result = $this->waiter->waitFor(function () use ($tab) {
            return $tab->evaluate('//div[contains(@class, "notification--error")]', EvaluateOptions::new()->allowNull(true)->timeout(5));
        });

        if ($result) {
            $error = $tab->evaluate('//div[contains(@class, "notification--error")]');

            return LoginResult::question(self::EMAIL_OTC_QUESTION, $error->getInnerText());
        }

        $result = $this->waiter->waitFor(function () use ($tab) {
            return $tab->evaluate('//span[contains(@class, "logged-in-user-initial")]', EvaluateOptions::new()->allowNull(true)->timeout(10));
        });

        if ($result) {
            return LoginResult::success();
        }

        return new LoginResult(false);
    }

    public function parse(Tab $tab, Master $master, AccountOptions $accountOptions): void
    {
        $master->createStatement()->setBalance(str_replace(',', '', $tab->evaluate('//a[contains(@href, "spend-miles")]/preceding::text()[normalize-space()][1][not(contains(normalize-space(), "miles"))]', EvaluateOptions::new()->nonEmptyString())->getInnerText()));
    }

    public function logout(Tab $tab): void
    {
        $tab->gotoUrl('https://www.etihad.com/ada-services/ey-login/logout/v1');
    }
}
