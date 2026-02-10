<?php

namespace AwardWallet\Engine\navy;

use AwardWallet\ExtensionWorker\AbstractParser;
use AwardWallet\ExtensionWorker\AccountOptions;
use AwardWallet\ExtensionWorker\Credentials;
use AwardWallet\ExtensionWorker\EvaluateOptions;
use AwardWallet\ExtensionWorker\FindTextOptions;
use AwardWallet\ExtensionWorker\LoginResult;
use AwardWallet\ExtensionWorker\LoginWithIdInterface;
use AwardWallet\ExtensionWorker\Message;
use AwardWallet\ExtensionWorker\Tab;
use AwardWallet\Schema\Parser\ParserTraits\TextTrait;

class NavyExtension extends AbstractParser implements LoginWithIdInterface
{
    use TextTrait;

    public function getStartingUrl(AccountOptions $options): string
    {
        return 'https://digitalomni.navyfederal.org/nfcu-online-banking/accounts/list/summary';
    }

    public function isLoggedIn(Tab $tab): bool
    {
        $formOrProfile = $tab->evaluate('//form[@id="loginForm"] | //button[@id="signout" or @id="signoutHeaderResp"]');

        return stristr($formOrProfile->getAttribute('id'),'signout');
    }

    public function getLoginId(Tab $tab): string
    {
        return strtolower($tab->findText('//div[@data-role="user-full-name"]/span', FindTextOptions::new()->visible(false)));
    }

    public function login(Tab $tab, Credentials $credentials): LoginResult
    {
        $login = $tab->evaluate('//input[@id="username"]');
        $login->setValue($credentials->getLogin());

        $password = $tab->evaluate('//input[@id="password"]');
        $password->setValue($credentials->getPassword());

        $tab->evaluate('//button[@id="signInButton"]')->click();

        $loginResult = $tab->evaluate('//p[contains(text(), "The username or password you entered was incorrect. Please try again.")] 
        | //h1[contains(text(), "2-Step Verification")]
        | //button[@id="signout" or @id="signoutHeaderResp"]');
        if (!$loginResult) {
            return new LoginResult(false);
        }
        if (stristr($loginResult->getAttribute('id'),'signout')) {
            return LoginResult::success();
        }
        $innerText = $loginResult->getInnerText();

        if (stristr($innerText, "2-Step Verification")) {
            $tab->showMessage(Message::identifyComputerSelect('Send'));
            $verifyCode = $tab->evaluate('//input[@id="otp"]',
                EvaluateOptions::new()->timeout(120)->allowNull(true));
            if ($verifyCode) {
                $tab->showMessage(Message::identifyComputer('Submit'));
                $loginSuccess = $tab->evaluate('//button[contains(text(),"Remember Browser")] | //button[@id="signout" or @id="signoutHeaderResp"]',
                    EvaluateOptions::new()->timeout(120)->allowNull(true));
                if ($loginSuccess) {
                    $loginSuccess->click();
                    return LoginResult::success();
                }
            }
            return LoginResult::success();
        } else {
            if (strstr($innerText, 'The username or password you entered was incorrect. Please try again.')) {
                return LoginResult::invalidPassword($innerText);
            }
            return new LoginResult(true);
        }
    }

    public function logout(Tab $tab): void
    {
        $tab->evaluate('//button[@id="signout"]')->click();
        $tab->evaluate('//form[@id="loginForm"]');
    }
}
