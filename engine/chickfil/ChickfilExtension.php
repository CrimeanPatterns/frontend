<?php

namespace AwardWallet\Engine\chickfil;

use AwardWallet\Common\Parsing\Exception\AcceptTermsException;
use AwardWallet\ExtensionWorker\AbstractParser;
use AwardWallet\ExtensionWorker\AccountOptions;
use function AwardWallet\ExtensionWorker\beautifulName;
use AwardWallet\ExtensionWorker\Credentials;
use AwardWallet\ExtensionWorker\EvaluateOptions;
use AwardWallet\ExtensionWorker\FindTextOptions;
use AwardWallet\ExtensionWorker\LoginResult;
use AwardWallet\ExtensionWorker\LoginWithIdInterface;
use AwardWallet\ExtensionWorker\ParseInterface;
use AwardWallet\ExtensionWorker\Tab;
use AwardWallet\Schema\Parser\Component\Master;
use AwardWallet\Schema\Parser\ParserTraits\TextTrait;

class ChickfilExtension extends AbstractParser implements LoginWithIdInterface, ParseInterface
{
    use TextTrait;
    public $authType = '';
    private const VERIFY_DEVICE = '//h1[contains(@class, "title-text red")]/following-sibling::div[1][@class="title-sub-text"]';

    public function getStartingUrl(AccountOptions $options): string
    {
        switch ($options->login2) {
            case 'google':
                $this->authType = 'google';
                break;

            case 'apple':
                $this->authType = 'apple';
                break;

            default:
                break;
        }

        return 'https://order.chick-fil-a.com/status';
    }

    public function isLoggedIn(Tab $tab): bool
    {
        $tab->evaluate('//button[contains(text(),"Sign In")] 
        | //button[@data-cy="SignOut"]', EvaluateOptions::new()->visible(false));
        sleep(1);
        $result = $tab->evaluate('//button[contains(text(),"Sign In")] 
        | //button[@data-cy="SignOut"]', EvaluateOptions::new()->visible(false));

        return str_starts_with($result->getAttribute('data-cy'), "SignOut")
            || $result->getNodeName() == 'H5'
            || $result->getInnerText() == 'Sign Out';
    }

    public function getLoginId(Tab $tab): string
    {
        return $tab->findText('//h5[contains(text(),"Membership #")]/following-sibling::div',
            FindTextOptions::new()->preg('/^[\d\s]{5,}$/'));
    }

    public function logout(Tab $tab): void
    {
        $tab->evaluate('//button[@data-cy="SignOut"]', EvaluateOptions::new()->visible(false))->click();
        $tab->evaluate('//button[contains(text(),"Yes, sign out")]')->click();
        $tab->evaluate('//button[contains(text(),"Sign In")]', EvaluateOptions::new()->visible(false));
    }

    public function login(Tab $tab, Credentials $credentials): LoginResult
    {
        sleep(1);

        $result = $this->waiter->waitFor(function () use ($tab) {
            return $tab->evaluate('//button[@title="Open drawer"] | //button[@data-cy="SignIn"]', EvaluateOptions::new()->allowNull(true)->timeout(20));
        });

        if ($result){
            $mobileMenu = $tab->evaluate('//button[@title="Open drawer"]', EvaluateOptions::new()->allowNull(true));
            if ($mobileMenu){
                $mobileMenu->click();
            }

            $signInBtn = $tab->evaluate('//button[@data-cy="SignIn"] | //button[normalize-space()="Sign in"]', EvaluateOptions::new()->allowNull(true));
            if ($signInBtn){
                $signInBtn->click();
            }
        }

        if ($this->authType === 'google') {
            $result = $this->loginForGoogle($tab, $credentials);
        } elseif ($this->authType === 'apple') {
            $result = $this->loginForApple($tab, $credentials);
        } else {
            $result = $this->loginForEmail($tab, $credentials);
        }

        return $result;
    }

    public function loginForGoogle(Tab $tab, Credentials $credentials): LoginResult
    {
        $result = $this->waiter->waitFor(function () use ($tab) {
            return $tab->evaluate('//text()[contains(normalize-space(), "with Google")]', EvaluateOptions::new()->allowNull(true)->timeout(10));
        });

        if ($result){
            $tab->evaluate("//text()[contains(normalize-space(), 'with Google')]/ancestor::a[1]")->click();
        }

        $result = $this->waiter->waitFor(function () use ($tab) {
            return $tab->evaluate('//input[@id="identifierId"] | //div[@data-authuser="0"] | //img[contains(@src, "profile_icon_new")]/ancestor::form[1]/descendant::p[1]', EvaluateOptions::new()->allowNull(true)->timeout(10));
        });

        $newUserError = $tab->evaluate('//img[contains(@src, "profile_icon_new")]/ancestor::form[1]/descendant::p[1]', EvaluateOptions::new()->allowNull(true));
        if ($newUserError){
            return LoginResult::providerError($newUserError->getInnerText());
        }

        $userElm = $tab->evaluate('//div[@data-authuser="0"]', EvaluateOptions::new()->allowNull(true));
        if ($userElm){
            $tab->showMessage('Please choose an account or select the "Use another account" option');
            $result = $this->waiter->waitFor(function () use ($tab) {
                return $tab->evaluate('//button[@data-cy="SignOut"]', EvaluateOptions::new()->allowNull(true)->timeout(30));
            });

            if (!$result){
                return LoginResult::identifyComputer();
            } else {
                $tab->gotoUrl('https://order.chick-fil-a.com/status');
                return new LoginResult(true);
            }
        } else {
            $loginElm = $tab->evaluate('//input[@id="identifierId"]', EvaluateOptions::new()->allowNull(true));
            if ($loginElm){
                $loginElm->setValue($credentials->getLogin());
                $continueBtn = $tab->evaluate('//button[normalize-space()="Next"]', EvaluateOptions::new()->allowNull(true));
                if ($continueBtn){
                    $continueBtn->click();
                }
            }

            $errorLogin = $tab->evaluate('//div[normalize-space()="Couldn’t find your Google Account" and @aria-atomic="true"]',
                EvaluateOptions::new()
                    ->timeout(5)
                    ->allowNull(true));
            if ($errorLogin){
                return LoginResult::invalidPassword($errorLogin->getInnerText());
            }

            $result = $this->waiter->waitFor(function () use ($tab) {
                return $tab->evaluate('//input[@name="Passwd"]', EvaluateOptions::new()->allowNull(true)->timeout(10));
            });

            if ($result){
                $tab->evaluate('//input[@name="Passwd"]')->setValue($credentials->getPassword());
                $tab->evaluate('//button[normalize-space()="Next"]')->click();

                $errorPassword = $tab->evaluate('//div[contains(normalize-space(), "Wrong password") and @aria-live="polite"]',
                    EvaluateOptions::new()
                        ->allowNull(true)
                        ->timeout(5));
                if ($errorPassword){
                    LoginResult::invalidPassword($errorPassword->getInnerText());
                }
            }

            $faElm = $tab->evaluate('//div[@id="headingSubtext"]',
                EvaluateOptions::new()
                    ->allowNull(true)
                    ->timeout(5));
            if ($faElm){
                $tab->showMessage($faElm->getInnerText());

                $result = $this->waiter->waitFor(function () use ($tab) {
                    return $tab->evaluate('//div[contains(@jsaction, "CLIENT")]/descendant::button[last()]', EvaluateOptions::new()->allowNull(true)->timeout(10));
                });

                if ($result){
                    $tab->evaluate('//div[contains(@jsaction, "CLIENT")]/descendant::button[last()]')->click();
                }

                $result = $this->waiter->waitFor(function () use ($tab) {
                    return $tab->evaluate('//text()[normalize-space()="OK"]', EvaluateOptions::new()->allowNull(true)->timeout(10));
                });

                if ($result){
                    $tab->gotoUrl('https://order.chick-fil-a.com/status');
                    return LoginResult::success();
                }
            }

            $result = $this->waiter->waitFor(function () use ($tab) {
                return $tab->evaluate('//button[@data-cy="SignOut"]', EvaluateOptions::new()->allowNull(true)->timeout(30));
            });

            if ($result){
                $tab->gotoUrl('https://order.chick-fil-a.com/status');
                return new LoginResult(true);
            }
        }
        return new LoginResult(false);
    }

    public function loginForApple(Tab $tab, Credentials $credentials): LoginResult
    {
        $result = $this->waiter->waitFor(function () use ($tab) {
            return $tab->evaluate('//text()[contains(normalize-space(), "with Apple")]', EvaluateOptions::new()->allowNull(true)->timeout(10));
        });

        if ($result){
            $tab->evaluate("//text()[contains(normalize-space(), 'with Apple')]/ancestor::a[1]")->click();
        }

        $inputElm = $tab->evaluate('//input[@id="account_name_text_field"]');
        if ($inputElm){
            $inputElm->setValue($credentials->getLogin());
        }

        $nextBtn = $tab->evaluate('//button[@id="sign-in"]');
        if ($nextBtn){
            $nextBtn->click();
        }

        $inputPss = $tab->evaluate('//input[@id="password_text_field"]');
        if ($inputPss){
            $inputPss->setValue($credentials->getPassword());
        }

        $loginBtn = $tab->evaluate('//button[@id="sign-in"]');
        if ($loginBtn){
            $loginBtn->click();
        }

        $errorMss = $tab->evaluate('//div[contains(@class, "signin-error")] | //h1[contains(@class, "tk-intro")]');
        if ($errorMss->getNodeName() == 'DIV'){
            return LoginResult::invalidPassword($errorMss->getInnerText());
        }

        $codeMss = $tab->evaluate('//div[@class="signin-container-footer__info"]', EvaluateOptions::new()->allowNull(true));
        if ($codeMss){
            $tab->showMessage($codeMss->getInnerText());

            $result = $tab->evaluate('//div[contains(@id, "form-security-code-error")] | //div[@class="trust-browser"]', EvaluateOptions::new()->timeout(60));
            if (stripos($result->getAttribute('class'), 'error') !== false){
                return LoginResult::identifyComputer();
            } else {
                $tab->showMessage($result->getInnerText());
                $result = $tab->evaluate('//button[@data-cy="SignOut"]', EvaluateOptions::new()->timeout(60));
                if (!$result){
                    return LoginResult::identifyComputer();
                }
            }
        }

        $result = $this->waiter->waitFor(function () use ($tab) {
            return $tab->evaluate('//p[@class="profile__description profile__description--nofrom"]', EvaluateOptions::new()->allowNull(true)->timeout(10));
        });

        if ($result){
            $acceptUser = $tab->evaluate('//p[@class="profile__description profile__description--nofrom"]');
            $tab->showMessage($acceptUser->getInnerText());

            $result = $this->waiter->waitFor(function () use ($tab) {
                return $tab->evaluate('//button[@data-cy="SignOut"] | //div[@class="title-sub-text"]', EvaluateOptions::new()->allowNull(true)->timeout(20));
            });
            if ($result){
                $errorOrSuccess = $tab->evaluate('//button[@data-cy="SignOut"] | //div[@class="title-sub-text"]');
                if ($errorOrSuccess->getNodeName() == 'DIV'){
                    return LoginResult::providerError($errorOrSuccess->getInnerText());
                }
            }
        }

        if (strstr($tab->getUrl(), '/get-started')) {
            $tab->gotoUrl('https://order.chick-fil-a.com/status');
            return new LoginResult(true);
        }

        return new LoginResult(false);
    }

    public function loginForEmail(Tab $tab, Credentials $credentials): LoginResult
    {
        $tab->evaluate('//button[contains(text(),"Sign In")]', EvaluateOptions::new()->visible(false))->click();
        $result = $tab->evaluate('
            //input[@name="pf.username"]
            | //div[contains(text(),"re having trouble logging you in")]
        ');
        // We're having trouble logging you in
        // You may have entered an incorrect email address or password, so please try again. Or you may have reached the maximum number of accounts for this device. In that case, please sign in with a previously accessed account.
        if (stristr($result->getInnerText(), "We're having trouble logging you in")) {
            return LoginResult::providerError($result->getInnerText());
        }

        $tab->evaluate('//input[@name="pf.username"]')->setValue($credentials->getLogin());
        $tab->evaluate('//input[@name="pf.pass"]')->setValue($credentials->getPassword());
        $tab->evaluate('//button[@name="pf.ok"]')->click();

        $result = $this->waiter->waitFor(function() use ($tab) {
            return $tab->evaluate(self::VERIFY_DEVICE, EvaluateOptions::new()->allowNull(true)->timeout(10));
        });

        if ($result){
            $message = $tab->evaluate(self::VERIFY_DEVICE);
            $tab->showMessage($tab->evaluate(self::VERIFY_DEVICE)->getInnerText());

            $resultVerify = $this->waiter->waitFor(function() use ($tab) {
                return $tab->evaluate('//button[@data-cy="SignOut"]', EvaluateOptions::new()->allowNull(true)->timeout(180)->allowNull(true));
            });

            if (!$resultVerify){
                return LoginResult::providerError($message);
            }
        }

        $result = $tab->evaluate('
            //div[@class="err"]
            | //p[contains(text(),"We just need to verify your details. We\'ve sent a verification code to:")] 
            | //h5[contains(text(),"Membership #")]/following-sibling::div
            | //h1[contains(text(),"My Status")]
            | //h1[contains(text(),"What type of order can we get started for you?")]
            | //div[contains(text(),"re having trouble logging you in")]
            ');

        // We're having trouble logging you in
        // You may have entered an incorrect email address or password, so please try again. Or you may have reached the maximum number of accounts for this device. In that case, please sign in with a previously accessed account.
        if (stristr($result->getInnerText(), "We're having trouble logging you in")) {
            return LoginResult::providerError($result->getInnerText());
        }

        // We didn't recognize the username or password you entered. Please try again.
        if (stristr($result->getInnerText(), "We didn't recognize the username or password you entered. Please try again.")) {
            return LoginResult::invalidPassword($result->getInnerText());
        }

        if (str_starts_with($result->getInnerText(), "That email or password doesn’t look right")) {
            return LoginResult::invalidPassword($result->getInnerText());
        }


        if (strstr($tab->getUrl(), '/get-started')) {
            $tab->gotoUrl('https://order.chick-fil-a.com/status');
            return new LoginResult(true);
        }

        return new LoginResult(false);
    }

    public function parse(Tab $tab, Master $master, AccountOptions $accountOptions): void
    {
        $st = $master->createStatement();

        if ($tab->findTextNullable('//div[@id = "titleSubText" and (contains(text(), "We\'ve made some updates to our Privacy Policy. Learn more about the updates to the Privacy Policy and how we") or contains(text(), "We\'ve made some updates to our Chick-fil-A Terms"))]')) {
            throw new AcceptTermsException();
        }
        $tab->findText('//h5[contains(text(), "Lifetime points earned")]/following-sibling::div[text()!="NaN"]');

        // Balance - REWARDS BALANCE: 1803 PTS
        $st->setBalance($tab->findText('//h5[contains(text(), "Available points")]/following-sibling::h2',
            FindTextOptions::new()->pregReplace('/[^\d.,]+/', '')),
        );

        // Name
        //$st->addProperty('Name', beautifulName($tab->findText("//div[@class='cp-nav__details']//h4")));
        // CHICK-FIL-A ONE MEMBER
        $st->addProperty('Status',
            beautifulName($tab->findText('//h1[contains(text(),"My Status")]/following-sibling::div//h3')));
        // Your Chick-fil-A One™ red status is valid through ...
        $expStatus = $tab->findTextNullable('//h1[contains(text(),"My Status")]/following-sibling::div//h3/../following-sibling::div[contains(text(), "Valid until")]',
            FindTextOptions::new()->preg('#Valid until (.+)#'));
        if ($expStatus) {
            $st->addProperty('StatusExpiration', $expStatus);
        }
        // Lifetime points earned
        $st->addProperty('TotalPointsEarned', $tab->findText('//h5[contains(text(), "Lifetime points earned")]/following-sibling::div'));
        // Earn ... to reach ... Status.
        $st->addProperty('PointsNextLevel', $tab->findTextNullable('//div[contains(text(), "more points by the end of this year.")]',
            FindTextOptions::new()->preg("/Earn (.+?) more/ims")->pregReplace('/[^\d.,]+/', '')));
        // MEMBERSHIP #
        $st->addProperty('AccountNumber', $tab->findText('//h5[contains(text(),"Membership #")]/following-sibling::div',
            FindTextOptions::new()->preg('/^[\d\s]{5,}$/')->pregReplace('/\s+/', '')));
        // MEMBER SINCE
        /*$memberSince = $tab->findTextNullable("//h5[contains(text(),'Member Since')]/following-sibling::p");
        if ($memberSince)
            $st->addProperty('MemberSince', $memberSince);*/

        $tab->gotoUrl('https://order.chick-fil-a.com/my-rewards');

        $tab->findText('//h1[contains(text(),"You don\'t have any rewards")]',
            FindTextOptions::new()->allowNull(true));

        if ($tab->findTextNullable('//h1[contains(text(),"You don\'t have any rewards")]')) {
            $this->logger->notice("Rewards not found");

            return;
        }

        $tab->evaluate("//div[@id = 'my-rewards-set']/div[contains(@class, 'reward-card')] | //li[@data-cy='Reward']",
            EvaluateOptions::new()->timeout(5)->allowNull(true));
        $rewards = $tab->evaluateAll("//div[@id = 'my-rewards-set']/div[contains(@class, 'reward-card')] | //li[@data-cy='Reward']");
        $this->logger->debug("Total " . count($rewards) . " rewards were found");

        foreach ($rewards as $reward) {
            $displayName = $tab->findText(
                ".//div[div/div/div[@class = 'reward-details'] and position() = 1]//div[@class = 'reward-details']/h5 
                | //h4[@data-cy = 'RewardName']", FindTextOptions::new()->contextNode($reward));
            $exp = $tab->findText(".//*[self::p or self::div][contains(text(), 'Valid through')]",
                FindTextOptions::new()->contextNode($reward)->preg("/Valid\s*through\s*(.+)/"));
            $this->logger->debug("{$displayName} / Exp date: {$exp}");
            $exp = strtotime($exp, false);
            $st->addSubAccount([
                'Code'           => 'chickfil' . str_replace([' ', '®', '™', ','], '', $displayName) . $exp,
                'DisplayName'    => $displayName,
                'Balance'        => null,
                'ExpirationDate' => $exp,
            ]);
        }
    }
}
